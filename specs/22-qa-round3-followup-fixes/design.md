# HLZ-43 (ampliación) + HLZ-47 + HLZ-48 — Diseño del fix

## Decisiones de diseño

Tres correcciones independientes en dominios distintos, agrupadas en un solo feature por ser todas de bajo alcance (1-2 archivos por corrección) — no comparten código ni capa.

### 1. HLZ-43 — patch mínimo en backend, no mover el label al frontend

Se descartó mover la resolución del label al frontend (mandar el `type` crudo del período y que `Index.vue` arme el copy). Es más "correcto" a futuro, pero toca más superficie (tipos, composable, template) para un bug de una condición faltante. El fix cabe en `buildRules()`: condicionar explícitamente por `$period->type`, igual que ya se hace para `PeriodType::Year`, en vez de dejar la asignación de la línea 209 corriendo sin condición.

```php
private function buildRules(?Period $period, ?Student $student): array
{
    $periodLabel = match ($period?->type) {
        PeriodType::Year => null, // se resuelve abajo con el Lapse vigente
        PeriodType::Semester => $period->name,
        default => '',
    };

    if ($period?->type === PeriodType::Year) {
        $lapse = $period->lapses()
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->first();

        $periodLabel = $lapse?->name ?? $period->name;
    }

    return [
        'period' => $period?->name,
        // ...
        'trimester' => $periodLabel,
    ];
}
```

En `resources/js/pages/enrollment/Index.vue`, el copy de ayuda hardcodeado "este trimestre" (línea ~152) se reemplaza por el label ya resuelto (`uiRules.trimester`), consistente con lo que ya se muestra en el encabezado de la línea ~149 — sin introducir lógica nueva en el frontend.

### 2. HLZ-47 — prop tipada, no parche de binding

Se descartó mantener `usePage().props.auth.user` y solo cambiar `:default-value` por `:value`/`v-model`. Ese parche podría ocultar el síntoma sin corregir la causa señalada explícitamente en `CLAUDE.md`: *"usePage().props.auth solo dentro de composables de permisos — nunca en páginas"*. `Profile.vue` hoy la usa directo en el cuerpo de la página, violando esa convención.

Se descartó también reutilizar `App\Http\Resources\Security\UserResource` (existe y ya se usa en el listado admin de usuarios) porque carga campos ajenos al contexto de auto-edición de perfil (`roles`, `student_level`, `guardians_count`, etc. — pensado para que un admin liste usuarios, no para que un usuario vea su propio perfil). Se crea un resource nuevo, mínimo, en el namespace de Settings:

**`app/Http/Resources/Settings/ProfileUserResource.php`** (nuevo):
```php
class ProfileUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
        ];
    }
}
```

**Nota (2026-09-13, tras bloqueo del tester en modo `pre`):** el diseño original omitía `email_verified_at`. `Profile.vue` usa ese campo para el badge "Correo verificado." (`v-if="user.email_verified_at"`), visible hoy para todo usuario porque los seeders fijan `email_verified_at`. Sin este campo, el badge desaparecería silenciosamente para todos — regresión no contemplada en el alcance. El humano decidió agregarlo: cero cambio de alcance real, un campo más en el mismo Resource nuevo.

**`ProfileController::edit()`:**
```php
public function edit(Request $request): Response
{
    return Inertia::render('settings/Profile', [
        'user' => new ProfileUserResource($request->user()),
        'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
        'status' => $request->session()->get('status'),
    ]);
}
```

**`resources/js/pages/settings/Profile.vue`:** reemplazar `const user = computed(() => page.props.auth.user)` por una prop tipada:
```ts
interface Props {
    user: { id: number; name: string; email: string; email_verified_at: string | null };
    mustVerifyEmail: boolean;
    status?: string;
}
const props = defineProps<Props>();
```
Y usar `props.user.name` / `props.user.email` en los `:default-value`, mismo patrón que ya funciona en `teams/Edit.vue:111` (prop tipada directa, no `usePage()`). El tipo puede vivir inline en el componente (2 campos) o en `resources/js/types/` si se prefiere consistencia con el resto del proyecto — se decide en implementación, no cambia el enfoque.

**Nota (2026-09-13, tras implementación de Task 2):** el supuesto de este párrafo era incorrecto — `:default-value` **nunca funcionó**, ni en `Profile.vue` ni presumiblemente en `teams/Edit.vue`. El implementer confirmó vía HTML renderizado que `shouldSetAsProp` de `@vue/runtime-dom` evalúa `key in el` con la clave literal `"default-value"` (con guion), que no es una IDL prop real del elemento (`defaultValue` es camelCase) — cae a `setAttribute`, sin efecto sobre `.value`. `teams/Edit.vue` nunca tuvo un test Dusk que lo detectara. Se cambió `:default-value` → `:value` en los dos inputs de `Profile.vue` (único archivo, no protegido) para que el prefill funcione de verdad — sin esto, HLZ-47 queda sin resolver sin importar de dónde venga `user`. `teams/Edit.vue` probablemente tiene el mismo bug latente, sin cobertura — fuera de alcance de este feature, candidato a HLZ nuevo si el humano lo confirma.

### 3. HLZ-48 — subir el límite del profesor, no reducir la carga sembrada

Se descartó reducir la carga horaria que `DemoSectionsSeeder` asigna a los profesores sobre el límite. Esa opción es más "realista" en el sentido de mantener `weekly_hour_limit = 20` como techo fijo, pero implica tocar qué secciones/horarios tiene cada profesor — con riesgo real de invalidar datos que la feature `21-student-schedule-view` ya usó y verificó en su QA Gate (`est109` con las secciones reales de Luis Rodríguez y María Torres). Subir `weekly_hour_limit` es un cambio de un solo campo por profesor, sin tocar `sections`/`schedules` existentes — cero riesgo de romper escenarios de QA ya validados.

`database/seeders/Demo/DemoProfessorsSeeder.php:57` fija `weekly_hour_limit => 20` para todos. `database/seeders/Demo/DemoSectionsSeeder.php` (método que crea los `Schedule` de cada sección, ~línea 174-194) escribe los horarios directo a DB sin pasar por `ScheduleConflictService::professorWeeklyHoursExceeded()` — de ahí que la carga real termine por encima del límite sin que nada lo detecte en seed-time.

**Enfoque:** después de que `DemoSectionsSeeder` corre (o en un paso posterior dentro del mismo comando de seed), calcular la carga horaria real asignada a cada profesor y, si supera su `weekly_hour_limit`, subirlo a un valor con margen razonable (ej. carga real redondeada hacia arriba + un margen fijo, para no dejarlo exactamente en el límite sin holgura para futuras altas). El orden exacto de seeders y el punto donde insertar este ajuste se confirma en implementación — no se investigó a fondo el orden de `DatabaseSeeder::run()` en esta fase de diseño.

**Riesgo a verificar en implementación:** `demo-seeder-realism` figura como `status: in_progress` en `specs/feature_list.json`, sin evidencia de commit reciente ni de tocar `weekly_hour_limit` o `DemoSectionsSeeder` en sus specs. No parece haber solapamiento real (esa feature toca currículum de bachillerato, representantes multi-estudiante e historial académico — no horarios de profesores), pero el implementer debe confirmar contra el estado real del seeder antes de tocarlo, para no pisar trabajo en curso de esa feature si es que sigue activa.

## Sin cambios en

- `ScheduleConflictService::professorWeeklyHoursExceeded()` — la validación de negocio es correcta, no se toca.
- `StoreScheduleRequest` / `UpdateScheduleRequest` — sin cambios de reglas.
- Migraciones — no se agrega ninguna columna ni tabla nueva.
