# Casos de uso — Perfil propio (`/settings/profile`)

**Dominio:** settings
**Flujo:** editar perfil propio (self-service)
**Última revisión:** 2026-09-13 (22-qa-round3-followup-fixes)
**Auditor:** tester (task-gate)

---

## Contexto

`/settings/profile` es la página de configuración del propio usuario autenticado (cualquier rol:
admin, professor, student, guardian). Distinto del flujo `/security/users/{id}/edit` (un admin
editando a otro usuario) documentado en `specs/qa/security/user-edit.md`.

`ProfileController::edit()` pasa `user` como prop explícita vía `ProfileUserResource` (id, name,
email, email_verified_at). `Profile.vue` recibe `user` con `defineProps` — ya no lee
`usePage().props.auth.user` en el cuerpo de la página (fix HLZ-47).

---

## UC-QA-03 — Cualquier rol ve su perfil prellenado en `/settings/profile`

**Precondición:** usuario autenticado de cualquier rol (admin, professor, student, guardian), con
`name`/`email` reales en DB.

**Pasos:**
1. Login.
2. Navegar a `/settings/profile`.
3. Verificar los inputs "Nombre" (`#name`) y "Correo electrónico" (`#email`).

**Resultado esperado:**
- Ambos inputs muestran los valores reales del usuario autenticado (no vacíos), en desktop y
  mobile.
- El badge "Correo verificado." (`.sp-verif.ok`) permanece visible cuando `email_verified_at` no es
  null — no desaparece por este fix.

**Test Dusk:** `tests/Browser/Settings/QaRound3ProfilePrefillTest.php` — 5 tests:
- admin (desktop) ✅ PASS
- professor (desktop) ✅ PASS
- student (desktop) ✅ PASS
- guardian (desktop) ✅ PASS
- student (mobile 375×812) ✅ PASS

**Test Feature (backend, complementario):**
`tests/Feature/QaRound3FollowupFixes/Acceptance/ProfileEditPropsAcceptanceTest.php` — 5 tests, los
4 roles + verificación de `email_verified_at` presente en el prop.

**Feature de origen:** 22-qa-round3-followup-fixes (HLZ-47)
**Última verificación:** 2026-09-13 ✅
