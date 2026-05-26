# Tasks — user-edit-catalog-ids

**Feature:** `user-edit-catalog-ids`
**Scope:** Full-stack — backend catálogos + tipos TS + composable + 4 componentes Vue + tests
**Depends on:** `user-edit-auth-fix` (comparten FormRequests y test base)

---

## Tasks

- [x] T01 — Backend `UserController::edit()`: agregar a `catalogData` los catálogos nuevos: `religions`, `institutionTypes`, `transferReasons`, `digitalLevels`, `educationLevels`, `maritalStatuses`, `contractTypes`, `dedicationTypes`, `employmentStatuses`, `departments`
- [x] T02 — `resources/js/types/userEdit.ts`: extender `UserFormCatalogData` con las 10 propiedades nuevas (`religions`, `institutionTypes`, `transferReasons`, `digitalLevels`, `educationLevels`, `maritalStatuses`, `contractTypes`, `dedicationTypes`, `employmentStatuses`, `departments`)
- [x] T03 — `resources/js/types/userForm.ts`: agregar campos `*Id` de tipo `number?` para S04 (`birthStateId`, `birthCountryId`, `nativeLangId`, `religionId`, `previousCountryId`)
- [x] T04 — `resources/js/types/userForm.ts`: agregar campos `*Id` de tipo `number?` para S09 (`prevInstitutionTypeId`, `transferReasonId`, `digitalLevelId`, `motherEduId`, `fatherEduId`)
- [x] T05 — `resources/js/types/userForm.ts`: agregar campos `*Id` de tipo `number?` para S16 (`guardianMaritalId`, `guardianEduId`) y S17 (`contractTypeId`, `dedicationTypeId`, `emplStatusId`, `coordDeptId`)
- [x] T06 — `UserFormS04Demographic.vue`: recibir `catalogData` como prop, reemplazar `UF_STATES_VE`/`UF_COUNTRIES`/`UF_LANGUAGES`/`UF_RELIGIONS` por selects con `catalogData.*` usando IDs como valores, cambiar `setField` a `birthStateId`/`birthCountryId`/`nativeLangId`/`religionId`
- [x] T07 — `UserFormS09PrevEducation.vue`: recibir `catalogData` como prop, reemplazar `UF_INSTITUTION_TYPES`/`UF_TRANSFER_REASONS`/`UF_DIGITAL_LEVELS`/`UF_EDU_LEVELS` por selects con `catalogData.*` usando IDs, cambiar `setField` a campos `*Id`
- [x] T08 — `UserFormS16GuardianProfile.vue`: recibir `catalogData` como prop, reemplazar `UF_MARITAL`/`UF_EDU_LEVELS` por selects con `catalogData.maritalStatuses`/`catalogData.educationLevels` usando IDs, cambiar `setField` a `guardianMaritalId`/`guardianEduId`
- [x] T09 — `UserFormS17ProfessorProfile.vue`: recibir `catalogData` como prop, reemplazar `UF_CONTRACT`/`UF_DEDICATION`/`UF_EMPL_STATUS`/`UF_DEPARTMENTS` por selects con `catalogData.*` usando IDs, cambiar `setField` a `contractTypeId`/`dedicationTypeId`/`emplStatusId`/`coordDeptId`
- [x] T10 — `useUserEditForm.ts` — `buildInitialFormData()`: leer `_id` desde el resource en lugar de `.name` para los 16 campos de S04/S09/S16/S17 afectados
- [x] T11 — `useUserEditForm.ts` — `saveDemographic()`: actualizar payload para enviar `birth_state_id`, `birth_country_id`, `native_language_id`, `religion_id`, `previous_country_id` (IDs numéricos, claves con sufijo `_id`)
- [x] T12 — `useUserEditForm.ts` — `saveBackground()`: actualizar payload para enviar `institution_type_id`, `transfer_reason_id`, `digital_level_id`, `mother_education_level_id`, `father_education_level_id`
- [x] T13 — `useUserEditForm.ts` — `saveGuardianProfile()`: actualizar payload para enviar `marital_status_id`, `education_level_id`
- [x] T14 — `useUserEditForm.ts` — `saveStaffProfile()`: actualizar payload para enviar `contract_type_id`, `dedication_type_id`, `employment_status_id`, `coordinated_department_id`; eliminar los campos sin `_id` (`contract_type`, `dedication_type`, `employment_status`, `coordinated_department`)
- [x] T15 — `resources/js/pages/security/Users/Edit.vue`: verificar que `catalogData` se pasa como prop a S04, S09, S16, S17 (igual que ya se pasa a S03, S10, S13)
- [x] T16 — Test S04: agregar assertion en `UserEditUseCaseTest.php` que guarda `birth_country_id` y `religion_id` con IDs reales y verifica que `demographic_profiles` los persiste
- [x] T17 — Test S09: agregar assertion que guarda `institution_type_id` y `digital_level_id` y verifica que `student_backgrounds` los persiste
- [x] T18 — Test S16: agregar assertion que guarda `marital_status_id` y `education_level_id` y verifica que `guardian_profiles` los persiste
- [x] T19 — Test S17: actualizar el test existente de S17 staff profile para confirmar que guarda con IDs correctos y retorna 200 (no 422)
- [x] T20 — Ejecutar `vendor/bin/sail artisan test --compact --filter=UserEditUseCaseTest` — todos los tests pasan incluyendo S17
- [x] T21 — `vendor/bin/sail npm run build` — sin errores TypeScript
- [x] T22 — `vendor/bin/sail bin pint --dirty --format agent` sobre `UserController.php`

---

## Checkpoints

- **CHECKPOINT A: después de T05** ✅ — `UserFormCatalogData` y `UserFormData` tienen todos los campos `*Id` nuevos. Build TypeScript pasa.
- **CHECKPOINT B: después de T09** ✅ — Los 4 componentes Vue reciben `catalogData` como prop y usan IDs. Sin errores de consola.
- **CHECKPOINT C: después de T14** ✅ — Los 4 handlers en el composable envían IDs con nombres de campo correctos (`_id` sufijo).
- **CHECKPOINT D: después de T20** ✅ — Todos los tests pasan. En particular S17 ya no devuelve 422.
