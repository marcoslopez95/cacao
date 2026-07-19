# Design — guardian-enrollment-entry

## Frontend only — sin cambios de backend

### `resources/js/pages/guardian/Dashboard.vue`

Agregar un botón CTA en el header de cada card de estudiante (junto al badge de estado), usando el mismo composable de ruta que ya usa `student/Dashboard.vue`:

```ts
import { index as enrollmentIndex } from '@/routes/enrollment'

function enrollmentCtaLabel(status: string | null): string {
  if (status === null) return 'Inscribir'
  if (status === 'draft') return 'Continuar inscripción'
  return 'Ver inscripción'
}
```

```html
<Link :href="enrollmentIndex.url({ query: { student_id: student.id } })" ...>
  {{ enrollmentCtaLabel(student.enrollment_status) }} →
</Link>
```

Estilo: mismo botón inline (`background:var(--accent);color:#fff;border-radius:var(--radius-md)`) que el CTA "Ir a inscripciones →" de `student/Dashboard.vue`, pero de tamaño más compacto para caber en el header de la card junto al badge.

Requiere agregar `import { Link } from '@inertiajs/vue3'` (hoy `guardian/Dashboard.vue` solo importa `usePage`).

### `resources/js/pages/enrollment/Index.vue`

Línea 33: cambiar el breadcrumb estático por el nombre real del estudiante, ya disponible en `uiRules.studentName`:

```ts
setLayoutProps({
    breadcrumbs: [
        { title: uiRules.studentName || 'Estudiante', href: '#' },
        { title: 'Inscripción', href: enrollmentIndex.url() },
    ],
})
```

Nota: `setLayoutProps` se llama antes de que `uiRules` exista como `computed` en el orden actual del script — hay que mover la declaración de `uiRules` antes de `setLayoutProps`, o convertir `breadcrumbs` en un `computed` pasado a `setLayoutProps`. Verificar el contrato de `setLayoutProps` (¿acepta reactividad o es un snapshot?) antes de implementar; si es snapshot estático, reordenar el `computed` de `uiRules` arriba del `setLayoutProps` alcanza (el prop `rules` ya está disponible en el momento del render inicial, no cambia después).

## Backend

Ninguno. `EnrollmentController::resolveStudent()`, `EnrollmentPolicy` y `CreateEnrollmentAction` ya reciben `Student` explícito y ya están testeados para el flujo de representante.

## Tests

No hay tests de backend nuevos (sin cambios de backend). No existe convención de tests de componente Vue a nivel de página en este proyecto (solo se testean composables en `tests/js/composables/`) — se valida manualmente: `npm run dev` + navegar como representante desde `guardian/dashboard` hasta `/enrollment?student_id=X` y confirmar breadcrumb + botón.
