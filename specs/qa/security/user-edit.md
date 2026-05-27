# Casos de uso — Edición de usuario (`/security/users/{id}/edit`)

**Dominio:** security  
**Flujo:** editar usuario  
**Última revisión:** 2026-05-26 (user-edit-admin-fix)  
**Auditor:** QA Manager

---

## Contexto

El formulario de edición de usuario tiene 17 secciones organizadas en tabs por rol. Las secciones visibles dependen del rol del usuario editado:

| Rol       | Tabs                                             | Secciones activas            |
|-----------|--------------------------------------------------|------------------------------|
| `admin`   | Identidad, Salud, Documentos                     | 1–7                          |
| `student` | Identidad, Salud, Académico, Familia, Socioeconómico, Documentos | 1–15     |
| `professor`| Identidad, Salud, Profesional, Documentos       | 1–7, 17                      |
| `guardian` | Identidad, Salud, Representante, Documentos    | 1–7, 16                      |

---

## UC-01 — Carga inicial de la página

**Precondición:** admin autenticado; usuario objetivo existe en DB.  
**Pasos:** navegar a `/security/users/{id}/edit`  
**Resultado esperado:**
- Inertia renderiza `security/Users/Edit`
- Props presentes: `user`, `addresses`, `documents`, `consent`, `catalogData`
- `user.roles[0]` contiene un `RoleKey` válido (`admin`, `student`, `professor`, `guardian`)
- `catalogData` contiene `countries`, `states`, `languages`, `languageLevels`, `benefits`, `religions`, `institutionTypes`, `transferReasons`, `digitalLevels`, `educationLevels`, `maritalStatuses`, `contractTypes`, `dedicationTypes`, `employmentStatuses`, `departments`
- Las props adicionales (`student`, `professor`, `guardian`) solo están presentes si el usuario tiene ese perfil
- El header muestra el rol mapeado correctamente (no el nombre Spatie en español)

**Test Dusk:** pendiente  
**Feature de origen:** user-form-connect; actualizado en user-edit-catalog-ids  
**Última verificación:** 2026-05-25 ✅ (acceptance tests UserEditCatalogIdsAcceptanceTest pasan)

---

## UC-02 — S01 Identidad personal — carga y campos activos

**Precondición:** página cargada.  
**Pasos:** tab "Identidad" → sección 01 visible.  
**Resultado esperado:**
- `first_name` y `last_name` pre-poblados desde `props.user`
- Los campos de documento, teléfonos, fecha de nacimiento, género, nacionalidad y foto son **editables** (no read-only)
- La sección arranca en estado `complete` (secciones 1 y 2 siempre pre-marcadas)

**Nota HLZ-27:** los campos de identidad extendida (doc, fecha, género, nacionalidad, teléfonos, foto) aparecen vacíos aunque existan en DB — `UserEditResource` no los expone y `buildInitialFormData()` no los carga. Ver UCs 35–38.

**Test Dusk:** pendiente  
**Feature de origen:** user-form-views  
**Última verificación:** 2026-05-25 ✅ (lógica `initialSavedSections` correcta) — ⚠️ ver HLZ-27

---

## UC-03 — S02 Credenciales de acceso

**Precondición:** página cargada.  
**Pasos:** tab "Identidad" → sección 02.  
**Resultado esperado:**
- Email pre-poblado
- Campo de contraseña disponible (modo edición)
- Save → handler es `Promise.resolve()` (stub — no envía request); sección queda `complete`

**Test Dusk:** pendiente  
**Feature de origen:** user-form-connect  
**Última verificación:** 2026-05-25 — ⚠️ stub: no persiste cambios de email/contraseña desde esta sección

---

## UC-04 — S03 Dirección (repeatable, con catalogData)

**Precondición:** página cargada; `catalogData.countries` y `catalogData.states` disponibles.  
**Pasos:** tab "Identidad" → sección 03 → agregar dirección → seleccionar país → el select de estado se filtra por `country_id`.  
**Resultado esperado:**
- Select de países muestra opciones de `catalogData.countries`
- Al cambiar país, el `state_id` se resetea y el select de estados filtra por `country_id`
- Al guardar: POST a `/security/users/{user}/addresses` para items nuevos; PUT para existentes; DELETE para eliminados
- Props `country_id` y `state_id` son numéricos (no strings)

**Test Dusk:** pendiente  
**Feature de origen:** user-form-connect  
**Última verificación:** 2026-05-25 ✅ (lógica del componente correcta; test backend S3 pasa)

---

## UC-05 — S04 Perfil demográfico

**Precondición:** página cargada.  
**Pasos:** tab "Identidad" → sección 04 → editar → guardar.  
**Resultado esperado:**
- PUT a `/security/users/{user}/demographic-profile`
- Segunda llamada actualiza (upsert); no crea duplicado
- `religion_id` está presente en el prop `demographicProfile` para el rol `Admin` (fix HLZ-14)
- Guardar sin enviar `religion_id` **no** destruye el valor existente en DB para rol `Admin`

**Nota HLZ-14 (user-edit-admin-fix):** `DemographicProfileResource` tenía un guard `hasAnyRole(['Administrador', 'Coordinador'])` que excluía el rol `Admin` (nombre real en producción). Esto causaba que `religion_id` quedara ausente del prop → el frontend lo enviaba como `null` → el save lo destruía silenciosamente. Fix: agregar `'Admin'` a la lista del `hasAnyRole()`.

**Test Dusk:** pendiente  
**Feature de origen:** user-profiles  
**Última verificación:** 2026-05-26 ✅ (test S4 pasa; fix HLZ-14 verificado — 6 acceptance tests pasan)

---

## UC-06 — S05 Salud

**Precondición:** usuario objetivo tiene consentimiento activo.  
**Pasos:** tab "Salud" → sección 05 → editar → guardar.  
**Resultado esperado:**
- PUT a `/security/users/{user}/health-profile`
- Los dropdowns de tipo de sangre, discapacidad y seguro médico muestran opciones de `catalogData.bloodTypes`, `catalogData.disabilityTypes`, `catalogData.insuranceTypes`
- Guardar con `blood_type_id` seleccionado persiste el ID en DB
- Recargar la página pre-llena los tres dropdowns con los valores guardados

**Nota HLZ-10/HLZ-15 (user-edit-health-fix):** antes del fix, `buildInitialFormData()` leía `h.blood_type?.name` (objeto anidado ausente) → `undefined`. Los tres dropdowns siempre aparecían vacíos aunque hubiera datos en DB. Además, `saveHealth()` enviaba claves sin `_id` (`blood_type`, `disability_type`, `insurance_type`) que el backend ignoraba; los valores previos en DB quedaban en `NULL` silenciosamente. Fix: leer los campos planos `blood_type_id`, `disability_type_id`, `insurance_type_id` del resource; enviar con sufijo `_id`; agregar `bloodTypes`, `disabilityTypes`, `insuranceTypes` a `catalogData`. Frontend-only — el resource ya devolvía los campos planos correctamente.

**Test automático:** `tests/Feature/UserEditHealthFix/Acceptance/HealthProfileS05AcceptanceTest.php` (RF-01 ×2, RF-02, RF-03, RF-04 ×2, RF-05 ×2, RF-06 ×4 = 12 tests); `tests/Feature/Security/UserEditUseCaseTest.php` (nuevo S5 catalog IDs test)  
**Test Dusk:** `tests/Browser/Security/UserEditS05HealthTest.php` (7 tests: UC-S05-01 ×2, UC-S05-02, UC-S05-03, UC-S05-04, UC-S05-05, UC-S05-06) ✅ 7/7 pasan  
**Feature de origen:** user-profiles; actualizado en user-edit-health-fix  
**Última verificación:** 2026-05-27 ✅ (12 acceptance tests pasan; 7 Dusk tests pasan; HLZ-10 y HLZ-15 resueltos)

---

## UC-32 — S05 carga blood_type_id pre-llenado del DB (fix HLZ-10/HLZ-15)

**Precondición:** admin autenticado; usuario objetivo tiene `health_profiles.blood_type_id`, `disability_type_id` y/o `insurance_type_id` con valores no nulos en DB.  
**Pasos:** GET `/security/users/{user}/edit`  
**Resultado esperado:**
- `props.healthProfile.blood_type_id` es un entero igual al valor en DB (RF-01)
- `props.healthProfile.disability_type_id` es un entero igual al valor en DB (RF-02)
- `props.healthProfile.insurance_type_id` es un entero igual al valor en DB (RF-03)
- Ninguno de los tres campos llega como `null` ni como objeto anidado ausente

**Contexto:** `HealthProfileResource` ya exponía los campos planos; el bug estaba en `buildInitialFormData()` que ignoraba los `_id` y solo buscaba el objeto anidado `blood_type?.name` (siempre ausente porque el controller no eager-carga esas relaciones).

**Fix aplicado (user-edit-health-fix):**
- `buildInitialFormData()`: `d.bloodTypeId = h.blood_type_id ?? undefined`, `d.disabilityTypeId = h.disability_type_id ?? undefined`, `d.insuranceTypeId = h.insurance_type_id ?? undefined`

**Test automático:** `tests/Feature/UserEditHealthFix/Acceptance/HealthProfileS05AcceptanceTest.php` (RF-01 ×2, RF-02, RF-03)  
**Test Dusk:** `tests/Browser/Security/UserEditS05HealthTest.php` (UC-S05-01 ×2) ✅  
**Feature de origen:** user-edit-health-fix  
**Última verificación:** 2026-05-27 ✅ (4 acceptance tests pasan; 2 Dusk tests pasan)

---

## UC-33 — S05 guarda con IDs de catálogo y no destruye los existentes (fix HLZ-10/HLZ-15)

**Precondición:** admin autenticado; consentimiento activo para el usuario objetivo.  
**Pasos:**
- PUT `/security/users/{user}/health-profile` con `blood_type_id` (RF-04)
- PUT `/security/users/{user}/health-profile` sin `blood_type_id` cuando ya hay uno guardado en DB (RF-05)

**Resultado esperado (RF-04):**
- HTTP 200
- `health_profiles.blood_type_id` en DB coincide con el ID enviado
- Response JSON incluye `data.blood_type_id` con el mismo valor

**Resultado esperado (RF-05):**
- HTTP 200
- `health_profiles.blood_type_id` en DB permanece con el valor previo — NO se nullifica
- `health_profiles.disability_type_id` en DB permanece con el valor previo cuando se omite del payload

**Contexto RF-05:** antes del fix, `saveHealth()` enviaba `blood_type: null` (clave incorrecta pero `??null` producía valor nulo). El backend `StoreHealthProfileRequest` tiene `blood_type_id` como nullable; aceptaba `null` explícito y destruía el valor previo. Fix: enviar `blood_type_id: formData.bloodTypeId ?? null` (clave correcta con `_id`).

**Test automático:** `tests/Feature/UserEditHealthFix/Acceptance/HealthProfileS05AcceptanceTest.php` (RF-04 ×2, RF-05 ×2)  
**Test Dusk:** `tests/Browser/Security/UserEditS05HealthTest.php` (UC-S05-02, UC-S05-03) ✅  
**Feature de origen:** user-edit-health-fix  
**Última verificación:** 2026-05-27 ✅ (4 acceptance tests pasan; 2 Dusk tests pasan)

---

## UC-34 — catalogData incluye bloodTypes, disabilityTypes e insuranceTypes (fix HLZ-10/HLZ-15)

**Precondición:** admin autenticado; seeder `SocioeconomicCatalogsSeeder` ejecutado.  
**Pasos:** GET `/security/users/{user}/edit`  
**Resultado esperado (RF-06):**
- `props.catalogData.bloodTypes` es un array no vacío
- `props.catalogData.disabilityTypes` es un array no vacío
- `props.catalogData.insuranceTypes` es un array no vacío
- Cada item tiene al menos `{ id: number, name: string }`

**Fix aplicado (user-edit-health-fix):**
- `UserController::edit()`: agrega `BloodType::active()->ordered()->get(['id','name','code'])`, `DisabilityType::active()->ordered()->get(['id','name','code'])`, `InsuranceType::active()->ordered()->get(['id','name','code'])` a `catalogData`
- `UserFormCatalogData` interface en `userEdit.ts`: agrega `bloodTypes`, `disabilityTypes`, `insuranceTypes`

**Test automático:** `tests/Feature/UserEditHealthFix/Acceptance/HealthProfileS05AcceptanceTest.php` (RF-06 ×4)  
**Test Dusk:** `tests/Browser/Security/UserEditS05HealthTest.php` (UC-S05-04) ✅  
**Feature de origen:** user-edit-health-fix  
**Última verificación:** 2026-05-27 ✅ (4 acceptance tests pasan; 1 Dusk test pasa)

---

## UC-07 — S06 Consentimientos (read-only para admin)

**Precondición:** página cargada.  
**Pasos:** tab "Documentos" → sección 06.  
**Resultado esperado:**
- La sección es display-only para admin (el endpoint POST solo acepta al propio usuario)
- Save es stub (`Promise.resolve()`)
- Si existe consentimiento activo, la sección aparece `complete`

**Test Dusk:** pendiente  
**Feature de origen:** user-profiles  
**Última verificación:** 2026-05-25 ✅ (diseño intencional documentado en composable)

---

## UC-08 — S07 Documentos adjuntos (display)

**Precondición:** página cargada.  
**Pasos:** tab "Documentos" → sección 07.  
**Resultado esperado:**
- Lista de documentos del usuario
- Save es stub (`Promise.resolve()`)
- Upload de archivos es feature separada

**Test Dusk:** pendiente  
**Feature de origen:** user-profiles  
**Última verificación:** 2026-05-25 ✅ (test S7 para upload separado pasa)

---

## UC-09 — S10 Idiomas (student, IDs numéricos)

**Precondición:** usuario tiene rol `student`; `catalogData.languages` y `catalogData.languageLevels` disponibles (9 idiomas, 7 niveles).  
**Pasos:** tab "Académico" → sección 10 → agregar idioma → seleccionar idioma y nivel → guardar.  
**Resultado esperado:**
- Selects muestran opciones de `catalogData.languages` / `catalogData.languageLevels`
- POST a `/security/students/{student}/languages` con `{ language_id: number, language_level_id: number, is_mother_tongue: boolean }`
- DELETE para idiomas removidos (keyed by `language_id`, no por id de pivot)
- `formData.languages[i].language_id` es número entero (no string)

**Test Dusk:** pendiente  
**Feature de origen:** user-form-connect  
**Última verificación:** 2026-05-25 ✅ (tests S10 add/remove pasan)

---

## UC-10 — S13 Beneficios institucionales (student, IDs numéricos)

**Precondición:** usuario tiene rol `student`; `catalogData.benefits` disponible (6 beneficios).  
**Pasos:** tab "Socioeconómico" → sección 13 → agregar beneficio → seleccionar → toggle activo → guardar.  
**Resultado esperado:**
- Select muestra opciones de `catalogData.benefits`
- POST a `/security/students/{student}/benefits/{benefit}` para items nuevos
- DELETE para items removidos (keyed by `benefit_id`)
- `formData.benefits[i].benefit_id` es número entero (no string)

**Test Dusk:** pendiente  
**Feature de origen:** user-form-connect  
**Última verificación:** 2026-05-25 ✅ (tests S13 attach/detach pasan)

---

## UC-11 — S16 Perfil del representante (guardian)

**Precondición:** usuario tiene rol `guardian`; existe registro `Guardian` vinculado.  
**Pasos:** tab "Representante" → sección 16 → editar → guardar.  
**Resultado esperado:**
- PUT a `/security/guardians/{guardian}/profile`
- Segunda llamada actualiza (upsert)

**Test Dusk:** pendiente  
**Feature de origen:** role-profiles  
**Última verificación:** 2026-05-25 ✅ (tests S16 pasan) — ⚠️ ver HLZ-02

---

## UC-12 — Guardado con rol `guardian` sin registro Guardian en DB

**Precondición:** usuario tiene rol Spatie "Representante" pero no tiene fila en tabla `guardians`.  
**Pasos:** navegar a `/security/users/{id}/edit` (User 198).  
**Resultado esperado:**
- La página carga sin error JS
- `props.guardian` es `undefined`/ausente
- La sección 16 no aparece en el tab (porque `props.guardian` es undefined → `saveGuardianProfile` hace early return)
- `UF_TABS['guardian']` sigue mostrando el tab "Representante" pero la sección 16 guarda como no-op hasta que exista el registro

**Test Dusk:** pendiente  
**Feature de origen:** ad-hoc  
**Última verificación:** 2026-05-25 — ⚠️ ver HLZ-02

---

## UC-13 — S17 Perfil del personal (professor)

**Precondición:** usuario tiene rol `professor`; existe registro `Professor` vinculado.  
**Pasos:** tab "Profesional" → sección 17 → editar → guardar.  
**Resultado esperado:**
- PUT a `/academic/professors/{professor}/staff-profile`
- Segunda llamada actualiza (upsert)

**Test Dusk:** pendiente  
**Feature de origen:** role-profiles  
**Última verificación:** 2026-05-26 ✅ (tests S17 pasan; HLZ-05 resuelto en user-edit-catalog-ids; HLZ-20/HLZ-21 resueltos en user-edit-professor-s17-fix)

---

## UC-14 — Carga de página con rol `admin`

**Precondición:** usuario objetivo tiene rol Spatie `Admin` (mapea a `admin`); no tiene sub-registros de student/professor/guardian.  
**Pasos:** navegar a `/security/users/{id}/edit`  
**Resultado esperado:**
- `user.roles[0]` → `"admin"`
- Tabs visibles: Identidad [1,2,3,4], Salud [5], Documentos [7,6]
- Props `student`, `professor`, `guardian` están **ausentes**
- Secciones 8–17 no se renderizan (no están en ningún tab de admin)
- `savedSections` inicia con [1, 2]

**Test Dusk:** pendiente  
**Última verificación:** 2026-05-25 ✅ (análisis de código; user_id=177 con rol `Admin` carga correctamente)

---

## UC-15 — Guardado S04/S09/S16 persiste FK ids (fix HLZ-08)

**Precondición:** cualquier rol; S04 visible para admin/student/professor/guardian; S09 para student; S16 para guardian.  
**Pasos:** editar campos de dropdown (estado de nacimiento, religión, nivel educativo, etc.) → guardar.  
**Resultado esperado:**
- Los dropdowns muestran opciones de `catalogData.*` con IDs reales de DB
- Save persiste FK ids en las tablas: `birth_country_id`, `religion_id`, `native_language_id`, `institution_type_id`, `digital_level_id`, `marital_status_id`, `education_level_id`
- No hay pérdida silenciosa de datos

**Fix aplicado (user-edit-catalog-ids):**
- S04/S09/S16/S17: `catalogData` prop pasada desde `Edit.vue`
- Componentes usan `v-for` sobre `catalogData.*` con `:value="item.id"` (number)
- `useUserEditForm.ts`: handlers envían `_id` sufijados; `buildInitialFormData()` lee `_id` del resource
- `userForm.ts`: campos `*Id: number?` reemplazan strings deprecados

**Test automático:** `tests/Feature/Security/Acceptance/UserEditCatalogIdsAcceptanceTest.php` (T16, T17, T18)  
**Feature de origen:** user-edit-catalog-ids  
**Última verificación:** 2026-05-25 ✅ (acceptance tests pasan)

---

## UC-16 — Guardado S17 (professor) con FK ids — fix HLZ-05

**Precondición:** usuario tiene rol `professor`; existe registro `Professor` vinculado.  
**Pasos:** tab "Profesional" → sección 17 → editar → seleccionar tipo de contrato, dedicación, estatus → guardar.  
**Resultado esperado:**
- PUT a `/academic/professors/{professor}/staff-profile` con `contract_type_id`, `dedication_type_id`, `employment_status_id` como integers
- Guardado exitoso (200) — ya no devuelve 422
- Staff profile persiste con FK ids correctas

**Fix aplicado (user-edit-catalog-ids):**
- `saveStaffProfile()` envía `contract_type_id`, `dedication_type_id`, `employment_status_id` (integers) — elimina `contract_type`, `dedication_type`, `employment_status` (strings)
- `buildInitialFormData()` lee `p.contract_type_id`, `p.dedication_type_id`, `p.employment_status_id`
- S17 recibe `catalogData` con `contractTypes`, `dedicationTypes`, `employmentStatuses`

**Fix adicional (user-edit-professor-s17-fix):**
- `StoreStaffProfileRequest`: `exists:departments,id` → `exists:coordinations,id` (HLZ-20)
- `StaffProfile::coordinatedDepartment()`: `Department::class` → `Coordination::class` (HLZ-20)
- `buildInitialFormData()`: `hire_date`, `termination_date`, `coordinator_since` normalizados a `.substring(0, 10)` (HLZ-21)
- `StaffProfile.php`: PHPDoc documenta que la FK es `professor_id`, no `user_id` (HLZ-04)

**Test automático:** `tests/Feature/Security/Acceptance/UserEditCatalogIdsAcceptanceTest.php` (T19); `tests/Feature/UserEditProfessorS17Fix/Acceptance/StaffProfileS17AcceptanceTest.php` (UC-22–UC-25)  
**Feature de origen:** user-edit-catalog-ids; user-edit-professor-s17-fix  
**Última verificación:** 2026-05-26 ✅ (todos los acceptance tests pasan — 8/8)

---

## UC-17 — Admin con rol 'Admin' puede guardar S10, S13 y S16 (fix HLZ-06)

**Precondición:** usuario autenticado tiene únicamente el rol `Admin` (sin `Administrador`).  
**Pasos:** editar perfil de un estudiante → tab "Académico" → S10 → agregar idioma → guardar; o S13 beneficios; o editar perfil de un representante → S16.  
**Resultado esperado:**
- Guardado exitoso (admin tiene acceso completo vía `Gate::before` bypass)

**Fix aplicado (user-edit-auth-fix):**
- `StoreStudentLanguageRequest::authorize()` → `Gate::authorize('create', StudentLanguage::class)`
- `StoreStudentBenefitRequest::authorize()` → `Gate::authorize('create', StudentBenefit::class)`
- `StoreGuardianProfileRequest::authorize()` → `Gate::authorize('update', $guardian)` (con self-edit check para guardians)
- `StudentLanguageController::destroy()` → `Gate::authorize('delete', StudentLanguage::class)`
- `StudentBenefitController::destroy()` → `Gate::authorize('delete', StudentBenefit::class)`
- Políticas creadas: `StudentLanguagePolicy`, `GuardianPolicy`; `StudentBenefitPolicy` corregida
- `adminForEditUseCase()` ahora solo asigna `Admin` (bug de test corregido)

**Test Dusk:** `tests/Browser/Security/UserEditAuthFixTest.php` (3 tests)  
**Feature de origen:** user-edit-auth-fix  
**Última verificación:** 2026-05-25 ✅ (fix aplicado — acceptance tests + regression tests pasan)

---

## UC-18 — Rol `professor` sin sub-registro de `Professor` en DB

**Precondición:** usuario tiene rol Spatie `Profesor` pero no tiene fila en tabla `professors` (p.ej. user_id=2).  
**Pasos:** navegar a `/security/users/2/edit`  
**Resultado esperado:** misma situación que UC-12 para guardian — tab "Profesional" visible pero S17 no guarda.  
**Comportamiento actual:**
- `props.professor` → `undefined`
- `saveStaffProfile()`: `if (!props.professor) return` → early return silencioso
- S17 nunca puede marcarse como `complete`

**Test Dusk:** pendiente  
**Última verificación:** 2026-05-25 — ⚠️ ver HLZ-07

---

## UC-19 — Integridad de sub-registros al crear/actualizar usuario con rol

**Precondición:** admin autenticado con permiso `users.create` o `users.update`.  
**Pasos crear:** formulario nuevo usuario → asignar rol `Profesor` / `Estudiante` / `Representante` → guardar.  
**Pasos actualizar:** editar usuario con rol Admin → cambiar rol a `Profesor` → guardar.  
**Resultado esperado:**
- Se crea automáticamente la fila en `professors` / `students` / `guardians`
- `students.educational_level` queda en `university` (valor por defecto)
- La operación es idempotente: si la fila ya existe no se duplica (`firstOrCreate`)
- Para roles sin sub-registro (`Admin`, `Coordinador`) no se crean filas extras

**Fix aplicado (role-sub-record-integrity):**
- `CreateUserAction::ensureSubRecord()` llamado después de `syncRoles()`
- `UpdateUserAction::ensureSubRecord()` iterado sobre `$wrapper->getRoles()`
- Acepta cualquier combinación de roles sin duplicar registros

**Test automático:** `tests/Feature/RoleSubRecordIntegrity/Acceptance/RoleSubRecordAcceptanceTest.php` (AC-1, AC-2, AC-3, AC-4)  
`tests/Feature/Security/RoleSubRecordIntegrityTest.php` (T07–T12)  
**Feature de origen:** role-sub-record-integrity  
**Última verificación:** 2026-05-25 ✅

---

## UC-20 — Reparación de usuarios huérfanos existentes (`users:fix-orphan-records`)

**Precondición:** usuario admin con acceso a CLI; existen usuarios con rol pero sin sub-registro en DB.  
**Pasos:** `php artisan users:fix-orphan-records` (o `--dry-run` para previsualizar).  
**Resultado esperado:**
- Con `--dry-run`: muestra lista de usuarios afectados sin crear filas
- Sin flag: crea las filas faltantes para todos los roles afectados
- Reporta `Fixed N orphan record(s)` al terminar
- Idempotente: segunda ejecución reporta `Fixed 0 orphan record(s)`
- Aplicado en dev: user_id=2 (Profesor Demo), user_id=3 (Estudiante Demo), user_id=198 (Nuevo usuario Representante) — 3 filas creadas

**Test automático:** `tests/Feature/RoleSubRecordIntegrity/Acceptance/RoleSubRecordAcceptanceTest.php` (AC-5, AC-6)  
`tests/Feature/Security/RoleSubRecordIntegrityTest.php` (T11, T12)  
**Feature de origen:** role-sub-record-integrity  
**Última verificación:** 2026-05-25 ✅

---

## UC-21 — catalogData incluye todos los catálogos necesarios para S04/S09/S16/S17

**Precondición:** admin autenticado; seeders de catálogos ejecutados.  
**Pasos:** navegar a `/security/users/{id}/edit`  
**Resultado esperado:**
- `catalogData` incluye: `religions`, `institutionTypes`, `transferReasons`, `digitalLevels`, `educationLevels`, `maritalStatuses`, `contractTypes`, `dedicationTypes`, `employmentStatuses`, `departments`
- Cada entrada tiene `{ id: number, name: string }`
- Los counts no son cero: religions=8, contractTypes=4, dedicationTypes=3, employmentStatuses=4, maritalStatuses=5, educationLevels=6

**Test automático:** `tests/Feature/Security/Acceptance/UserEditCatalogIdsAcceptanceTest.php` (T01 — 3 tests)  
**Feature de origen:** user-edit-catalog-ids  
**Última verificación:** 2026-05-25 ✅

---

## UC-22 — Guardar S17 con is_coordinator=true y coordinated_department_id válido no devuelve 500 (fix HLZ-20)

**Precondición:** admin autenticado; `Professor` existente; fila en `coordinations` existente.  
**Pasos:** PUT `/academic/professors/{professor}/staff-profile` con `is_coordinator=true` y `coordinated_department_id` con ID de fila real en `coordinations`.  
**Resultado esperado:**
- HTTP 200 (no 500 por `SQLSTATE[42P01]: Undefined table: departments`)
- Fila en `staff_profiles` con `is_coordinator=true` y `coordinated_department_id` correcto

**Fix aplicado (user-edit-professor-s17-fix):**
- `StoreStaffProfileRequest::rules()`: `exists:departments,id` → `exists:coordinations,id`

**Test automático:** `tests/Feature/UserEditProfessorS17Fix/Acceptance/StaffProfileS17AcceptanceTest.php` (RF-01 — 2 tests)  
**Feature de origen:** user-edit-professor-s17-fix  
**Última verificación:** 2026-05-26 ✅

---

## UC-23 — Eager-load de coordinatedDepartment() en StaffProfile no lanza excepción PHP (fix HLZ-20)

**Precondición:** existe un `StaffProfile` con `coordinated_department_id` apuntando a una fila en `coordinations`.  
**Pasos:** `StaffProfile::with('coordinatedDepartment')->...->first()`.  
**Resultado esperado:**
- No lanza `Class "App\Models\Department" not found`
- Retorna instancia de `Coordination`
- `$profile->coordinatedDepartment->id` coincide con `coordinated_department_id` en DB

**Fix aplicado (user-edit-professor-s17-fix):**
- `StaffProfile::coordinatedDepartment()`: `Department::class` → `Coordination::class` + `use App\Models\Coordination` agregado

**Test automático:** `tests/Feature/UserEditProfessorS17Fix/Acceptance/StaffProfileS17AcceptanceTest.php` (RF-02 — 2 tests)  
**Feature de origen:** user-edit-professor-s17-fix  
**Última verificación:** 2026-05-26 ✅

---

## UC-24 — hire_date en prop Inertia de la página de edición llega en formato YYYY-MM-DD (fix HLZ-21)

**Precondición:** admin autenticado con permiso `users.update`; profesor con `hire_date` guardado en DB.  
**Pasos:** GET `/security/users/{professor_user}/edit`.  
**Resultado esperado:**
- `props.professor.staffProfile.hire_date` tiene exactamente 10 caracteres
- Formato `YYYY-MM-DD` (no ISO 8601 con componente de tiempo)
- Valor coincide con la fecha almacenada en DB

**Fix aplicado (user-edit-professor-s17-fix):**
- `buildInitialFormData()` aplica `.substring(0, 10)` a `hire_date`, `termination_date` y `coordinator_since`

**Test automático:** `tests/Feature/UserEditProfessorS17Fix/Acceptance/StaffProfileS17AcceptanceTest.php` (RF-03 — 2 tests)  
**Feature de origen:** user-edit-professor-s17-fix  
**Última verificación:** 2026-05-26 ✅

---

## UC-25 — Round-trip de S17 no destruye fechas que no fueron modificadas (fix HLZ-21)

**Precondición:** existe `StaffProfile` con `hire_date` y opcionalmente `coordinator_since` en DB.  
**Pasos:** PUT `/academic/professors/{professor}/staff-profile` enviando los mismos valores de fecha (tal como los devuelve el form tras un reload).  
**Resultado esperado:**
- `hire_date` en DB permanece igual antes y después del save
- `coordinator_since` en DB permanece igual antes y después del save

**Test automático:** `tests/Feature/UserEditProfessorS17Fix/Acceptance/StaffProfileS17AcceptanceTest.php` (RF-04 — 2 tests)  
**Feature de origen:** user-edit-professor-s17-fix  
**Última verificación:** 2026-05-26 ✅

---

## UC-26 — `UserSeeder` crea exactamente una fila en `professors` para el usuario con rol Profesor (fix HLZ-22)

**Precondición:** `migrate:fresh --seed` completo; roles y catálogos sembrados.  
**Pasos:** ejecutar `UserSeeder`; consultar `Professor::where('user_id', $profesorUser->id)->count()`.  
**Resultado esperado:**
- Existe exactamente 1 fila en `professors` para el usuario con rol Spatie `Profesor`
- `assertDatabaseHas('professors', ['user_id' => $user->id])` pasa

**Fix aplicado (user-seeder-subrecord-fix):**
- `UserSeeder::run()` llama a `ensureSubRecord()` / `firstOrCreate` después de `syncRoles()` para cada usuario con rol role-specific

**Test automático:** `tests/Feature/UserSeederSubrecordFix/Acceptance/UserSeederSubrecordAcceptanceTest.php` (RF-01)  
**Feature de origen:** user-seeder-subrecord-fix  
**Última verificación:** 2026-05-26 ✅

---

## UC-27 — `UserSeeder` crea exactamente una fila en `students` para el usuario con rol Estudiante (fix HLZ-22)

**Precondición:** `migrate:fresh --seed` completo; roles y catálogos sembrados.  
**Pasos:** ejecutar `UserSeeder`; consultar `Student::where('user_id', $estudianteUser->id)->count()`.  
**Resultado esperado:**
- Existe exactamente 1 fila en `students` para el usuario con rol Spatie `Estudiante`
- `assertDatabaseHas('students', ['user_id' => $user->id])` pasa

**Fix aplicado (user-seeder-subrecord-fix):**
- Mismo fix que UC-26 — `ensureSubRecord()` / `firstOrCreate` cubre el caso Estudiante

**Test automático:** `tests/Feature/UserSeederSubrecordFix/Acceptance/UserSeederSubrecordAcceptanceTest.php` (RF-02)  
**Feature de origen:** user-seeder-subrecord-fix  
**Última verificación:** 2026-05-26 ✅

---

## UC-28 — `UserSeeder` es idempotente — doble ejecución no duplica sub-registros (fix HLZ-22)

**Precondición:** `UserSeeder` ejecutado una vez; sub-registros ya existen.  
**Pasos:** ejecutar `UserSeeder` por segunda vez; contar filas en `professors` y `students`.  
**Resultado esperado:**
- `Professor::where('user_id', $user->id)->count()` → 1 (no 2)
- `Student::where('user_id', $user->id)->count()` → 1 (no 2)

**Fix aplicado (user-seeder-subrecord-fix):**
- Uso de `firstOrCreate` garantiza que la segunda ejecución es un no-op

**Test automático:** `tests/Feature/UserSeederSubrecordFix/Acceptance/UserSeederSubrecordAcceptanceTest.php` (RF-03 — 2 tests)  
**Feature de origen:** user-seeder-subrecord-fix  
**Última verificación:** 2026-05-26 ✅

---

## UC-29 — `UserSeeder` crea sub-registro cuando el usuario ya existe sin sub-registro (fix HLZ-22)

**Precondición:** usuario con rol `Profesor` o `Estudiante` ya existe en DB sin fila en `professors` / `students`.  
**Pasos:** ejecutar `UserSeeder`; verificar que el sub-registro fue creado.  
**Resultado esperado:**
- `Professor::where('user_id', $existingUser->id)->exists()` → true (para usuario pre-existente Profesor sin fila en professors)
- `Student::where('user_id', $existingUser->id)->exists()` → true (para usuario pre-existente Estudiante sin fila en students)

**Fix aplicado (user-seeder-subrecord-fix):**
- El seeder toma la ruta `updateOrCreate` / `firstOrCreate` tanto para usuarios nuevos como para usuarios ya existentes (update path con `syncRoles`)

**Test automático:** `tests/Feature/UserSeederSubrecordFix/Acceptance/UserSeederSubrecordAcceptanceTest.php` (RF-04 — 2 tests)  
**Feature de origen:** user-seeder-subrecord-fix  
**Última verificación:** 2026-05-26 ✅

---

## UC-30 — Admin guarda S04 sin cambiar religión — religion_id permanece en DB (fix HLZ-14)

**Precondición:** admin autenticado con rol `Admin`; usuario objetivo tiene `demographic_profiles.religion_id` con valor no nulo.  
**Pasos:** PUT `/security/users/{user}/demographic-profile` con payload que omite `religion_id` (comportamiento del frontend cuando el Resource no incluye el campo).  
**Resultado esperado:**
- HTTP 200
- `demographic_profiles.religion_id` permanece con su valor previo — **no se nullifica**

**Contexto:** antes del fix, `DemographicProfileResource` usaba `hasAnyRole(['Administrador', 'Coordinador'])` que excluía `Admin`. La ausencia del campo en el prop hacía que `buildInitialFormData()` asignara `religionId = undefined`, y `saveDemographic()` enviara `religion_id: null`. El backend (campo nullable) aceptaba el null y destruía el valor existente.

**Fix aplicado (user-edit-admin-fix):**
- `DemographicProfileResource` línea 15: `hasAnyRole(['Admin', 'Administrador', 'Coordinador'])` — `Admin` agregado a la lista

**Test automático:** `tests/Feature/UserEditAdminFix/Acceptance/DemographicProfileAdminAcceptanceTest.php` (RF-02 — 2 tests)  
**Feature de origen:** user-edit-admin-fix  
**Última verificación:** 2026-05-26 ✅ (2/2 tests pasan)

---

## UC-31 — Admin guarda S04 con nuevo religion_id — DB se actualiza correctamente (fix HLZ-14)

**Precondición:** admin autenticado con rol `Admin`; usuario objetivo tiene `demographic_profiles.religion_id` con valor inicial; existe al menos una segunda religión en `religions`.  
**Pasos:** PUT `/security/users/{user}/demographic-profile` con `religion_id` de una religión diferente.  
**Resultado esperado:**
- HTTP 200
- `demographic_profiles.religion_id` actualizado al nuevo valor
- El valor anterior ya no está en DB para ese `user_id`
- `DemographicProfileResource` en la respuesta incluye `religion_id` para el rol `Admin`

**Fix aplicado (user-edit-admin-fix):**
- Mismo fix que UC-30 — `Admin` incluido en `hasAnyRole()` permite que el Resource devuelva `religion_id` también en el response del PUT

**Test automático:** `tests/Feature/UserEditAdminFix/Acceptance/DemographicProfileAdminAcceptanceTest.php` (RF-01 — 2 tests, RF-03 — 2 tests)  
**Feature de origen:** user-edit-admin-fix  
**Última verificación:** 2026-05-26 ✅ (4/4 tests pasan)

---

## UC-35 — S01 no pre-llena campos de identidad extendida desde DB (bug HLZ-27)

**Precondición:** usuario con `document_number`, `birth_date`, `gender_id`, `phone_primary` con valores no nulos en `users`.  
**Pasos:** GET `/security/users/{user}/edit` → tab "Identidad" → sección 01.  
**Resultado esperado (correcto):** `docNumber`, `birthDate`, `gender`, `phone1` pre-llenados con los valores de DB.  
**Resultado actual (bug):** campos vacíos — `UserEditResource` no expone esas columnas; `buildInitialFormData()` no las lee.

**Raíz de causa:**
- `UserEditResource::toArray()`: solo expone `id`, `first_name`, `last_name`, `name`, `email`, `active`, `roles`, `created_at`
- `buildInitialFormData()` líneas 429–432: solo lee `firstName`, `lastName`, `email`, `roles` de `props.user`
- `userEdit.ts` tipo `User`: no declara `document_type_id`, `document_number`, `birth_date`, `gender_id`, `nationality_id`, `phone_primary`, `phone_secondary`, `profile_photo_url`

**Columnas afectadas en `users`:** `document_type_id` (int8 FK), `document_number`, `birth_date`, `gender_id` (int8 FK), `nationality_id` (int8 FK), `phone_primary`, `phone_secondary`, `profile_photo_url`

**Test Dusk:** pendiente  
**Feature de origen:** ad-hoc  
**Última verificación:** 2026-05-26 — ❌ bug confirmado por análisis de código

---

## UC-36 — S01 save no persiste campos de identidad extendida (bug HLZ-27)

**Precondición:** usuario edita `docNumber`, `birthDate`, `phone1` en S01 y hace click en guardar.  
**Pasos:** editar campos → guardar → PATCH `/security/users/{user}/identity` → recargar página.  
**Resultado esperado (correcto):** `document_number`, `birth_date`, `phone_primary` actualizados en DB; visibles al recargar.  
**Resultado actual (bug):** PATCH devuelve 200 (success), sección marca `complete`, pero DB no cambia — el handler solo envía `first_name`, `last_name`, `email`, `roles`.

**Raíz de causa:**
- `useUserEditForm.ts:336–343` handler `1`: envía exclusivamente `{ first_name, last_name, email, roles }`
- `UpdateUserRequest::rules()`: solo valida `first_name`, `last_name`, `email`, `roles`

**Test Dusk:** pendiente  
**Feature de origen:** ad-hoc  
**Última verificación:** 2026-05-26 — ❌ bug confirmado por análisis de código

---

## UC-37 — S01 handler incluye `email` y `roles` que no pertenecen al componente S01 (diseño confuso)

**Precondición:** cualquier usuario en modo edición.  
**Pasos:** S01 → guardar → observar payload del PATCH.  
**Resultado esperado (correcto):** S01 persiste solo los campos visibles en su UI (nombre, doc, fecha, teléfonos, género, nacionalidad, foto).  
**Resultado actual:** el handler envía `email` y `roles` que no tienen input en S01 — esos campos los gestiona S02 (credenciales) y el role picker respectivamente. Esto significa que guardar S01 actualiza `email` y `roles` usando los valores del form state, aunque el usuario no haya tocado esas secciones.

**Nota:** No es un bug bloqueante si los valores de email/roles no cambian; es un efecto secundario no documentado del diseño actual.

**Test Dusk:** pendiente  
**Feature de origen:** ad-hoc  
**Última verificación:** 2026-05-26 — ⚠️ diseño a revisar

---

## UC-38 — S01 campos de catálogo usan strings estáticos en vez de IDs de DB (bug HLZ-28)

**Precondición:** usuario selecciona tipo de documento 'V' y género 'f' en S01.  
**Pasos:** guardar S01 → verificar `document_type_id` y `gender_id` en DB.  
**Resultado esperado (correcto):** `document_type_id` = ID de la fila en `document_types` con `code = 'V'`; `gender_id` = ID de la fila en `genders` con `code = 'f'`.  
**Resultado actual (bug):** los campos no se envían al backend (ver UC-36); pero incluso si se enviaran, los valores string `'V'`/`'f'` son incompatibles con los FK enteros de DB — faltaría conversión `code → id`.

**Raíz de causa:**
- `UF_DOC_TYPES` usa keys string (`'V'`, `'E'`) — `users.document_type_id` es int8 FK a `document_types` (que tiene columna `code`)
- `UF_GENDERS` usa keys string (`'f'`, `'m'`) — `users.gender_id` es int8 FK a `genders` (que tiene columna `code`)
- `UF_COUNTRIES` usa keys string (`'ve'`, `'co'`) — `users.nationality_id` es int8 FK a `countries`
- No existe conversión `string code → integer id` en ninguna capa del sistema

**Decisión de diseño requerida:** al implementar el fix de S01, usar catálogos dinámicos desde `catalogData` (patrón ya adoptado en S04, S05, S17) — no catálogos estáticos con strings.

**Test Dusk:** pendiente  
**Feature de origen:** ad-hoc  
**Última verificación:** 2026-05-26 — ❌ bug confirmado por análisis de código + schema DB
