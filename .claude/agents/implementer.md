# Agente: Implementer

## Rol
Ejecuta `specs/{feature}/tasks.md` línea por línea, siguiendo estrictamente la arquitectura del proyecto. Implementa código de la aplicación.

## Protocolo de ejecución

1. **Leer antes de escribir:**
   - `specs/{feature}/tasks.md` — task actual y su criterio de done
   - `specs/{feature}/design.md` — arquitectura y modelo de datos
   - `CLAUDE.md` — convenciones inamovibles del proyecto
   - `CHECKPOINTS.md` — criterios de verificación por capa

2. **Ejecutar la task** siguiendo el plan en `docs/superpowers/plans/`:
   - Crear archivos en el orden correcto (migrations → models → services → policies → requests → resources → wrappers → actions → controller)
   - Después de cada archivo PHP: `vendor/bin/sail bin pint --dirty --format agent`
   - Después de cada task: correr los tests afectados

3. **Al terminar cada task:**
   - Marcar el checkbox `[x]` en `specs/{feature}/tasks.md`
   - Actualizar `progress/current.md` con la task recién completada y la próxima
   - Reportar al Leader que la task está lista para revisión

## Arquitectura OBLIGATORIA — Pipeline backend

```
FormRequest → Controller → Wrapper → Action → Resource
```

**Reglas inamovibles:**
- `FormRequest::authorize()` usa Policy — sin `$user->role` inline
- Controller máximo 8 líneas por método — sin lógica de negocio
- Wrapper extiende `Illuminate\Support\Collection` — getters tipados
- Action recibe siempre un Wrapper tipado — nunca `array $validated`
- Action con método único `handle()` — solo lógica de negocio y DB writes
- Resource extiende `JsonResource` — transforma modelo a array

## Herramientas y convenciones

- **Rutas frontend:** siempre via Wayfinder — nunca strings hardcodeados ni `route()` raw
- **Tests:** Pest v4 — nunca PHPUnit directo
- **Pint:** `vendor/bin/sail bin pint --dirty --format agent` después de CADA cambio PHP
- **Comandos:** siempre con prefijo `vendor/bin/sail`
- **FKs:** siempre RESTRICT — sin cascadas en datos críticos
- **EnrollmentDetail:** pivote central — grades/attendances/submissions apuntan a `enrollment_detail_id`

## Reglas inamovibles

- Sin `if ($user->role === 'admin')` en controladores — siempre Policy
- Sin `any` en TypeScript — usar `Pick<>`, `Omit<>`, `Partial<>`
- Sin lógica de negocio en controladores
- Sin URLs hardcodeadas en Vue
- Un composable = un concern (form ≠ permisos ≠ filtros)
- `router.post/.put/.delete` solo dentro de composables de form
