# Diseño — Enrollment Backend

**Feature:** `01-enrollment-backend`
**Plan fuente:** `docs/superpowers/plans/2026-05-20-enrollment-backend.md`

---

## Stack

| Capa | Tecnología |
|------|-----------|
| Backend | PHP 8.3 · Laravel 13 |
| Base de datos | PostgreSQL |
| Caché | Redis |
| Tests | Pest v4 |
| Rutas frontend | Laravel Wayfinder v0 |

---

## Arquitectura

El módulo sigue el pipeline obligatorio del proyecto:

```
HTTP Request
    → FormRequest   valida input + autoriza vía Policy en authorize()
    → Controller    crea Wrapper, inyecta Action, devuelve Resource (máx 8 líneas por método)
    → Wrapper       encapsula validated data con getters tipados
    → Action        recibe Wrapper, ejecuta lógica de negocio pura
    → Resource      transforma modelo → array para Inertia
```

Los Services (`PrerequisiteValidator`, `EnrollmentCacheManager`, `EnrollmentService`) son invocados desde las Actions — nunca desde los Controllers directamente.

---

## Modelo de datos

### Tablas

#### `students`
| Columna | Tipo | Notas |
|---------|------|-------|
| `id` | bigint PK | |
| `user_id` | bigint FK → users | RESTRICT, unique |
| `guardian_id` | bigint FK → guardians | RESTRICT, nullable |
| `educational_level` | enum(`primary`,`secondary`,`university`) | default `university` |
| `current_pensum_id` | bigint FK → pensums | nullable, null on delete |
| `academic_year` | integer | nullable |

#### `guardians`
| Columna | Tipo | Notas |
|---------|------|-------|
| `id` | bigint PK | |
| `user_id` | bigint FK → users | RESTRICT, unique |

#### `enrollments`
| Columna | Tipo | Notas |
|---------|------|-------|
| `id` | bigint PK | |
| `student_id` | bigint FK → students | RESTRICT |
| `period_id` | bigint FK → periods | RESTRICT |
| `pensum_id` | bigint FK → pensums | RESTRICT |
| `uc_disponibles` | integer | default 0 |
| `uc_inscritas` | integer | default 0 |
| `status` | enum(`draft`,`confirmed`,`approved`,`rejected`) | default `draft` |
| unique constraint | (`student_id`, `period_id`) | una inscripción por estudiante/período |

#### `enrollment_details`
| Columna | Tipo | Notas |
|---------|------|-------|
| `id` | bigint PK | |
| `enrollment_id` | bigint FK → enrollments | RESTRICT |
| `subject_id` | bigint FK → subjects | RESTRICT |
| `section_id` | bigint FK → sections | RESTRICT |
| `status` | enum(`draft`,`confirmed`,`rejected`) | default `draft` |
| unique constraint | (`enrollment_id`, `subject_id`) | una materia por inscripción |

### Relaciones clave

```
students → enrollments (hasMany)
enrollments → enrollment_details (hasMany)
enrollment_details → grades (hasMany)        ← PIVOTE CENTRAL
enrollment_details → attendances (hasMany)   ← PIVOTE CENTRAL
enrollment_details → submissions (hasMany)   ← PIVOTE CENTRAL
students → guardians (belongsTo, nullable)
guardians → students (hasMany)
```

---

## Clases por capa

### Models
- `app/Models/Student.php`
- `app/Models/Guardian.php`
- `app/Models/Enrollment.php`
- `app/Models/EnrollmentDetail.php`

### Services
- `app/Services/Enrollment/PrerequisiteValidator.php` — verifica prelaciones consultando tabla `grades` + `prerequisites` pivot
- `app/Services/Enrollment/EnrollmentCacheManager.php` — gestiona claves Redis para cupo, pensum, guardian, prelaciones
- `app/Services/Enrollment/EnrollmentService.php` — orquesta validación combinada (cupo + prelaciones + duplicados + pensum)

### Policies
- `app/Policies/EnrollmentPolicy.php` — viewAny, view, create, update, confirm, delete
- `app/Policies/EnrollmentDetailPolicy.php` — view, create, update, delete

### Form Requests
- `app/Http/Requests/Enrollment/StoreEnrollmentRequest.php`
- `app/Http/Requests/Enrollment/StoreEnrollmentDetailRequest.php`

### Resources
- `app/Http/Resources/Enrollment/EnrollmentResource.php`
- `app/Http/Resources/Enrollment/EnrollmentDetailResource.php`

### Wrappers
- `app/Http/Wrappers/Enrollment/EnrollmentWrapper.php` — extiende `Collection`, getter `getStudent()`
- `app/Http/Wrappers/Enrollment/EnrollmentDetailWrapper.php` — extiende `Collection`, getters `getSubject()` y `getSection()`

### Actions
- `app/Actions/Enrollment/CreateEnrollmentAction.php` — crea cabecera Enrollment para el período activo
- `app/Actions/Enrollment/AddEnrollmentDetailAction.php` — agrega materia/sección + decrementa caché
- `app/Actions/Enrollment/ConfirmEnrollmentAction.php` — transacción con pessimistic lock, revalida todo

### Controller
- `app/Http/Controllers/Enrollment/EnrollmentController.php` — 5 endpoints, máx 8 líneas por método

---

## Decisiones de diseño

### Caché híbrida
Redis almacena el cupo disponible con TTL de 30 segundos para reads rápidos. La base de datos es siempre la fuente de verdad — si Redis no tiene la clave, se calcula desde DB. Esto evita N+1 de cupo en la página de inscripción sin sacrificar consistencia.

### Pessimistic lock en confirmación
La confirmación de inscripción usa `lockForUpdate()` sobre las secciones dentro de `DB::transaction()`. Esto garantiza que dos estudiantes no consuman el último cupo simultáneamente. El fast path de caché es solo para la fase `draft` (UX); la consistencia real se garantiza al confirmar.

### EnrollmentDetail como pivote central
Notas, asistencia y entregas se vinculan a `enrollment_detail_id` — no a `student_id` ni `section_id` directamente. Esto preserva el historial académico por período/sección y permite auditoría completa de qué estudiante tomó qué sección en qué período.

### Rutas de inscripción
```
GET    /enrollment                          → index
POST   /enrollment                          → store
POST   /enrollment/{enrollment}/detail      → addDetail
DELETE /enrollment/{enrollment}/detail/{d}  → removeDetail
POST   /enrollment/{enrollment}/confirm     → confirm
```
