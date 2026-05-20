# Enrollment Backend Design

**Goal:** Implement real-time course enrollment system for university students and guardians (secondary/primary education), with quota control and prerequisite validation under high concurrency.

**Architecture:** Hybrid approach—fast cache-based validation (Redis) with pessimistic locking on confirmation. Two distinct user flows: university students self-enroll, guardians enroll their assigned students.

**Tech Stack:** Laravel 13, PostgreSQL, Redis, Inertia.js v3, Pest v4

---

## 1. Data Models & Relations

### 1.1 User Extension Models

**`Student` Model**
- `user_id` (FK, unique) → User (1:1)
- `guardian_id` (FK, nullable) → Guardian (N:1) — only for secondary/primary
- `educational_level` (enum: `primary`, `secondary`, `university`)
- `current_pensum_id` (FK, nullable) → Pensum (current active pensum)
- `academic_year` (int, nullable) — for tracking enrollment year
- Created via `User` → `Student` morphism or dedicated relationship

**`Guardian` Model**
- `user_id` (FK, unique) → User (1:1)
- `students()` (1:N) → Student via `guardian_id`
- Only represents guardians of secondary/primary students
- Can enroll multiple assigned students

### 1.2 Enrollment Models

**`Enrollment` Model (Header)**
- `id` (PK)
- `student_id` (FK) → Student (1:N)
- `period_id` (FK) → Period
- `pensum_id` (FK) → Pensum
- `uc_disponibles` (int) — available credits from pensum
- `uc_inscritas` (int) — credits currently enrolled (calculated)
- `status` (enum: `draft`, `confirmed`, `approved`, `rejected`)
- `created_at`, `updated_at`

Relations:
```php
public function student() // belongs to Student
public function details() // has many EnrollmentDetail
public function period() // belongs to Period
public function pensum() // belongs to Pensum
```

**`EnrollmentDetail` Model (Line Items)**
- `id` (PK)
- `enrollment_id` (FK) → Enrollment (1:N)
- `subject_id` (FK) → Subject
- `section_id` (FK) → Section
- `status` (enum: `draft`, `confirmed`, `rejected`)
- `created_at`, `updated_at`

Relations:
```php
public function enrollment() // belongs to Enrollment
public function subject() // belongs to Subject
public function section() // belongs to Section
```

### 1.3 Relational Diagram

```
User (1) ↔ (1) Student
  ├─ guardian_id (FK, nullable) → Guardian
  └─ enrollments (1:N)

User (1) ↔ (1) Guardian
  └─ students (1:N)

Enrollment (1) ← (N) EnrollmentDetail
  ├─ subject_id
  └─ section_id

Section → Classroom (theory + lab)
Section → Schedule (multiple per section)
Subject → Pensum (M:N via pensum_subjects)
Subject → Prerequisites (self-referential M:M)
```

---

## 2. Caching Strategy (Redis)

### 2.1 Cache Keys & Values

| Key | Type | Value | TTL | Purpose |
|-----|------|-------|-----|---------|
| `enrollments:section:{section_id}:available` | int | Cupos disponibles | 30s | Validación rápida en select |
| `prerequisites:subject:{subject_id}` | set | Set de subject_ids prereq | 3600s | Check prereqs cumplidos |
| `pensum:student:{student_id}:active` | json | Pensum activo actual | 1800s | Pensum vigente del estudiante |
| `guardian:student:{student_id}` | json | Guardian data (si existe) | 1800s | Asignación guardian ↔ student |

### 2.2 Cache Invalidation

- **On create/reject EnrollmentDetail:** Increment `enrollments:section:{id}:available`
- **On confirm Enrollment:** Final DB lock + atomic transaction (no pre-invalidation)
- **On admin actions:** Clear relevant keys (e.g., update section capacity → purge section key)
- **Staleness tolerance:** Up to 30s for quota (acceptable in high-concurrency scenario)

---

## 3. API Endpoints (Inertia Routes)

### 3.1 Enrollment Management

**GET `/enrollment`** — List student's enrollments (or guardian's students' enrollments)
- Auth: Student views own | Guardian views assigned students
- Returns: Collection of Enrollment with EnrollmentDetail counts
- Inertia page: `enrollment/Index`

**POST `/enrollment`** — Create new Enrollment header for current period
- Auth: Student for self, Guardian for assigned student
- Body: `{ student_id? }` (optional, required if guardian)
- Validates:
  - Student exists and guardian authorized (if guardian acting)
  - Period is open for enrollment
  - No existing draft Enrollment for this period/student
- Response: Enrollment + empty details, status 201
- Side effect: Initialize `enrollments:section:*:available` cache entries for all sections

**POST `/enrollment/{enrollment_id}/detail`** — Add course to enrollment
- Auth: User (student/guardian) who owns enrollment
- Body: `{ subject_id, section_id }`
- Validates (cached):
  - Enrollment status is `draft` (can't add to confirmed/rejected)
  - Section has quota available (`redis.get()`)
  - Student not already enrolled in same subject (in this Enrollment)
  - Prerequisites met (`redis.sismember()`)
  - Pensum allows this subject (`redis.get()`)
- On success:
  - Create EnrollmentDetail `draft`
  - Decrement `enrollments:section:{section_id}:available` in Redis
  - Return EnrollmentDetail + updated Enrollment uc_inscritas
- On fail: HTTP 422 with validation error message

**DELETE `/enrollment/{enrollment_id}/detail/{detail_id}`** — Remove course
- Auth: User who owns enrollment
- Validates:
  - Detail belongs to enrollment
  - Enrollment status is `draft` (not confirmed/approved/rejected)
- On success:
  - Update EnrollmentDetail status → `rejected`
  - Increment `enrollments:section:{section_id}:available` in Redis
  - Return updated Enrollment

**POST `/enrollment/{enrollment_id}/confirm`** — Finalize enrollment (move draft → confirmed)
- Auth: User who owns enrollment
- Validates:
  - Enrollment is `draft` status
  - Has at least 1 `draft` detail (must enroll in at least one course)
- Process:
  1. Fetch all EnrollmentDetail with status `draft`
  2. **Acquire pessimistic lock:** `SELECT id FROM sections WHERE id IN (...) FOR UPDATE`
  3. **Re-validate in DB** (source of truth):
     - Check section quota (row-level lock prevents race)
     - Check prerequisites against `grades` table (student's passed courses)
     - Check pensum restrictions
  4. If any detail fails: **rollback**, return 422 with errors
  5. If all pass: **Atomic transaction:**
     - Update all EnrollmentDetail status → `confirmed`
     - Update Enrollment status → `confirmed`
     - Update Enrollment `uc_inscritas` = sum of enrolled subjects' credits
     - Decrement section capacities in `sections` table
  6. **Release locks**, commit
  7. Return Enrollment with confirmed details

---

## 4. Validation Strategy (Stratified)

### 4.1 Fast Path (On Select/Unselect — Redis)

```php
// Pseudo-code for POST /enrollment/{id}/detail
validate_fast_cache:
  1. cupo = redis.get("enrollments:section:{section_id}:available")
     if cupo <= 0 → throw QuotaExhaustedException
  
  2. pensum = redis.get("pensum:student:{student_id}:active")
     if subject not in pensum.subjects → throw SubjectNotInPensumException
  
  3. prereqs = redis.smembers("prerequisites:subject:{subject_id}")
     student_completed = grades where student_id & grade >= pass_threshold
     if !prereqs.all in student_completed → throw PrerequisiteException
  
  4. create EnrollmentDetail, decrement redis counter
  → return success
```

**Expected latency:** <100ms (cache hit)

### 4.2 Slow Path (On Confirm — Database Lock)

```php
// Pseudo-code for POST /enrollment/{id}/confirm
validate_db_locked:
  1. BEGIN TRANSACTION
  
  2. SELECT id FROM sections WHERE id IN (...course sections...) 
     FOR UPDATE (pessimistic lock)
  
  3. For each detail:
       a. section = locked row
       b. if section.capacity - enrolled_count < 1 → fail this detail
       c. select grades.* where student_id & course_id & grade >= passing
          if missing prerequisites → fail this detail
       d. check pensum_subjects table for this subject
          if not in pensum → fail this detail
  
  4. If any detail failed → ROLLBACK, return 422
  
  5. If all passed → atomic updates:
       - update enrollment_details set status='confirmed'
       - update enrollments set status='confirmed', uc_inscritas=sum
       - update sections set capacity=capacity-enrolled_count
  
  6. COMMIT
```

**Expected latency:** <500ms (lock wait + re-validation)

### 4.3 Authorization (Policies)

**`EnrollmentPolicy`:**
- `viewAny(User $user)` — Student sees own enrollments, Guardian sees own students' enrollments
- `view(User $user, Enrollment $enrollment)` — 
  - True if `$user->student->id === $enrollment->student_id`
  - True if `$user->guardian->students()->where('id', $enrollment->student_id)->exists()`
  - False otherwise
- `create(User $user)` — True if Student or Guardian with assigned students
- `update(User $user, Enrollment $enrollment)` — True if draft status + same auth as view
- `confirm(User $user, Enrollment $enrollment)` — Same as update

**`EnrollmentDetailPolicy`:**
- Delegate to parent Enrollment: `$this->authorize('update', $detail->enrollment)`

---

## 5. User Flows

### 5.1 University Student (Self-Enrollment)

```
1. GET /enrollment
   → Shows student's current/past enrollments
   
2. [Create new enrollment] Click "Nueva Inscripción"
   → POST /enrollment { student_id: omitted }
   → Creates Enrollment draft for current period/pensum
   → Redirects to enrollment detail view
   
3. [Select courses] Search + click subject
   → POST /enrollment/{id}/detail { subject_id, section_id }
   → Fast validation (redis) → Create EnrollmentDetail draft
   → Frontend updates: uc_inscritas += subject.credits
   
4. [Unselect] Click X on selected course
   → DELETE /enrollment/{id}/detail/{detail_id}
   → EnrollmentDetail rejected/deleted
   → Frontend updates: uc_inscritas -= subject.credits
   
5. [Confirm] Click "Confirmar Inscripción"
   → POST /enrollment/{id}/confirm
   → Slow validation (DB lock) → All details confirmed
   → Enrollment status → confirmed
   → Success toast
```

### 5.2 Secondary/Primary Student (Guardian-Enrollment)

```
1. Guardian GET /enrollment
   → Shows dropdown: [Select student] or list of students
   
2. [Select student] Guardian picks one of their assigned students
   → Filters UI to show that student's enrollments
   
3. [Create for student] Click "Nueva Inscripción"
   → POST /enrollment { student_id: <selected_student_id> }
   → Creates Enrollment draft for selected student
   
4-5. [Select/unselect] Same as university (steps 3-4)
   
6. [Confirm] Guardian confirms
   → POST /enrollment/{id}/confirm (for selected student)
   → All validations use selected student's data
```

---

## 6. Error Handling

| Scenario | HTTP | Response | Frontend |
|----------|------|----------|----------|
| Quota exhausted | 422 | `{ message: "No hay cupos disponibles en esta sección" }` | Toast error, disable button |
| Prerequisites not met | 422 | `{ message: "Prerequisitos no cumplidos: ALG-302" }` | Show missing prereq |
| Subject not in pensum | 422 | `{ message: "Materia no está en el pensum" }` | Disable option |
| Enrollment already confirmed | 422 | `{ message: "La inscripción ya fue confirmada" }` | Disable actions |
| Guardian not authorized | 403 | `Unauthorized` | Redirect to login |
| Period closed | 422 | `{ message: "Período de inscripción cerrado" }` | Disable all |
| Lock timeout (DB busy) | 503 | `{ message: "Servidor ocupado, reintenta en 30s" }` | Retry button |

---

## 7. Testing Strategy

### 7.1 Feature Tests (Pest)

- **Enrollment creation:** Student creates enrollment for self, guardian creates for student
- **Detail add (fast path):** Quota validation, prerequisites, pensum check
- **Detail remove:** Quota refund, uc_inscritas update
- **Confirm (slow path):** Lock + re-validation, atomic transaction, quota decrement
- **Authorization:** Student can't see other student's enrollment, guardian can't see students they don't represent
- **Concurrency:** Multiple students selecting same section simultaneously (quota consistency)
- **Error cases:** Exhausted quota, failed prerequisites, closed period

### 7.2 Unit Tests

- **`PrerequisiteValidator`:** Check student's grades against subject requirements
- **`EnrollmentService`:** Quota calculation, uc_inscritas aggregation
- **Cache invalidation:** Verify keys are set/cleared correctly

### 7.3 Load Test (Optional)

- 1000 concurrent students selecting same section
- Verify quota never goes negative
- Verify no double-bookings

---

## 8. Migrations Checklist

- [ ] Create `students` table (user_id, guardian_id, educational_level, current_pensum_id)
- [ ] Create `guardians` table (user_id)
- [ ] Add FK `guardian_id` to `students` (nullable)
- [ ] Create `enrollments` table (student_id, period_id, pensum_id, uc_disponibles, uc_inscritas, status)
- [ ] Create `enrollment_details` table (enrollment_id, subject_id, section_id, status)
- [ ] Add index on `enrollments.student_id`, `enrollment_details.enrollment_id`
- [ ] Add index on `enrollment_details.section_id` (for quota checks)

---

## 9. Implementation Order

1. Models + Migrations (Student, Guardian, Enrollment, EnrollmentDetail)
2. Policies (authorization)
3. Services (PrerequisiteValidator, EnrollmentService)
4. Cache layer (Redis keys, seeders)
5. Controllers + Endpoints (GET, POST detail, DELETE detail)
6. POST /confirm endpoint (lock + transaction logic)
7. Tests (feature + unit)
8. Integration with frontend (Inertia props, form composables)

---

## 10. Notes

- **Quota consistency:** Redis cache is ±30s stale; DB lock on confirm is source of truth
- **Guardian flow:** Secondary/primary students always enrolled by guardian; self-enrollment disabled for under-age users
- **Enrollment reopen:** If confirm fails, all details stay `draft` and can retry or select different sections
- **Soft deletes:** Consider soft-deleting rejected EnrollmentDetails for audit trail
- **Pensum versioning:** Enrollment stores reference to pensum at time of enrollment (not current active)
