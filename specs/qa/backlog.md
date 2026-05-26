# QA Backlog — CACAO

---

## HLZ-01 — S03 Dirección: countries y states vacíos en DB de desarrollo

**Fecha:** 2026-05-25  
**Dominio:** security / catalogs  
**UC relacionado:** UC-04 en specs/qa/security/user-edit.md  
**Descripción:**
La tabla `countries` tiene 0 filas y `states` tiene 0 filas en el DB de desarrollo actual. El seeder `GeographicSeeder` existe y el archivo `database/seeders/data/countries.json` existe, pero **`database/seeders/data/states.json` está ausente**. Esto causa que el `GeographicSeeder` falle silenciosamente o no haya sido ejecutado.

Consecuencia en UI: los selects de País y Estado en la sección S03 del formulario de edición quedan completamente vacíos. No hay error JS — el array `catalogData.countries` llega como `[]` y `catalogData.states` llega como `[]`. Técnicamente funcional (puede guardarse con `country_id: null`) pero inutilizable en la práctica.

**Evidencia:**
- `vendor/bin/sail artisan tinker`: `countries: 0`, `states: 0`
- `database/seeders/data/states.json`: MISSING
- `database/seeders/data/countries.json`: EXISTS

**Acción sugerida:**
1. Crear/restaurar `database/seeders/data/states.json` con los estados de Venezuela (y opcionalmente otros países)
2. Ejecutar `vendor/bin/sail artisan db:seed --class=Database\\Seeders\\Catalogs\\GeographicSeeder`
3. Agregar un test en `UserEditUseCaseTest` que verifique que `catalogData.countries` no está vacío cuando existe al menos un país activo

**Estado:** resuelto  
**Fecha de resolución:** 2026-05-25  
**Feature:** geographic-seeder-fix  
**Verificación:** `countries: 187`, `states: 24` confirmados por tinker post-fix.

---

## HLZ-02 — S16 Representante: User 198 tiene rol guardian pero sin fila en `guardians`

**Fecha:** 2026-05-25  
**Dominio:** security / guardians  
**UC relacionado:** UC-11, UC-12 en specs/qa/security/user-edit.md  
**Descripción:**
El usuario 198 ("Nuevo usuario", email marcos@begento.net) tiene el rol Spatie "Representante" (mapeado a `guardian` en `UserEditResource`), pero **no existe fila en la tabla `guardians` con `user_id = 198`**. Esto significa que:

1. `props.guardian` llega como `undefined` en Inertia (no como `{ id, profile }`)
2. El tab "Representante" (S16) es visible en el formulario (porque el rol está asignado)
3. Al intentar guardar S16, `saveGuardianProfile()` hace early return (`if (!props.guardian) return`) — silencioso, sin feedback al usuario
4. La sección S16 nunca puede marcarse como `complete`

No causa error JS ni crash, pero la experiencia de usuario es confusa: el tab aparece, el usuario puede editar los campos, guarda, y nada pasa.

**Evidencia:**
- Tinker: `Guardian: NULL` para user_id=198
- `props.guardian` → `undefined` en `UserEditProps`
- `saveGuardianProfile()`: `if (!props.guardian) return` → early return silencioso

**Acción sugerida:**
1. Al asignar rol "Representante" a un usuario en `CreateUserAction` o `UpdateUserAction`, crear automáticamente la fila en `guardians` si no existe (similar a cómo se crea `Student` al asignar "Estudiante")
2. En el frontend, si `props.guardian` es `undefined` y el rol es `guardian`, mostrar mensaje en S16: "El perfil de representante se creará al guardar" — o deshabilitar la sección con texto explicativo
3. Cubrir con test: "asignar rol Representante crea fila en guardians"

**Estado:** resuelto  
**Fecha de resolución:** 2026-05-25  
**Feature:** role-sub-record-integrity  
**Verificación:** `Guardian::where("user_id", 198)->first()` → `id=21` confirmado por tinker post-fix.

---

## HLZ-03 — `states.json` ausente rompe `GeographicSeeder` en entornos frescos

**Fecha:** 2026-05-25  
**Dominio:** infrastructure / seeders  
**UC relacionado:** UC-04 en specs/qa/security/user-edit.md  
**Descripción:**
El archivo `database/seeders/data/states.json` no existe en el repositorio (o no fue commiteado). El `GeographicSeeder::seedStates()` lo carga con `file_get_contents()`. Su ausencia hace que el seeder falle con un `RuntimeException` o `false` en entornos frescos, lo que impide que `UserEditUseCaseTest` (que hace `$this->seed(GeographicSeeder::class)`) funcione correctamente en CI sin datos previos.

**Evidencia:**
- `file_exists(database_path("seeders/data/states.json"))` → `false`
- Los tests de S3 en `UserEditUseCaseTest` que usan `State::where('country_id', $venezuela->id)->first()` podrían devolver `null` en entornos frescos si el seeder falla

**Acción sugerida:**
1. Agregar `states.json` al repositorio (datos de Venezuela al menos)
2. O ajustar `GeographicSeeder` para ser resiliente cuando `states.json` no existe
3. Verificar que los tests de S3 en `UserEditUseCaseTest` pasen en un entorno con `php artisan migrate:fresh --seed`

**Estado:** resuelto  
**Fecha de resolución:** 2026-05-25  
**Feature:** geographic-seeder-fix  
**Verificación:** `states: 24` confirmados; tests de Feature/Security/ (163 tests) pasan en verde.

---

## HLZ-04 — `staff_profiles` usa `professor_id` como FK, no `user_id`

**Fecha:** 2026-05-25  
**Dominio:** security / staff  
**UC relacionado:** UC-13 en specs/qa/security/user-edit.md  
**Descripción:**
Hallazgo de análisis: la tabla `staff_profiles` tiene FK `professor_id`, no `user_id`. El modelo `User` no tiene relación `staffProfile` — la relación correcta es `User → Professor → StaffProfile`. El código de `useUserEditForm.ts` usa correctamente `props.professor.id` en la llamada a `upsertStaffProfile`. Sin embargo, una búsqueda directa `StaffProfile::where('user_id', ...)` fallaría (como se confirmó durante la auditoría).

No afecta el flujo actual (el código usa el camino correcto), pero documenta una confusión potencial para futuros desarrolladores.

**Evidencia:**
- Columns de `staff_profiles`: `id, professor_id, employee_code, ...` — sin `user_id`
- Tinker: `StaffProfile::where('user_id', 198)` → `SQLSTATE[42703]: Undefined column`

**Acción sugerida:** Agregar un comentario PHPDoc en el modelo `StaffProfile` aclarando que la FK es `professor_id` y no `user_id`. No requiere cambio de código.

**Estado:** resuelto  
**Fecha de resolución:** 2026-05-26  
**Feature:** user-edit-professor-s17-fix  
**Verificación:** PHPDoc de clase agregado en `StaffProfile.php` documentando que la FK es `professor_id` (vía `professors.user_id`) y que `StaffProfile::where('user_id', ...)` falla.

---

## HLZ-05 — S17 Profesor: payload envía strings en lugar de IDs — siempre 422

**Fecha:** 2026-05-25  
**Dominio:** security / professors  
**Rol afectado:** `professor`  
**UC relacionado:** UC-13  
**Descripción:**
`saveStaffProfile()` en `useUserEditForm.ts` envía los campos de catálogo con el **nombre** del valor seleccionado, no con su ID numérico. El backend (`StoreStaffProfileRequest`) exige los siguientes campos como integer FK **requeridos**:

| Campo frontend enviado | Campo esperado por backend | ¿Requerido? |
|---|---|---|
| `contract_type` | `contract_type_id` | REQUIRED |
| `dedication_type` | `dedication_type_id` | REQUIRED |
| `employment_status` | `employment_status_id` | REQUIRED |
| `coordinated_department` | `coordinated_department_id` | required_if:is_coordinator |

Adicionalmente, `hire_date` es requerido en backend pero el componente `UserFormS17ProfessorProfile.vue` lo tiene marcado solo como `required` en HTML (no en el handler).

**Consecuencia:** Guardar S17 **siempre devuelve 422** con errores de validación en `contract_type_id`, `dedication_type_id` y `employment_status_id`. Sección nunca puede completarse.

**Evidencia:**
- `StoreStaffProfileRequest::rules()`: `'contract_type_id' => ['required', 'integer', 'exists:contract_types,id']`
- `saveStaffProfile()`: `contract_type: formData.contract ?? null` — clave sin `_id`, valor string
- `UserFormS17ProfessorProfile.vue`: usa `UF_CONTRACT` (array de strings), no IDs de DB

**Acción sugerida:**
1. Agregar `contract_type_id`, `dedication_type_id`, `employment_status_id`, `coordinated_department_id` a `UserFormCatalogData` (igual que se hizo con `languages`, `languageLevels`, `benefits`)
2. Actualizar `UserFormS17ProfessorProfile.vue` para usar `catalogData.contractTypes`, etc.
3. Actualizar `saveStaffProfile()` en `useUserEditForm.ts` para enviar IDs
4. Test: guardar S17 con datos válidos → 200, no 422

**Estado:** resuelto  
**Fecha de resolución:** 2026-05-25  
**Feature:** user-edit-catalog-ids  
**Verificación:** `saveStaffProfile()` ahora envía `contract_type_id`, `dedication_type_id`, `employment_status_id` (integer IDs). `catalogData` incluye `contractTypes`, `dedicationTypes`, `employmentStatuses` desde backend. `StoreStaffProfileRequest` los acepta correctamente.

---

## HLZ-06 — `hasAnyRole(['Administrador','Coordinador'])` en FormRequests no corresponde a rol real 'Admin'

**Fecha:** 2026-05-25  
**Dominio:** security / authorization  
**Roles afectados:** `admin` (al editar formularios de student/guardian)  
**UC relacionados:** UC-09, UC-10, UC-11  
**Descripción:**
Tres FormRequests usan `hasAnyRole(['Administrador', 'Coordinador'])` en lugar de `Gate::authorize()` para autorizar acceso de administradores:

- `StoreStudentLanguageRequest` (S10)
- `StoreStudentBenefitRequest` (S13)
- `StoreGuardianProfileRequest` (S16)

El rol de administrador en producción se llama **`Admin`** (no `Administrador`). El rol `Administrador` **no existe en la DB de producción**. Esto significa:

- Un usuario con rol `Admin` que edite un estudiante obtiene **403** al intentar guardar S10 (idiomas) o S13 (beneficios)
- Un usuario con rol `Admin` que edite un representante obtiene **403** al intentar guardar S16 (perfil del representante)

`Gate::before(fn($user) => $user->hasRole('Admin') ? true : null)` en `AppServiceProvider` **solo aplica a llamadas `Gate::authorize()`**, no a `hasAnyRole()` directo.

**Por qué los tests no detectan esto:** `adminForEditUseCase()` en `UserEditUseCaseTest` asigna `Admin` Y `Administrador` al admin de prueba:
```php
$user->assignRole('Admin');
$user->assignRole('Administrador');  // ← bypasses the bug
```

**Evidencia:**
- `StoreStudentLanguageRequest::authorize()`: `return $this->user()->hasAnyRole(['Administrador', 'Coordinador']);`
- `AppServiceProvider`: `Gate::before(fn (User $user) => $user->hasRole('Admin') ? true : null);`
- DB producción: `Administrador` role → 0 usuarios

**Acción sugerida:**
1. Reemplazar `hasAnyRole()` por `Gate::authorize()` en los tres FormRequests afectados, igual que el patrón de `StoreDemographicProfileRequest` y otros
2. Corregir `adminForEditUseCase()` en tests para que solo asigne el rol `Admin` (el dual-role oculta el bug)
3. Agregar test específico: "Admin con solo rol 'Admin' puede guardar S10, S13, S16"

**Estado:** resuelto  
**Fecha de resolución:** 2026-05-25  
**Feature:** user-edit-auth-fix  
**Verificación:** Los tres FormRequests usan `Gate::authorize()`. `StoreStudentLanguageRequest::authorize()` → `Gate::authorize('create', StudentLanguage::class)`. `StoreStudentBenefitRequest::authorize()` → `Gate::authorize('create', StudentBenefit::class)`. `StoreGuardianProfileRequest::authorize()` → `Gate::authorize('update', $guardian)`.

---

## HLZ-07 — Usuarios con rol asignado pero sin sub-registro vinculado (profesor y estudiante)

**Fecha:** 2026-05-25  
**Dominio:** security / data integrity  
**Roles afectados:** `professor`, `student`, `guardian` (ya documentado en HLZ-02)  
**Descripción:**
Se detectaron en la DB de desarrollo:

| Rol | `user_id` sin sub-registro | Consecuencia |
|---|---|---|
| `professor` | user_id=2 (1 de 13 profesores) | `props.professor` → `undefined` → S17 early-return silencioso |
| `student` | user_id=3 (1 de 141 estudiantes) | `props.student` → `undefined` → secciones 8–15 early-return silenciosas |
| `guardian` | user_id=198 (ya en HLZ-02) | `props.guardian` → `undefined` → S16 early-return silencioso |

En los tres casos: el tab del rol aparece visible (UF_TABS lo incluye), el usuario puede editar campos, al guardar no ocurre nada y no hay feedback de error.

**Diferencia con HLZ-02:** HLZ-02 documentó el caso de guardian. Este ítem extiende el mismo patrón a professor y student.

**Acción sugerida:** La misma que HLZ-02: al asignar rol, crear automáticamente el sub-registro (`Professor`, `Student`, `Guardian`) si no existe. Considerar constraint a nivel de migración o trigger en `CreateUserAction`/`UpdateUserAction`.

**Estado:** resuelto  
**Fecha de resolución:** 2026-05-25  
**Feature:** role-sub-record-integrity  
**Verificación:** `Professor::where("user_id", 2)->first()` → `id=13`. `Student::where("user_id", 3)->first()` → `id=141`. `Guardian::where("user_id", 198)->first()` → `id=21`. Todos los sub-registros huérfanos reparados por `users:fix-orphan-records` y ensureSubRecord() en Actions.

---

## HLZ-08 — S04/S09/S16/S17 usan strings de catálogo local en lugar de IDs de DB

**Fecha:** 2026-05-25  
**Dominio:** security / catalog data  
**Roles afectados:** todos (`admin`, `student`, `professor`, `guardian`)  
**Descripción:**
Múltiples secciones del formulario usan arrays de strings hardcodeados en `userFormCatalogs.ts` (p.ej. `UF_STATES_VE`, `UF_RELIGIONS`, `UF_INSTITUTION_TYPES`, `UF_MARITAL`, `UF_CONTRACT`) y los envían como strings al backend, que espera IDs de FK de la DB.

**Secciones y campos afectados:**

| Sección | Componente | Campos string | Campos esperados por backend |
|---|---|---|---|
| S04 Demográfico | S04Demographic | `birth_state` (string), `birth_country` (string `"ve"`), `religion` (string), `native_language` (string) | `birth_state_id`, `birth_country_id`, `religion_id`, `native_language_id` (todos FK integers) |
| S09 Antecedentes | S09PrevEducation | `institution_type` (string), `transfer_reason` (string), `digital_level` (string), `mother_edu`/`father_edu` (strings) | `institution_type_id`, `transfer_reason_id`, `digital_level_id`, `mother_education_level_id`, `father_education_level_id` |
| S16 Representante | S16GuardianProfile | `marital_status` (string), `education_level` (string) | `marital_status_id`, `education_level_id` |
| S17 Profesor | S17ProfessorProfile | `contract_type`, `dedication_type`, `employment_status`, `coordinated_department` | `contract_type_id`, `dedication_type_id`, `employment_status_id`, `coordinated_department_id` |

**Comportamiento actual:**
- S04: backend acepta valores `null` para `birth_state_id` (nullable), así que el save no falla con 422, pero los dropdowns de estado/país/religión/idioma nativo nunca persisten su selección
- S09: igual — todos los campos son nullable, save pasa pero valores de catálogo se pierden
- S16: igual — nullable, save pasa pero marital/education no persisten
- S17: campos requeridos (`contract_type_id`, `dedication_type_id`, `employment_status_id`) → **siempre 422** (ver HLZ-05)

**Nota:** S10 (idiomas) y S13 (beneficios) ya fueron migrados a IDs en un commit reciente. S03 (dirección) también usa IDs correctamente. S04/S09/S16/S17 aún no han sido migrados.

**Acción sugerida:** Migrar S04, S09, S16, S17 al mismo patrón que S10/S13/S03: pasar los catálogos como `catalogData` con IDs desde el backend, usar `_id` en el form state, y enviar IDs al guardar.

**Estado:** resuelto  
**Fecha de resolución:** 2026-05-25  
**Feature:** user-edit-catalog-ids  
**Verificación:** `useUserEditForm.ts` envía `birth_state_id`, `birth_country_id`, `religion_id`, `native_language_id` (S04); `institution_type_id`, `transfer_reason_id`, `digital_level_id`, `mother_education_level_id`, `father_education_level_id` (S09); `marital_status_id`, `education_level_id` (S16); `contract_type_id`, `dedication_type_id`, `employment_status_id`, `coordinated_department_id` (S17). `catalogData` en `UserController::edit()` incluye todos los catálogos necesarios. `UserFormCatalogData` interface actualizada en `userEdit.ts`.

---

## HLZ-09 — `/academic/students` botones Ver y Editar sin funcionalidad

**Fecha:** 2026-05-25  
**Dominio:** academic / students  
**UC relacionado:** nuevo (sin UC definido aún)  
**Descripción:**
Los botones **Ver perfil** (ojo) y **Editar** (lápiz) en la tabla de `http://localhost:8000/academic/students` son **placeholders sin wiring**. Están renderizados en los cuatro layouts de tabla (Todos, Primaria, Bachillerato, Universitario) pero no tienen `@click`, `:href` ni ningún comportamiento asociado.

**Análisis de causas:**

| Elemento | Estado actual |
|---|---|
| Ruta `GET academic/students/{student}` | **No existe** |
| Ruta `GET academic/students/{student}/edit` | **No existe** |
| `StudentController::show()` | **No existe** |
| `StudentController::edit()` | **No existe** |
| `resources/js/pages/admin/Students/Show.vue` | **No existe** |
| `resources/js/pages/admin/Students/Edit.vue` | **No existe** |
| Botones en `Index.vue` | `<Button variant="ghost" ... icon="eye" />` sin `@click` ni `:href` |

**Esto fue intencional en el sprint `admin-students`:** `specs/admin-students/requirements.md` RF-07 dice explícitamente: *"Three buttons per row (Ver perfil, Editar, Más) rendered but non-functional — wired when subpages exist."*

**Qué existe de forma relacionada:**
- `GET /security/users/{user}/edit` → `security.users.edit` → `resources/js/pages/security/Users/Edit.vue` → formulario de 17 secciones completo (perfil personal, demográfico, académico, socioeconómico, etc.)
- El formulario `/security/users/{user}/edit` YA contiene todos los datos de un estudiante (S01–S15 cubren nombre, documentos, dirección, demográfico, antecedentes, idiomas, familias, beneficios, etc.)

**Pregunta de diseño crítica (requiere decisión del humano):**

¿Qué debe hacer el botón **Editar** desde `/academic/students`?

**Opción A — Redirigir a `/security/users/{user->user_id}/edit`:** El formulario de seguridad ya existe y es completo. La vista académica simplemente navegaría ahí. Requiere solo wiring frontend (sin backend nuevo).

**Opción B — Vista académica específica `admin/Students/Edit.vue`:** Una vista diferente enfocada en datos académicos (pensum, año, inscripción) separada del perfil personal. Requiere nuevo controlador + ruta + página Vue.

**Y para el botón Ver:**

¿Qué debe mostrar `Show.vue`?
- Solo datos académicos resumidos (carrera, año, inscripciones, notas)
- O un perfil completo del estudiante (datos personales + académicos + historial)

**Evidencia:**
- `Index.vue` línea 530: `<Button variant="ghost" size="sm" icon-only icon="eye" :aria-label="..." />`
- `Index.vue` línea 531: `<Button variant="ghost" size="sm" icon-only icon="edit" :aria-label="..." />`
- `vendor/bin/sail artisan route:list --path=academic/students` → solo 1 ruta: `GET academic/students`

**Acción sugerida:** Definir con el humano el diseño (Opción A o B para Editar; alcance de Show), luego crear feature en el arnés. Ver propuesta de UCs en `specs/qa/academic/student-show-edit.md` una vez aprobados por el humano.

**Implementación aprobada (2026-05-25):**

| Elemento | Decisión |
|---|---|
| Botón Editar | Opción A: wiring en `Index.vue` hacia `security.users.edit` pasando `s.user_id` |
| Botón Ver | Nivel 2: perfil académico completo en nueva página `admin/Students/Show.vue` |
| `StudentListResource` | Agregar campo `user_id` (necesario para el `:href` del botón Editar) |
| Nueva ruta | `GET /academic/students/{student}` → `academic.students.show` |
| Nuevo método | `Academic\StudentController::show()` carga: user, pensum→carrera, enrollments con detalles, grades, sections con schedules, guardians |
| Nuevo resource | `Academic\StudentShowResource` — todos los campos del Nivel 2 |
| Nueva página | `resources/js/pages/admin/Students/Show.vue` — secciones verticales: identidad, carrera/pensum, inscripción activa, representantes, historial, notas, secciones con horario |

**Feature:** `student-academic-show`
**UCs documentados en:** `specs/qa/academic/student-show.md`

**Estado:** pendiente  
**Prioridad:** MEDIA (funcionalidad esperada por usuarios admin)

---

## HLZ-10 — S05 Salud: round-trip roto — el reload no pre-llena blood_type, disability_type ni insurance_type; el save DESTRUYE los IDs existentes

**Fecha:** 2026-05-25  
**Dominio:** security / health  
**Rol afectado:** todos (admin, student, professor, guardian) — confirmado para admin (2026-05-26) y guardian (2026-05-26, user 137: blood_type_id=5, insurance_type_id=4 en DB → vacíos en reload)  
**UC relacionado:** UC-06 en specs/qa/security/user-edit.md  

**Descripción:**
`UserController::edit()` carga `'healthProfile'` sin relaciones anidadas. `HealthProfileResource` usa `whenLoaded('bloodType', ...)` / `whenLoaded('disabilityType', ...)` / `whenLoaded('insuranceType', ...)` — que devuelven `MissingValue` y son eliminados del output por `resolve()`. El resultado: los campos `blood_type`, `disability_type` e `insurance_type` están **ausentes** del prop de Inertia.

**Nota (auditoría 2026-05-26):** `HealthProfileResource` SÍ devuelve `blood_type_id`, `disability_type_id` e `insurance_type_id` como campos planos en el resource (siempre presentes). Sin embargo, `buildInitialFormData()` no lee estos campos planos — lee `h.blood_type?.name` (objeto anidado ausente) → `undefined`. La consecuencia es más grave de lo inicialmente documentado:

`buildInitialFormData()` lee:
- `h.blood_type?.name` → `undefined` (campo anidado ausente — el flat `h.blood_type_id` sí existe pero no es leído)
- `h.disability_type?.name` → `undefined` (ídem)
- `h.insurance_type?.name` → `undefined` (ídem)

Los dropdowns aparecen **vacíos al recargar**. Y al guardar, `saveHealth()` envía `blood_type: null`, `disability_type: null`, `insurance_type: null` — el backend interpreta esto como limpiar los IDs, y los valores existentes en DB quedan en `NULL`. **El save de S05 destruye activamente los valores previos de `blood_type_id`, `disability_type_id` e `insurance_type_id`.**

Adicionalmente, `saveHealth()` envía las claves sin `_id` sufijo (`blood_type`, `disability_type`, `insurance_type`), pero `StoreHealthProfileRequest` espera `blood_type_id`, `disability_type_id`, `insurance_type_id`. Las claves incorrectas son ignoradas completamente por la validación, pero el resultado neto es el mismo: los valores se pierden.

**Evidencia:**
- `UserController::edit()` línea 132: `'healthProfile'` — sin nested relations
- `HealthProfileResource`: `whenLoaded('bloodType', ...)` → MissingValue → ausente en resolve()
- `buildInitialFormData()` línea 464: `d.bloodType = h.blood_type?.name` → `undefined`
- `saveHealth()` línea 171: `blood_type: formData.bloodType ?? null` — clave string, no `blood_type_id`
- `StoreHealthProfileRequest::rules()`: `'blood_type_id' => ['nullable', 'integer', 'exists:blood_types,id']`
- Confirmado con tinker: resource resolve() sin nested load → keys `blood_type`, `disability_type`, `insurance_type` AUSENTES

**Impacto:**
- Al recargar S05: blood_type, disability_type, insurance_type siempre vacíos aunque estén en DB
- Al guardar S05: blood_type_id, disability_type_id, insurance_type_id **nunca se actualizan** via este formulario

**Acción sugerida:**
1. En `UserController::edit()`, agregar `'healthProfile.bloodType'`, `'healthProfile.disabilityType'`, `'healthProfile.insuranceType'` al `with()`
2. En `buildInitialFormData()`: leer también el `_id` numérico: `d.bloodTypeId = h.blood_type_id ?? undefined`
3. En `saveHealth()`: enviar `blood_type_id: formData.bloodTypeId ?? null`, `disability_type_id: formData.disabilityTypeId ?? null`, `insurance_type_id: formData.insuranceTypeId ?? null`
4. En `UserFormS05Health.vue`: cambiar los selects para usar `catalogData` con IDs de DB, igual que S04/S09
5. Agregar `bloodTypes`, `disabilityTypes`, `insuranceTypes` a `catalogData` en `UserController::edit()`

**Estado:** resuelto  
**Fecha de resolución:** 2026-05-26  
**Feature:** user-edit-health-fix  
**Verificación:** `buildInitialFormData()` lee `blood_type_id`, `disability_type_id`, `insurance_type_id` desde el resource plano. `saveHealth()` envía con sufijo `_id`. `catalogData` incluye `bloodTypes`, `disabilityTypes`, `insuranceTypes`. 12 acceptance tests pasan (RF-01 a RF-06). Ver UC-32, UC-33, UC-34 en specs/qa/security/user-edit.md.

---

## HLZ-11 — S11 Familia: round-trip roto — el reload no pre-llena marital_status, living_arrangement ni household_head_type

**Fecha:** 2026-05-25  
**Dominio:** security / family  
**Rol afectado:** `student`  
**UC relacionado:** (sin UC específico aún)  

**Descripción:**
`UserController::edit()` carga `'student.familyProfile'` sin relaciones anidadas. `FamilyProfileResource` usa `whenLoaded('guardianMaritalStatus', ...)` / `whenLoaded('livingArrangement', ...)` / `whenLoaded('householdHeadType', ...)` — que devuelven `MissingValue` y son ausentes del output.

`buildInitialFormData()` lee:
- `f.guardian_marital_status?.name` → `undefined` (campo ausente)
- `f.living_arrangement?.name` → `undefined` (campo ausente)
- `f.household_head_type?.name` → `undefined` (campo ausente)

Los tres dropdowns de S11 aparecen **vacíos al recargar**.

Adicionalmente, `saveFamily()` envía `guardian_marital_status`, `living_arrangement`, `household_head_type` como strings (de `UF_MARITAL`, `UF_LIVING`, `UF_HOUSEHOLD_HEAD`), pero el backend `StoreFamilyProfileRequest` espera `guardian_marital_status_id`, `living_arrangement_id`, `household_head_type_id` como integers FK. El save responde 200 pero **no actualiza los FK IDs**.

**Evidencia:**
- `UserController::edit()` línea 139: `'student.familyProfile'` — sin nested relations
- `FamilyProfileResource::toArray()`: `whenLoaded('guardianMaritalStatus', ...)` → ausente
- `buildInitialFormData()` línea 523: `d.repMarital = f.guardian_marital_status?.name` → `undefined`
- `saveFamily()` línea 232: `guardian_marital_status: formData.repMarital ?? null` — clave string
- `StoreFamilyProfileRequest::rules()`: `'guardian_marital_status_id' => ['nullable', 'integer', 'exists:marital_statuses,id']`
- Confirmado con tinker: DB tiene `guardian_marital_status_id=5`, `living_arrangement_id=1`, `household_head_type_id=2` (fijados por seeder), pero el formulario los muestra vacíos en reload

**Impacto:**
- Al recargar S11: marital_status, living_arrangement, household_head_type siempre vacíos
- Al guardar S11: los FK IDs nunca se actualizan via este formulario (save silencioso, sin 422)

**Acción sugerida:**
1. En `UserController::edit()`, agregar nested relations al with: `'student.familyProfile.guardianMaritalStatus'`, `'student.familyProfile.livingArrangement'`, `'student.familyProfile.householdHeadType'`
2. En `buildInitialFormData()`: leer también `f.guardian_marital_status_id`, `f.living_arrangement_id`, `f.household_head_type_id`
3. En `saveFamily()`: enviar `guardian_marital_status_id`, `living_arrangement_id`, `household_head_type_id` (integers)
4. En `UserFormS11Family.vue`: cambiar selects para usar `catalogData.maritalStatuses`, `catalogData.livingArrangements`, `catalogData.householdHeadTypes` con IDs
5. Agregar `maritalStatuses`, `livingArrangements`, `householdHeadTypes` a `catalogData`

**Estado:** resuelto  
**Fecha de resolución:** 2026-05-26  
**Feature:** user-edit-student-sections-fix  
**Verificación:** `buildInitialFormData()` lee `guardian_marital_status_id`, `living_arrangement_id`, `household_head_type_id` desde el resource plano. `saveFamily()` envía con sufijo `_id`. `catalogData` incluye `livingArrangements`, `householdHeadTypes`. 18 acceptance tests pasan. Ver feature `user-edit-student-sections-fix`.
**Prioridad:** ALTA (datos de familia no persisten en selects; reload siempre muestra vacíos)

---

## HLZ-12 — S12 Socioeconómico: round-trip roto — el reload no pre-llena income_range, income_source ni employment_type

**Fecha:** 2026-05-25  
**Dominio:** security / socioeconomic  
**Rol afectado:** `student`  
**UC relacionado:** (sin UC específico aún)  

**Descripción:**
`UserController::edit()` carga `'student.socioeconomicProfile'` sin relaciones anidadas. `SocioeconomicProfileResource` usa `whenLoaded('incomeRange', ...)` / `whenLoaded('incomeSource', ...)` / `whenLoaded('employmentType', ...)` — que devuelven `MissingValue` y son ausentes del output.

`buildInitialFormData()` lee:
- `e.income_range?.name` → `undefined`
- `e.income_source?.name` → `undefined`
- `e.remittance_country?.name` → `undefined`
- `e.employment_type?.name` → `undefined`

Adicionalmente, `saveSocioeconomic()` envía `income_range`, `income_source`, `remittance_country`, `employment_type` como strings (de arrays locales `UF_INCOME_RANGES`, `UF_INCOME_SOURCES`, `UF_COUNTRIES`, `UF_EMPLOYMENT_TYPES`), pero el backend `StoreSocioeconomicProfileRequest` espera `income_range_id`, `income_source_id`, `remittance_country_id`, `employment_type_id` como integers FK. El save responde 200 pero los FK IDs no se actualizan.

**Evidencia:**
- `UserController::edit()` línea 140: `'student.socioeconomicProfile'` — sin nested relations
- `SocioeconomicProfileResource::toArray()`: `whenLoaded('incomeRange', ...)` → ausente
- `buildInitialFormData()` línea 533: `d.incomeRange = e.income_range?.name` → `undefined`
- `saveSocioeconomic()` línea 245: `income_range: formData.incomeRange ?? null` — string
- `StoreSocioeconomicProfileRequest::rules()`: `'income_range_id' => ['nullable', 'integer', 'exists:income_ranges,id']`
- Confirmado: DB tiene `income_range_id=2`, `income_source_id=1`, `employment_type_id=3` (seeder), formulario muestra vacíos en reload

**Nota adicional sobre `study_date`:** El resource devuelve `study_date` como datetime string de Carbon (`"2026-05-07 00:00:00"`). El input `type="date"` en `UserFormS12Socioeconomic.vue` necesita formato `YYYY-MM-DD`. Si el valor llega como datetime completo, el input de fecha puede quedar vacío o mostrar valor incorrecto en algunos navegadores. Requiere verificación.

**Acción sugerida:**
1. Agregar nested relations al with en `UserController::edit()`: `'student.socioeconomicProfile.incomeRange'`, `'student.socioeconomicProfile.incomeSource'`, `'student.socioeconomicProfile.employmentType'`
2. En `buildInitialFormData()`: leer `e.income_range_id`, `e.income_source_id`, `e.employment_type_id`, `e.remittance_country_id`
3. En `saveSocioeconomic()`: enviar `income_range_id`, `income_source_id`, `employment_type_id`, `remittance_country_id` (integers)
4. En `UserFormS12Socioeconomic.vue`: cambiar selects para usar `catalogData` con IDs
5. Normalizar `study_date` en `buildInitialFormData()`: `e.study_date?.substring(0, 10) ?? undefined` para garantizar formato `YYYY-MM-DD`

**Estado:** resuelto  
**Fecha de resolución:** 2026-05-26  
**Feature:** user-edit-student-sections-fix  
**Verificación:** `buildInitialFormData()` lee `income_range_id`, `income_source_id`, `remittance_country_id`, `employment_type_id` desde resource plano. `saveSocioeconomic()` envía con sufijo `_id`. `SocioeconomicProfileResource` usa `->format('Y-m-d')`. `catalogData` incluye `incomeRanges`, `incomeSources`, `employmentTypes`. 18 acceptance tests pasan.
**Prioridad:** ALTA (datos socioeconómicos no persisten en selects; reload siempre muestra vacíos)

---

## HLZ-13 — S14 Vivienda: save envía strings en lugar de IDs — los FK no se actualizan (pero reload funciona)

**Fecha:** 2026-05-25  
**Dominio:** security / housing  
**Rol afectado:** `student`  
**UC relacionado:** (sin UC específico aún)  

**Descripción:**
`saveHousing()` envía `housing_type`, `tenure_type`, `construction_material`, `commute_time`, `transport_type` como **strings de nombre** (de arrays locales `UF_HOUSING`, `UF_TENURE`, `UF_CONSTRUCTION`, `UF_COMMUTE`, `UF_TRANSPORT`), pero el backend `StoreHousingProfileRequest` espera `housing_type_id`, `tenure_type_id`, `construction_material_id`, `commute_time_id`, `transport_type_id` como integers FK. Las claves sin `_id` son ignoradas — el save responde 200 pero los FK IDs no se actualizan.

**Diferencia con HLZ-10/11/12:** el **reload sí funciona** porque `UserController::edit()` carga las relaciones anidadas del housing profile (`housingType`, `tenureType`, `constructionMaterial`, `commuteTime`, `transportType`, `services`) y el resource devuelve los objetos anidados con `.name`. `buildInitialFormData()` lee correctamente `h.housing_type?.name` y los selects se pre-llenan al recargar. Solo el save está roto.

**Evidencia:**
- `saveHousing()` líneas 284-291: `housing_type: formData.housing ?? null` — string
- `StoreHousingProfileRequest::rules()`: `'housing_type_id' => ['nullable', 'integer', 'exists:housing_types,id']`
- `UserController::edit()` líneas 142-147: housing profile nested relations SÍ están cargadas → `housing_type`, `tenure_type`, etc. presentes en resource
- DB tiene `housing_type_id=2, tenure_type_id=1, ...` (fijados por seeder); el save no los cambia porque las claves no coinciden

**Impacto:**
- El usuario edita el tipo de vivienda, guarda → 200 → sin error visible
- Pero el FK en DB no cambia → el valor editado se pierde al recargar
- Servicios básicos sí persisten correctamente (se envían como strings de código, y `syncServices` los usa correctamente)

**Acción sugerida:**
1. Agregar `catalogData.housingTypes`, `catalogData.tenureTypes`, `catalogData.constructionMaterials`, `catalogData.commuteTimes`, `catalogData.transportTypes` al prop `catalogData` en `UserController::edit()`
2. En `buildInitialFormData()`: leer también los `_id`: `d.housingTypeId = h.housing_type_id ?? undefined`, etc.
3. En `saveHousing()`: enviar `housing_type_id`, `tenure_type_id`, `construction_material_id`, `commute_time_id`, `transport_type_id` (integers)
4. En `UserFormS14Housing.vue`: cambiar selects para usar `catalogData.*` con IDs

**Estado:** resuelto  
**Fecha de resolución:** 2026-05-26  
**Feature:** user-edit-student-sections-fix  
**Verificación:** `saveHousing()` envía `housing_type_id`, `tenure_type_id`, `construction_material_id`, `commute_time_id`, `transport_type_id` como integers. `catalogData` incluye `housingTypes`, `tenureTypes`, `constructionMaterials`, `commuteTimes`, `transportTypes`. 18 acceptance tests pasan.
**Prioridad:** ALTA (edición de tipo de vivienda/tenencia/construcción/traslado no persiste)

---

## HLZ-14 — S04 Demográfico: `DemographicProfileResource` oculta `religion_id` para rol `Admin` — el save lo destruye

**Fecha:** 2026-05-26  
**Dominio:** security / demographic  
**Rol afectado:** `admin`  
**UC relacionado:** UC-05 en specs/qa/security/user-edit.md  

**Descripción:**
`DemographicProfileResource::toArray()` tiene un guard de privilegio:

```php
$isPrivileged = $request->user()->hasAnyRole(['Administrador', 'Coordinador']);
// ...
'religion_id' => $this->when($isPrivileged, $this->religion_id),
```

El rol de administrador en producción se llama `Admin`, no `Administrador`. Para un usuario con rol `Admin`:
- `isPrivileged` → `false`
- `religion_id` → ausente del resource resolve output
- `buildInitialFormData()`: `d.religionId = p.religion_id ?? undefined` → `undefined`
- `saveDemographic()`: envía `religion_id: formData.religionId ?? null` → `null`
- **Resultado: cualquier save de S04 por un admin pone `religion_id = NULL` en DB**, destruyendo el valor existente silenciosamente.

El mismo guard oculta el objeto anidado `religion` (nombre), pero ese campo ya sería `MissingValue` de todos modos porque `UserController::edit()` no carga la relación `religion` anidada.

**Diferencia con HLZ-06:** HLZ-06 documenta el mismo patrón en FormRequests (resultado: 403). HLZ-14 documenta el patrón en un Resource (resultado: corrupción silenciosa de datos, sin error HTTP).

**Evidencia:**
- `app/Http/Resources/Admin/DemographicProfileResource.php` línea 15: `$isPrivileged = $request->user()->hasAnyRole(['Administrador', 'Coordinador'])`
- `Admin` no es `Administrador` → `isPrivileged = false` confirmado por tinker
- `religion_id` ausente del resolve output para admin → `d.religionId = undefined`
- `useUserEditForm.ts` línea 161: `religion_id: formData.religionId ?? null` → `null`
- `StoreDemographicProfileRequest`: `religion_id` es `nullable` → acepta `null` sin error → `religion_id` en DB queda `NULL`

**Acción sugerida:**
1. Reemplazar `hasAnyRole(['Administrador', 'Coordinador'])` por un check correcto. Fix mínimo: `hasAnyRole(['Admin', 'Administrador', 'Coordinador'])`. Fix estructural: usar Gate/Policy para decidir visibilidad del campo.
2. Aclarar la intención del guard: ¿`religion_id` debe ser visible solo para privilegiados o para todos?
3. Test: "Admin puede guardar S04 sin que `religion_id` sea nullificado".

**Estado:** resuelto  
**Fecha de resolución:** 2026-05-26  
**Feature:** user-edit-admin-fix  
**Verificación:** `DemographicProfileResource` ahora incluye `'Admin'` en `hasAnyRole(['Admin', 'Administrador', 'Coordinador'])`. `religion_id` presente en el prop Inertia para rol `Admin`. Guardar S04 sin enviar `religion_id` ya no lo destruye. 6 acceptance tests pasan (RF-01 ×2, RF-02 ×2, RF-03 ×2).

---

## HLZ-15 — S05 Salud: `buildInitialFormData()` ignora los `_id` planos del resource — save destruye los FK existentes

**Fecha:** 2026-05-26  
**Dominio:** security / health  
**Rol afectado:** todos (admin, student, professor, guardian)  
**UC relacionado:** UC-06 en specs/qa/security/user-edit.md  

**Descripción:**
Este hallazgo complementa HLZ-10 con el diagnóstico preciso. `HealthProfileResource` ya expone `blood_type_id`, `disability_type_id` e `insurance_type_id` como campos planos en `toArray()` (siempre presentes, sin nested relations). El bug está en el frontend:

1. `buildInitialFormData()` nunca lee los IDs planos — solo intenta `h.blood_type?.name`, `h.disability_type?.name`, `h.insurance_type?.name` (objetos anidados ausentes porque `UserController::edit()` no carga esas nested relations).
2. Resultado en reload: `d.bloodType = undefined`, `d.disabilityType = undefined`, `d.insuranceType = undefined`. Dropdowns siempre vacíos.
3. `saveHealth()` envía `blood_type: null`, `disability_type: null`, `insurance_type: null` (claves sin `_id`, ignoradas por validación). Los FK en DB no se actualizan — pero tampoco se restablecen con los valores previos.

**Consecuencia agravada:** Los campos planos `blood_type_id`, `disability_type_id`, `insurance_type_id` están presentes en el resource pero nunca se leen en `buildInitialFormData()`. Si el usuario no selecciona esos dropdowns (porque aparecen vacíos), `saveHealth()` envía `null` para esas claves. El backend los acepta como nullable → el valor previo en DB queda en `NULL`. **Guardar S05 sin seleccionar blood_type destruye el blood_type_id existente.**

**Evidencia:**
- `HealthProfileResource::resolve()` sin nested load: `blood_type_id: 1` presente, `blood_type`: ausente — confirmado por tinker
- `buildInitialFormData()` línea 464: `d.bloodType = h.blood_type?.name` → `undefined`
- `saveHealth()` línea 171: `blood_type: formData.bloodType ?? null` → `null`
- `StoreHealthProfileRequest`: `blood_type_id` nullable → acepta `null` → destruye el valor previo

**Corrección necesaria (frontend):**

| Componente | Cambio |
|---|---|
| `buildInitialFormData()` | `d.bloodTypeId = h.blood_type_id ?? undefined`, `d.disabilityTypeId = h.disability_type_id ?? undefined`, `d.insuranceTypeId = h.insurance_type_id ?? undefined` |
| `saveHealth()` | `blood_type_id: formData.bloodTypeId ?? null`, `disability_type_id: formData.disabilityTypeId ?? null`, `insurance_type_id: formData.insuranceTypeId ?? null` |
| `UserFormS05Health.vue` | Selects usando `catalogData.bloodTypes`, `catalogData.disabilityTypes`, `catalogData.insuranceTypes` con IDs |
| `UserController::edit()` | Agregar `BloodType`, `DisabilityType`, `InsuranceType` a `catalogData` |
| Tipos TS | Agregar `bloodTypeId?`, `disabilityTypeId?`, `insuranceTypeId?` a `UserFormData` |

La carga de nested relations en `UserController::edit()` para `healthProfile` puede eliminarse si se adopta el enfoque de IDs planos — el resource ya los provee sin necesidad de nested load.

**Relación con HLZ-10:** HLZ-10 documenta el bug. HLZ-15 precisa que el resource está correcto y el fix es frontend-only.

**Estado:** resuelto  
**Fecha de resolución:** 2026-05-26  
**Feature:** user-edit-health-fix  
**Verificación:** Fix frontend-only confirmado. `buildInitialFormData()` lee `h.blood_type_id`, `h.disability_type_id`, `h.insurance_type_id` (campos planos del resource). `saveHealth()` envía `blood_type_id`, `disability_type_id`, `insurance_type_id` (con `_id`). No se requirió cambiar `HealthProfileResource` ni el eager-loading del controller para los campos de catálogo. 12 acceptance tests pasan. Ver UC-32, UC-33, UC-34 en specs/qa/security/user-edit.md.

---

## HLZ-26 — S16 Representante: `workDial` no se inicializa en reload — dropdown de indicativo siempre vuelve a +58

**Fecha:** 2026-05-26  
**Dominio:** security / guardian  
**Rol afectado:** `guardian`  
**UC relacionado:** UC-11 en specs/qa/security/user-edit.md

**Descripción:**
El campo `workDial` (indicativo telefónico del teléfono laboral) es capturado por `AppTelInput` en `UserFormS16GuardianProfile.vue` (`@update:dial="setField('workDial', $event)"`), pero **nunca se inicializa en `buildInitialFormData()`** y **nunca se envía en `saveGuardianProfile()`**.

Al recargar la página, `data.workDial` es `undefined`, por lo que el componente usa el fallback `:dial="data.workDial ?? '+58'"` — el dropdown de indicativo siempre muestra Venezuela (+58) independientemente de lo que el usuario haya seleccionado.

**Por qué no hay pérdida de datos real:** La tabla `guardian_profiles` no tiene columna `work_dial`. El indicativo no se persiste en DB. `work_phone` almacena solo el número local (ej. `0212-9271924`). El indicativo es puramente cosmético en el componente `AppTelInput`. El round-trip del número de teléfono en sí funciona correctamente.

**Diferencia con HLZ-10/11/12/13:** No hay dato perdido en DB — es solo que el selector de indicativo no puede mantener su estado entre recargas porque no hay columna en DB para persistirlo.

**Evidencia:**
- `UserFormS16GuardianProfile.vue` línea 26: `:dial="data.workDial ?? '+58'"` — fallback hardcoded
- `useUserEditForm.ts` línea 574: `d.workPhone = g.work_phone ?? undefined` — solo número, sin dial
- `guardian_profiles` migration: columna `work_phone VARCHAR(20)` — sin `work_dial`
- DB user 137: `work_phone = "0212-9271924"` (número local sin indicativo)

**Acción sugerida (dos opciones):**
- **Opción A — Aceptar como diseño:** Documentar que `workDial` siempre vale `'+58'` por defecto. Si la institución es venezolana, esto es correcto en el 99% de los casos. No requiere acción.
- **Opción B — Agregar columna `work_phone_dial`:** Agregar `work_phone_dial VARCHAR(10) DEFAULT '+58'` a `guardian_profiles`, persistirlo en `StoreGuardianProfileRequest`, `GuardianProfileWrapper`, `UpsertGuardianProfileAction`, y leerlo en `buildInitialFormData()`.

**Estado:** pendiente  
**Prioridad:** BAJA (cosmético — el número de teléfono persiste correctamente; solo el indicativo se resetea a +58)

---

## HLZ-20 — S17 Coordinador: `exists:departments,id` consulta tabla inexistente — crash 500 cuando `is_coordinator=true`

**Fecha:** 2026-05-26  
**Dominio:** security / professors / staff-profile  
**Rol afectado:** `admin` (al guardar S17 con `is_coordinator=true`)  
**UC relacionado:** UC-16 en specs/qa/security/user-edit.md  

**Descripción:**
`StoreStaffProfileRequest::rules()` define la regla de validación:

```php
'coordinated_department_id' => ['nullable', 'integer', 'exists:departments,id', 'required_if:is_coordinator,true'],
```

La tabla referenciada es **`departments`** — que **no existe** en el schema de la base de datos. La tabla real se llama **`coordinations`** (modelo `Coordination`). Cuando `coordinated_department_id` tiene un valor (lo que ocurre cuando `is_coordinator=true`), el validador ejecuta `SELECT count(*) FROM departments WHERE id = ?` y obtiene un `QueryException` SQLSTATE[42P01]: Undefined table, lo que causa un **HTTP 500**.

El bug está dormido porque:
1. Los tests de `StaffProfileTest` solo verifican el caso `is_coordinator=true` con `coordinated_department_id` omitido (lo que activa `required_if`, no `exists`).
2. No hay test del happy path con `is_coordinator=true` + `coordinated_department_id` con valor.
3. La tabla `coordinations` tiene 0 filas en desarrollo — los selects de "departamento" están vacíos.

**Problema relacionado en el modelo:** `StaffProfile::coordinatedDepartment()` usa `Department::class` (que no existe como clase PHP). Esta relación crashea con `Class "App\Models\Department" not found` si alguna vez se intenta eager-load.

**Evidencia:**
- `StoreStaffProfileRequest` línea 33: `'exists:departments,id'`
- `php artisan tinker`: `Validator::make(["coordinated_department_id" => 1], ["coordinated_department_id" => ["nullable","integer","exists:departments,id"]])` → `SQLSTATE[42P01]: Undefined table: departments`
- `StaffProfile.php` línea 67: `$this->belongsTo(Department::class, 'coordinated_department_id')` — `Department::class` no importado ni definido
- `UserController::edit()`: `catalogData.departments` = `Coordination::where('active', true)->...` — correcto, usa `Coordination`

**Acción sugerida:**
1. En `StoreStaffProfileRequest`: cambiar `exists:departments,id` → `exists:coordinations,id`
2. En `StaffProfile.php`: cambiar `Department::class` → `Coordination::class` + agregar `use App\Models\Coordination;`
3. Agregar test: "guardar S17 con `is_coordinator=true` y `coordinated_department_id` válido devuelve 200"

**Estado:** resuelto  
**Fecha de resolución:** 2026-05-26  
**Feature:** user-edit-professor-s17-fix  
**Verificación:** `StoreStaffProfileRequest::rules()`: `exists:coordinations,id`. `StaffProfile::coordinatedDepartment()`: usa `Coordination::class`. Guardar S17 con `is_coordinator=true` + `coordinated_department_id` válido → HTTP 200. Acceptance tests RF-01 y RF-02 pasan (4 tests).

---

## HLZ-21 — S17 Fechas: `hire_date`, `termination_date`, `coordinator_since` llegan como ISO 8601 — input[type=date] siempre aparece vacío

**Fecha:** 2026-05-26  
**Dominio:** security / professors / staff-profile  
**Rol afectado:** `professor`, `admin`  
**UC relacionado:** UC-16 en specs/qa/security/user-edit.md  

**Descripción:**
`StaffProfileResource` serializa los campos de fecha usando el cast `'date'` de Eloquent (Carbon). Cuando el modelo es serializado a JSON (vía `->resolve()`), Carbon produce formato ISO 8601: `"2014-09-09T00:00:00.000000Z"`.

`buildInitialFormData()` asigna directamente estos valores:

```ts
d.hireDate   = p.hire_date          ?? undefined   // "2014-09-09T00:00:00.000000Z"
d.endDate    = p.termination_date   ?? undefined   // igual
d.coordSince = p.coordinator_since  ?? undefined   // igual
```

`UserFormS17ProfessorProfile.vue` usa `<input type="date" :value="data.hireDate" ...>`. El HTML spec de `input[type=date]` requiere exactamente el formato `YYYY-MM-DD`. Un valor como `"2014-09-09T00:00:00.000000Z"` es rechazado silenciosamente por el navegador — el campo se muestra **vacío** aunque el dato exista en DB.

**Consecuencia en UX:** Al editar un profesor con `hire_date` guardado, el campo "Fecha de ingreso" aparece vacío. El usuario puede pensar que el dato se perdió. Si guarda S17 sin tocar ese campo, `formData.hireDate` sigue siendo `"2014-09-09T00:00:00.000000Z"` internamente (Vue no actualiza el model reactive para inputs inválidos), y el backend acepta el string ISO como `date` válido — el dato no se destruye, pero la UX es confusa y propensa a error.

**Diferencia con HLZ-12 (`study_date`):** El mismo patrón pero en S17 para tres campos de fecha. HLZ-12 lo documentó para `study_date` en S12; este hallazgo lo extiende a S17.

**Evidencia:**
- `StaffProfile::casts()`: `'hire_date' => 'date'` → Carbon serializa como ISO 8601 en JSON
- Tinker: `(new StaffProfileResource(StaffProfile::find(1)))->resolve()["hire_date"]` → `"2014-09-09T00:00:00.000000Z"`
- `buildInitialFormData()` línea 587: `d.hireDate = p.hire_date ?? undefined` — sin `.substring(0, 10)`
- `<input type="date" :value="data.hireDate">` — el navegador muestra vacío para formato no-`YYYY-MM-DD`

**Acción sugerida:**
1. En `buildInitialFormData()`, normalizar las tres fechas de staff profile:
   ```ts
   d.hireDate   = p.hire_date?.substring(0, 10)          ?? undefined
   d.endDate    = p.termination_date?.substring(0, 10)   ?? undefined
   d.coordSince = p.coordinator_since?.substring(0, 10)  ?? undefined
   ```
2. Alternativa en `StaffProfileResource`: devolver fechas como `$this->hire_date?->toDateString()` (solo `YYYY-MM-DD`).
3. Agregar test: "reload de S17 con hire_date existente pre-llena el campo en formato YYYY-MM-DD".

**Estado:** resuelto  
**Fecha de resolución:** 2026-05-26  
**Feature:** user-edit-professor-s17-fix  
**Verificación:** `buildInitialFormData()` aplica `.substring(0, 10)` a `hire_date`, `termination_date` y `coordinator_since`. `props.professor.staffProfile.hire_date` llega como `YYYY-MM-DD` (10 chars). Acceptance tests RF-03 y RF-04 pasan (4 tests).

---

## HLZ-27 — S01 Identidad: campos de identidad extendida no se cargan ni se persisten

**Fecha:** 2026-05-26  
**Dominio:** security / users  
**UC relacionados:** UC-02 (actualizado), UC-35, UC-36 en specs/qa/security/user-edit.md  
**Descripción:**
El formulario S01 muestra como editables: documento (tipo + número), fecha de nacimiento, género, nacionalidad, teléfonos y foto de perfil. Todos estos campos tienen columnas en la tabla `users` (`document_type_id`, `document_number`, `birth_date`, `gender_id`, `nationality_id`, `phone_primary`, `phone_secondary`, `profile_photo_url`). Sin embargo, **ninguno se carga desde DB al abrir la página y ninguno se envía al guardar**.

**Causa raíz — dos capas rotas:**

1. **Backend no los expone:** `UserEditResource::toArray()` solo devuelve `id`, `first_name`, `last_name`, `name`, `email`, `active`, `roles`, `created_at`. Las columnas adicionales de `users` no están en el resource.

2. **Frontend no los envía:** el handler `1` en `useUserEditForm.ts` solo incluye `{ first_name, last_name, email, roles }`. `UpdateUserRequest::rules()` solo valida esos mismos 4 campos.

**Consecuencia:** guardar S01 responde 200 y marca la sección `complete`, pero las ediciones de doc/fecha/teléfono/género/nacionalidad se pierden silenciosamente.

**Evidencia:**
- `UserEditResource.php`: 8 campos expuestos; 8 columnas relevantes ausentes
- `useUserEditForm.ts:336–343`: handler `1` — 4 campos enviados
- `UpdateUserRequest.php:60–66`: 4 campos validados
- `vendor/bin/sail artisan db:table users`: `document_type_id`, `document_number`, `birth_date`, `gender_id`, `nationality_id`, `phone_primary`, `phone_secondary`, `profile_photo_url` confirmados en schema
- `buildInitialFormData()` líneas 429–432: solo lee `firstName`, `lastName`, `email`, `roles`

**Acción sugerida:**
1. Exponer los 8 campos adicionales en `UserEditResource` (con `document_type` serializado como código string o ID según decisión HLZ-28)
2. Actualizar tipo `User` en `userEdit.ts`
3. Actualizar `buildInitialFormData()` para leer los nuevos campos
4. Actualizar handler `1` para enviar los campos faltantes
5. Actualizar `UpdateUserRequest::rules()` para validarlos
6. Actualizar `UpdateUserAction` / `UserWrapper` para persistirlos
7. Ver HLZ-28 para la decisión de tipos antes de implementar

**Estado:** pendiente  
**Prioridad:** CRÍTICO — campos visibles en UI que nunca persisten

---

## HLZ-28 — S01 Identidad: mismatch de tipos entre catálogos estáticos y FKs de DB

**Fecha:** 2026-05-26  
**Dominio:** security / users / catalog  
**UC relacionado:** UC-38 en specs/qa/security/user-edit.md  
**Descripción:**
`UserFormS01Identity.vue` usa tres catálogos estáticos de `userFormCatalogs.ts`:
- `UF_DOC_TYPES`: keys `'V'`, `'E'`, `'P'`, `'J'` (strings)
- `UF_GENDERS`: keys `'f'`, `'m'`, `'o'`, `'na'` (strings)
- `UF_COUNTRIES`: keys `'ve'`, `'co'`, etc. (strings)

Pero la tabla `users` almacena `document_type_id` (int8 FK → `document_types`), `gender_id` (int8 FK → `genders`), `nationality_id` (int8 FK → `countries`). Esas tablas tienen una columna `code` varchar que podría usarse como puente, pero no existe ninguna conversión implementada en el sistema.

**Decisión de diseño requerida antes de implementar HLZ-27:**

| Opción | Pros | Contras |
|--------|------|---------|
| **A) Catálogos dinámicos** — cargar `document_types`, `genders`, `countries` desde `catalogData` con sus IDs | Consistente con S04, S05, S17 | Más cambios: backend + frontend + types |
| **B) Códigos string** — guardar el `code` en columnas varchar en `users` | Frontend más simple | Rompe integridad referencial; contra el diseño existente |

**Recomendación:** Opción A. Es el patrón ya establecido en el sistema. Los IDs de catálogo se cargan en `UserController::edit()` → `catalogData` → componente recibe IDs.

**Evidencia:**
- `userFormCatalogs.ts:41–67`: `UF_DOC_TYPES`, `UF_GENDERS`, `UF_COUNTRIES` con keys string
- `vendor/bin/sail artisan db:table document_types`: columna `code varchar` + FK `id int8`
- `vendor/bin/sail artisan db:table genders`: misma estructura
- Tablas S04, S05 ya usan el patrón de catálogos dinámicos con IDs

**Estado:** pendiente  
**Prioridad:** CRÍTICO — bloquea la implementación de HLZ-27

---

## HLZ-29 — S14 Vivienda: `housingProfile.services.some()` crash cuando estudiante tiene servicios en DB

**Fecha:** 2026-05-26  
**Dominio:** security / housing  
**Rol afectado:** `student` con `housing_profile` y ≥1 servicio en `housing_services`  
**UC relacionado:** (nuevo — sin UC aún)  

**Descripción:**
`buildInitialFormData()` en `useUserEditForm.ts` (líneas 560–563) hace:

```ts
d.svc_water = (h.services ?? []).some(sv => sv.code === 'potable_water' && sv.is_available)
d.svc_elec  = (h.services ?? []).some(sv => sv.code === 'electricity'   && sv.is_available)
d.svc_gas   = (h.services ?? []).some(sv => sv.code === 'gas'           && sv.is_available)
d.svc_inet  = (h.services ?? []).some(sv => sv.code === 'internet'      && sv.is_available)
```

El guard `h.services ?? []` solo protege contra `null`/`undefined`. Si `h.services` llega como `{ data: [...] }` (objeto — `ResourceCollection` sin `.resolve()`), el guard no activa el fallback y `.some()` falla con `TypeError: (h.services ?? []).some is not a function`.

**Evidencia en browser logs (hoy 2026-05-26 00:36–00:37):**
```
TypeError: Cannot read properties of undefined (reading 'some')
  at Me (Edit-B6E3sVrM.js:1:14605)
URL: /security/users/130/edit y /security/users/63/edit
```
Estos son usuarios con rol `student` (S14 solo se activa para estudiantes).

**Causa probable:** `HousingProfileResource` o el resource del student que contiene el housing profile devuelve `services` como colección no resuelta. Verificar si el eager-load de `'student.housingProfile.services'` en `UserController::edit()` llega correctamente como array al frontend.

**Acción sugerida:**
1. Verificar en `UserController::edit()` que la relación `housingProfile.services` esté en el `with()` y que el resource la serialice como array plano
2. Si `HousingProfileResource` usa `$this->whenLoaded('services', ...)` con un `ResourceCollection`, asegurarse de llamar `.resolve()` o usar `$this->whenLoaded('services', fn () => $this->services->map(...))`
3. Agregar test: "editar estudiante con ≥1 servicio en housing_profile → página carga sin error JS"
4. Variante de cobertura: test con 0 servicios (colección vacía) para prevenir regresión

**Nota de seguimiento (2026-05-26):** El error es del build `Edit-B6E3sVrM.js`. El build actual es `Edit-COISBrbU.js` (compilado tras `user-edit-student-sections-fix`). No hay nuevas entradas en browser logs para estos usuarios con el build nuevo. Posiblemente resuelto como efecto secundario de esa feature — **requiere verificación manual** en `/security/users/130/edit` y `/security/users/63/edit`.

**Estado:** pendiente (verificación pendiente)  
**Prioridad:** CRÍTICO si el crash persiste con el build actual; BAJA si fue resuelto

---

## HLZ-22 — `UserSeeder` nunca crea sub-registros — cada `migrate:fresh --seed` reproduce HLZ-07

**Fecha:** 2026-05-26  
**Dominio:** infrastructure / seeders  
**Roles afectados:** `professor`, `student`, `guardian`  
**UC relacionado:** (raíz de causa de HLZ-07)  

**Descripción:**
`database/seeders/UserSeeder.php` crea los usuarios base (admin, profesor demo, estudiante demo, coordinador demo) a partir de `database/data/users.yaml` y les asigna roles vía `syncRoles()`. Sin embargo, **nunca llama a `ensureSubRecord()`** ni crea las filas en `professors`, `students` ni `guardians`.

Consecuencia: cada vez que se ejecuta `php artisan migrate:fresh --seed` (o el seeder en un entorno fresco), el usuario "Profesor Demo" (user_id=2) y el usuario "Estudiante Demo" (user_id=3) quedan en el mismo estado huérfano que documenta HLZ-07. `users:fix-orphan-records` debe ejecutarse **manualmente** después para reparar el estado.

**Evidencia:**
- `UserSeeder::run()`: solo llama `User::factory()->create(...)` + `$user->syncRoles(...)` — sin `Professor::firstOrCreate`, `Student::firstOrCreate` ni `Guardian::firstOrCreate`
- Confirmado 2026-05-26: DB fresca → `users:fix-orphan-records` reporta "Estudiante: user #3 (Estudiante Demo) — Fixed 1 orphan". User 2 fue creado hoy vía DB fresca y no tenía `professor` row.
- `CreateUserAction::ensureSubRecord()` y `UpdateUserAction::ensureSubRecord()` tienen la lógica correcta — el seeder simplemente no la usa.

**Acción sugerida:**
1. En `UserSeeder::run()`, después de `$user->syncRoles(...)`, llamar `ensureSubRecord($user, $userData['role'])`. Extraer este método o inyectar `CreateUserAction`.
2. Alternativa más simple: añadir `Professor::firstOrCreate(['user_id' => $user->id])`, `Student::firstOrCreate(...)`, `Guardian::firstOrCreate(...)` condicionalmente según el rol.
3. Agregar test en `UserSeederTest` o en `DatabaseSeeder` integration: "seed fresco crea filas en professors, students, guardians para los usuarios demo".

**Estado:** resuelto  
**Fecha de resolución:** 2026-05-26  
**Feature:** user-seeder-subrecord-fix  
**Verificación:** `UserSeeder::run()` llama `ensureSubRecord()` / `firstOrCreate` después de `syncRoles()`. `migrate:fresh --seed` crea automáticamente filas en `professors` y `students` para los usuarios demo. Acceptance tests RF-01 a RF-04 pasan (6 tests). `users:fix-orphan-records` ya no es necesario post-seed.
