# Tasks — user-edit-auth-fix

**Feature:** `user-edit-auth-fix`
**Scope:** Backend PHP + tests — sin frontend, sin migraciones
**Depends on:** ninguno

---

## Tasks

- [x] T01 — Corregir `StoreStudentLanguageRequest::authorize()`: reemplazar `hasAnyRole(['Administrador', 'Coordinador'])` por `Gate::authorize('create', StudentLanguage::class)`
- [x] T02 — Corregir `StoreStudentBenefitRequest::authorize()`: reemplazar `hasAnyRole(['Administrador', 'Coordinador'])` por `Gate::authorize('create', StudentBenefit::class)` (o modelo equivalente del pivote)
- [x] T03 — Corregir `StoreGuardianProfileRequest::authorize()`: reemplazar la condición `hasAnyRole(['Administrador', 'Coordinador'])` — preservar el check del propio guardian, usar `Gate::authorize()` para admins y coordinadores
- [x] T04 — Corregir `adminForEditUseCase()` en `UserEditUseCaseTest.php`: eliminar `$user->assignRole('Administrador')` — solo asignar `Admin`
- [x] T05 — Agregar test de regresión S10: `S10 languages: admin con solo rol Admin puede agregar idioma` — verificar que el guardado retorna 200
- [x] T06 — Agregar test de regresión S13: `S13 benefits: admin con solo rol Admin puede adjuntar beneficio` — verificar que el guardado retorna 200
- [x] T07 — Agregar test de regresión S16: `S16 guardian profile: admin con solo rol Admin puede guardar perfil de representante` — verificar que el guardado retorna 200
- [x] T08 — Ejecutar `vendor/bin/sail artisan test --compact --filter=UserEditUseCaseTest` — todos los tests pasan
- [x] T09 — `vendor/bin/sail bin pint --dirty --format agent` sobre los tres FormRequests modificados

---

## Checkpoints

- **CHECKPOINT A: después de T03** — los tres FormRequests tienen `Gate::authorize()` en lugar de `hasAnyRole()`. Revisar que `StoreGuardianProfileRequest` preserva el acceso del propio guardian.
- **CHECKPOINT B: después de T07** — los nuevos tests de regresión están escritos y reflejan el comportamiento correcto (admin con solo `Admin` puede guardar).
- **CHECKPOINT C: después de T08** — `vendor/bin/sail artisan test --compact --filter=UserEditUseCaseTest` pasa sin errores. El test de S10, S13 y S16 con admin de solo-un-rol pasan.
