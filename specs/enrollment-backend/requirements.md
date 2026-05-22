# Requisitos — Enrollment Backend

**Feature:** `01-enrollment-backend`
**Plan fuente:** `docs/superpowers/plans/2026-05-20-enrollment-backend.md`

---

## Objetivo

Implementar el backend de inscripción de cursos en tiempo real con control de cupos, validación automática de prelaciones y soporte para estudiantes universitarios (auto-inscripción) y representantes (inscriben a estudiantes de nivel secundario/primario).

---

## Actores

| Actor | Descripción |
|-------|-------------|
| `Student` | Estudiante universitario. Se inscribe por sí mismo en materias de su pensum activo. |
| `Guardian` | Representante legal. Inscribe a un estudiante asignado (nivel no universitario). |

---

## Reglas de negocio (notación EARS)

### Inscripción — Creación de cabecera

- WHEN un `Student` autentica Y no tiene inscripción activa en el período vigente, THE SYSTEM SHALL crear una `Enrollment` con `status = draft` asociada al período activo y al pensum activo del estudiante.
- WHEN un `Guardian` autentica Y envía un `student_id` de un estudiante asignado, THE SYSTEM SHALL crear una `Enrollment` en nombre de ese estudiante.
- WHEN un `Guardian` intenta crear inscripción para un estudiante no asignado, THE SYSTEM SHALL rechazar la solicitud con HTTP 403.

### Inscripción — Control de cupos

- WHEN un usuario agrega una sección a una `Enrollment`, THE SYSTEM SHALL verificar disponibilidad de cupo en Redis (fast path) antes de escribir en base de datos.
- WHEN el cupo de la sección es cero, THE SYSTEM SHALL rechazar la adición con HTTP 422 y mensaje `"No hay cupos disponibles en esta sección."`.
- WHEN se agrega exitosamente un `EnrollmentDetail`, THE SYSTEM SHALL decrementar el contador de cupo en caché.
- WHEN se elimina un `EnrollmentDetail` (estado `draft`), THE SYSTEM SHALL incrementar el contador de cupo en caché.

### Inscripción — Validación de prelaciones

- WHEN un usuario agrega una materia a una `Enrollment`, THE SYSTEM SHALL consultar `PrerequisiteValidator` para verificar que el estudiante ha aprobado todas las prelaciones requeridas.
- WHEN alguna prelación no está aprobada, THE SYSTEM SHALL rechazar con HTTP 422 indicando los códigos de materias faltantes.
- WHEN la materia no tiene prelaciones, THE SYSTEM SHALL permitir la adición sin consulta de historial.

### Inscripción — Pensum

- WHEN un usuario agrega una materia, THE SYSTEM SHALL verificar que la materia pertenece al pensum activo del estudiante.
- WHEN la materia no está en el pensum, THE SYSTEM SHALL rechazar con HTTP 422.

### Inscripción — Duplicados

- WHEN un usuario intenta agregar una materia ya presente en la inscripción, THE SYSTEM SHALL rechazar con HTTP 422 y mensaje `"Ya estás inscrito en esta materia."`.

### Inscripción — Confirmación

- WHEN un usuario confirma una `Enrollment` vacía (sin detalles `draft`), THE SYSTEM SHALL rechazar con HTTP 422.
- WHEN un usuario confirma una `Enrollment` con detalles, THE SYSTEM SHALL adquirir `lockForUpdate` sobre las secciones involucradas, revalidar cupos y prelaciones, y si todo pasa: actualizar todos los detalles a `confirmed`, la cabecera a `confirmed`, y calcular `uc_inscritas`.
- WHEN falla alguna validación durante la confirmación, THE SYSTEM SHALL hacer rollback de la transacción y devolver HTTP 422.

### Inscripción — Estado

- WHEN una `Enrollment` tiene `status != draft`, THE SYSTEM SHALL rechazar cualquier modificación (agregar/eliminar detalles, confirmar).

---

## Restricciones técnicas

- **Redis fast path:** lectura de cupo siempre pasa por caché Redis (TTL 30s). La DB es la fuente de verdad pero no se consulta en cada solicitud.
- **Pessimistic DB lock en confirmación:** `Section::lockForUpdate()` dentro de `DB::transaction()` para garantizar consistencia bajo concurrencia.
- **FKs con RESTRICT:** ninguna eliminación en cascada. Toda eliminación de datos relacionados es manual.
- **EnrollmentDetail como pivote central:** notas, asistencia y entregas cuelgan de `enrollment_detail_id` — nunca de `student_id`, `section_id` ni `enrollment_id` directamente.
- **Roles via Policy:** nunca `if ($user->role === 'admin')` en controladores. Toda autorización via `EnrollmentPolicy` y `EnrollmentDetailPolicy`.
