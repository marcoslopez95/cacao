# Requirements — user-edit-catalog-ids

**Feature ID:** `user-edit-catalog-ids`
**QA Hallazgos:** HLZ-05, HLZ-08
**Prioridad:** ALTA (S17 bloqueante) / MEDIA-ALTA (S04, S09, S16 pérdida silenciosa)

---

## Problema

Cuatro secciones del formulario de edición de usuario usan arrays de strings hardcodeados en `userFormCatalogs.ts` (p.ej. `UF_CONTRACT`, `UF_MARITAL`, `UF_STATES_VE`) y los envían como strings al backend. El backend espera IDs de FK de la DB.

### S17 (BLOQUEANTE)

`saveStaffProfile()` en `useUserEditForm.ts` envía:

```typescript
contract_type:    formData.contract    // string, e.g. "Tiempo completo"
dedication_type:  formData.dedication  // string
employment_status: formData.emplStatus // string
coordinated_department: formData.coordDept // string
```

`StoreStaffProfileRequest` exige:

```php
'contract_type_id'    => ['required', 'integer', 'exists:contract_types,id'],
'dedication_type_id'  => ['required', 'integer', 'exists:dedication_types,id'],
'employment_status_id' => ['required', 'integer', 'exists:employment_statuses,id'],
'coordinated_department_id' => [..., 'required_if:is_coordinator,true'],
```

**Resultado:** guardar S17 siempre devuelve 422. La sección nunca puede completarse.

### S04 (pérdida silenciosa)

`UserFormS04Demographic.vue` usa `UF_STATES_VE` (strings), `UF_COUNTRIES` (strings con key 've'), `UF_LANGUAGES` (strings), `UF_RELIGIONS` (strings). Los campos de backend son:

```php
'birth_state_id'     => ['nullable', 'integer', 'exists:states,id'],
'birth_country_id'   => ['nullable', 'integer', 'exists:countries,id'],
'native_language_id' => ['nullable', 'integer', 'exists:languages,id'],
'religion_id'        => ['nullable', 'integer', 'exists:religions,id'],
```

Como son `nullable`, el save pasa con 200 pero los valores de catálogo no persisten.

**Nota:** `saveDemographic()` ya envía `birth_state` / `birth_country` / `native_language` / `religion` (strings). El FormRequest espera `birth_state_id` etc. Doble problema: nombre de campo incorrecto + tipo incorrecto.

### S09 (pérdida silenciosa)

`UserFormS09PrevEducation.vue` usa `UF_INSTITUTION_TYPES`, `UF_TRANSFER_REASONS`, `UF_DIGITAL_LEVELS`, `UF_EDU_LEVELS` (todos strings). Los campos de backend son:

```php
'institution_type_id'       => ['nullable', 'integer', 'exists:institution_types,id'],
'transfer_reason_id'        => ['nullable', 'integer', 'exists:transfer_reasons,id'],
'digital_level_id'          => ['nullable', 'integer', 'exists:digital_levels,id'],
'mother_education_level_id' => ['nullable', 'integer', 'exists:education_levels,id'],
'father_education_level_id' => ['nullable', 'integer', 'exists:education_levels,id'],
```

`saveBackground()` envía `institution_type`, `transfer_reason`, `digital_level`, `mother_education_level`, `father_education_level` — nombres incorrectos + strings en lugar de IDs. Save pasa 200 pero los valores no persisten.

### S16 (pérdida silenciosa)

`UserFormS16GuardianProfile.vue` usa `UF_MARITAL` y `UF_EDU_LEVELS` (strings). El backend (`StoreGuardianProfileRequest`) exige:

```php
'marital_status_id'  => ['nullable', 'integer', Rule::exists('marital_statuses', 'id')],
'education_level_id' => ['nullable', 'integer', Rule::exists('education_levels', 'id')],
```

`saveGuardianProfile()` envía `marital_status` y `education_level` (strings, claves incorrectas). Save pasa 200 pero no persiste.

---

## Contexto: patrón correcto (S10, S13, S03 ya migrados)

S10 (idiomas) y S13 (beneficios) ya siguen el patrón correcto: el backend inyecta catálogos como objetos `{id, name}` en `catalogData`, los componentes usan `v-for="l in catalogData.languages"` con `:value="l.id"`, y el composable envía IDs.

S03 (dirección) también usa `catalogData.countries` y `catalogData.states` con IDs.

---

## Precondiciones

- Las tablas de catálogo existen y tienen datos (del seeder de catálogos)
- `StoreStaffProfileRequest`, `StoreDemographicProfileRequest`, `StoreStudentBackgroundRequest`, `StoreGuardianProfileRequest` ya tienen las validaciones con `_id` correctas

---

## Criterios de aceptación

1. Guardar S17 con datos válidos → 200, registro en `staff_profiles` con FK ids correctas
2. Guardar S04 con país/estado/religión/idioma → 200, `demographic_profiles` persiste `birth_country_id`, `birth_state_id`, `religion_id`, `native_language_id`
3. Guardar S09 con tipo de institución/motivo de traslado/nivel digital/educación padres → 200, `student_backgrounds` persiste con FK ids
4. Guardar S16 con estado civil / nivel educativo → 200, `guardian_profiles` persiste `marital_status_id`, `education_level_id`
5. Tests específicos en `UserEditUseCaseTest.php` confirman que cada sección persiste FK ids correctas

---

## Archivos principales afectados

**Backend:**
- `app/Http/Controllers/Security/UserController.php` — inyectar nuevos catálogos en `catalogData`

**Tipos TypeScript:**
- `resources/js/types/userEdit.ts` — extender `UserFormCatalogData`
- `resources/js/types/userForm.ts` — cambiar campos string a ID para S04, S09, S16, S17

**Composable:**
- `resources/js/composables/forms/useUserEditForm.ts` — actualizar payloads de `saveDemographic()`, `saveBackground()`, `saveGuardianProfile()`, `saveStaffProfile()` + actualizar `buildInitialFormData()` para leer IDs

**Componentes Vue:**
- `resources/js/components/security/UserForm/UserFormS04Demographic.vue`
- `resources/js/components/security/UserForm/UserFormS09PrevEducation.vue`
- `resources/js/components/security/UserForm/UserFormS16GuardianProfile.vue`
- `resources/js/components/security/UserForm/UserFormS17ProfessorProfile.vue`

**Tests:**
- `tests/Feature/Security/UserEditUseCaseTest.php`
