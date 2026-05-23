# Grades Module — Requirements

## Resumen

Módulo completo de notas académicas. La institución configura la estructura de evaluación (cantidad de notas, nombres y pesos). Los profesores registran las notas por estudiante. Los estudiantes y representantes las consultan.

---

## Actores

| Actor | Rol |
|---|---|
| Admin | Configura la estructura de notas por nivel educativo |
| Profesor | Carga y gestiona notas de sus secciones |
| Estudiante | Consulta sus notas (solo lectura) |
| Representante | Consulta notas del estudiante vinculado (solo lectura) |

---

## Requerimientos funcionales

### RF-01 — Configuración institucional de notas

- La institución define una configuración de notas por nivel educativo (`primary_secondary` / `university`).
- La configuración incluye: escala (numérica o letras), rango (min/max), umbral de aprobación, y lista de slots con nombre y peso.
- La suma de pesos de todos los slots debe ser exactamente 100%.
- Existe un slot especial `is_remedial = true` para la nota de reparación.
- La configuración es global por defecto. Si cambia para un período específico, se crea una versión `period_id` que aplica solo a ese período.

### RF-02 — Escala de notas

- **Nivel universitario**: siempre numérica, con rango configurable (ej. 0–20, 0–100, 0–5).
- **Nivel primaria/secundaria**: numérica con rango configurable, o letras (A–F) con equivalencias numéricas y marcador de aprobación por letra.
- El `passing_value` siempre se almacena en valor numérico, independientemente de la escala.

### RF-03 — Modo de evaluación por nivel educativo

- **Primaria/secundaria**: modo lapso — los slots de evaluación aplican dentro de cada `Lapse`. La nota definitiva del período es el promedio (igual peso) de las notas por lapso.
- **Universitario**: modo período — los slots aplican directamente al período completo. La nota definitiva es `Σ(slot.value × slot.weight / 100)`.

### RF-04 — Entrada de notas por el profesor

- El profesor carga notas por sección. La interfaz es una planilla: filas = estudiantes, columnas = slots institucionales.
- Para cada slot, el profesor puede:
  - Ingresar la nota directamente (un solo valor).
  - Subdividir en N sub-notas con nombre y peso libre (pesos deben sumar 100% dentro del slot). La nota del slot se calcula automáticamente como `Σ(sub.value × sub.weight / 100)`.
- Las sub-notas son definidas por el profesor, no por la institución.

### RF-05 — Reparación

- Si un estudiante reprueba, el admin habilita el slot `is_remedial` para ese `EnrollmentDetail` específico.
- El profesor carga la nota de reparación en ese slot.
- La nota de reparación reemplaza la nota definitiva si es mayor (lógica a confirmar con institución).

### RF-06 — Visibilidad de notas

- Configuración de equipo (`Team.grade_visibility`): `real_time` (default) o `manual`.
- **Tiempo real**: `is_published = true` automáticamente al guardar la nota. El estudiante la ve de inmediato.
- **Manual**: el profesor publica las notas de un slot completo para toda la sección con una acción explícita. Hasta ese momento el estudiante no las ve.

### RF-07 — Consulta de notas

- El estudiante ve sus notas por materia y período, con los valores por slot y la nota definitiva calculada.
- En modo `manual`, solo ve las notas publicadas.
- El representante ve exactamente lo mismo pero del estudiante vinculado.

---

## Requerimientos no funcionales

- Toda autorización mediante **Policies** — nunca condicionales directas en controladores.
- Cálculo de nota definitiva siempre en backend — nunca en el cliente.
- FK con `RESTRICT` — sin cascadas.
- Tests Pest para todo flujo crítico: configuración, entrada de notas, cálculo, visibilidad, acceso por rol.
