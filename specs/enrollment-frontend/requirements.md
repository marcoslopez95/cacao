# Requisitos — Enrollment Frontend Integration

**Feature:** `02-enrollment-frontend`
**Depende de:** `01-enrollment-backend` (completado)

---

## Objetivo

Conectar la interfaz de inscripción (ya implementada con datos mock en `enrollmentMockData.ts`) al backend real. El estudiante o representante debe poder ver las materias disponibles de su pensum, seleccionar secciones, agregar/eliminar materias de su inscripción en borrador, y confirmar la inscripción — todo sin recargar la página.

---

## Actores

| Actor | Descripción |
|-------|-------------|
| `Student` | Estudiante universitario autenticado. Ve y edita su propia inscripción. |
| `Guardian` | Representante autenticado. Ve y edita la inscripción de un estudiante asignado (requiere `?student_id=` en query param). |

---

## Reglas de negocio (notación EARS)

### Carga de la página

- WHEN un `Student` autenticado visita `/enrollment`, THE SYSTEM SHALL buscar o crear automáticamente una `Enrollment` con `status = draft` para el período activo y servir el catálogo de materias del pensum activo del estudiante.
- WHEN un `Guardian` visita `/enrollment` sin `student_id` en query param Y tiene exactamente un estudiante asignado, THE SYSTEM SHALL cargar la inscripción de ese estudiante.
- WHEN un `Guardian` visita `/enrollment` sin `student_id` Y tiene más de un estudiante asignado, THE SYSTEM SHALL devolver una prop `guardian_students` para que la UI muestre un selector de estudiante.
- WHEN un `Guardian` intenta acceder a la inscripción de un estudiante no asignado, THE SYSTEM SHALL rechazar con HTTP 403.
- WHEN no existe un período activo, THE SYSTEM SHALL pasar `catalog: []` y `rules.period = null` para que la UI muestre el estado vacío "No hay período activo".
- WHEN el estudiante no tiene pensum activo (`current_pensum_id = null`), THE SYSTEM SHALL pasar `catalog: []` y la UI mostrará "No tienes un pensum asignado".

### Construcción del catálogo

- WHEN se construye el catálogo, THE SYSTEM SHALL incluir únicamente las materias del pensum activo del estudiante que tengan al menos una sección abierta en el período activo.
- WHEN se construye el catálogo, THE SYSTEM SHALL marcar `prereqs_ok = true` por materia usando `PrerequisiteValidator::canTake()`.
- WHEN se construye el catálogo, THE SYSTEM SHALL marcar `completed = true` si el estudiante tiene un `EnrollmentDetail` con `status = confirmed` dentro de un `Enrollment` con `status = approved` para esa materia.
- WHEN se construye el catálogo, THE SYSTEM SHALL marcar `recommended_trim = true` si `subject.period_number === student.academic_year`.
- WHEN existe una `Enrollment` en borrador con detalles, THE SYSTEM SHALL incluir en cada materia el `selected_section_id` correspondiente (o `null` si no está seleccionada).

### Estado de cupos por sección

- WHEN se construye el catálogo, THE SYSTEM SHALL incluir para cada sección el conteo real de `enrollment_details` con `status IN ('draft', 'confirmed')` como campo `enrolled`.
- WHEN `section.enrolled >= section.capacity`, THE SYSTEM SHALL marcar `is_full = true` en esa sección.

### Agregar materia a la inscripción

- WHEN el frontend envía POST `/enrollment/{id}/detail` con `subject_id` y `section_id`, THE SYSTEM SHALL validar cupo, prelaciones y duplicado, y responder con el `EnrollmentDetail` creado (201) o un error 422 con mensaje legible en español.
- WHEN la respuesta es exitosa, THE SYSTEM SHALL reflejar el cambio localmente en la UI sin recargar la página completa.
- WHEN la respuesta es 422, THE SYSTEM SHALL mostrar el mensaje de error en la UI y NO guardar la selección.

### Eliminar materia de la inscripción

- WHEN el frontend envía DELETE `/enrollment/{id}/detail/{detailId}`, THE SYSTEM SHALL marcar el `EnrollmentDetail` como `rejected` y responder `{"success": true}` (200).
- WHEN la respuesta es exitosa, THE SYSTEM SHALL desmarcar la sección en la UI localmente.

### Confirmar inscripción

- WHEN el frontend envía POST `/enrollment/{id}/confirm`, THE SYSTEM SHALL re-validar cupos y prelaciones con pessimistic DB lock y responder con el `Enrollment` actualizado (200) o un error 422.
- WHEN la confirmación es exitosa, THE SYSTEM SHALL recargar la página para reflejar el estado `confirmed` (read-only).
- WHEN la respuesta es 422 al confirmar, THE SYSTEM SHALL mostrar el mensaje de error en el panel de resumen y mantener el estado `draft`.

### Autorización

- WHEN la `Enrollment` tiene `status != draft`, THE SYSTEM SHALL mostrar la UI en modo solo-lectura (sin botones de agregar/eliminar/confirmar).
- WHEN un usuario sin `student` ni `guardian` intenta acceder a `/enrollment`, THE SYSTEM SHALL responder con HTTP 403.

---

## Restricciones técnicas

1. Llamadas al backend vía `useHttp` (Inertia v3) — sin Axios, sin `fetch` crudo.
2. Rutas importadas desde `@/routes/enrollment` via Wayfinder — sin strings hardcodeados.
3. Los datos iniciales de la página llegan como Inertia props — sin llamadas XHR adicionales al cargar.
4. `router.post/.put/.delete` solo dentro de `useEnrollmentForm.ts` — nunca en componentes o página.
5. La UI se actualiza localmente (local state) en operaciones exitosas — el servidor no re-renderiza salvo en `confirm`.
6. No se elimina ningún componente UI existente — solo se desacoplan de `enrollmentMockData`.
