# HLZ-50..54 — User form (Task 22 of user-form-views) fixes

Source: `specs/qa/backlog.md` (HLZ-50 to HLZ-54), found by `tests/Browser/Security/UserFormTask22VisualVerificationTest.php` (6 of 14 tests red).

## Functional requirements

- RF-01 (HLZ-54): the topbar hamburger (`.topbar-menu-btn`) is hidden at >= lg (1024px) and visible below it.
- RF-02 (HLZ-52): `vue-tsc` reports no errors in the user form module (components/security/UserForm, useUserEditForm, useUserFormPage). No `any`.
- RF-03 (HLZ-53): the scroll spy highlights the last section of every tab when jumping to it from the side-nav.
- RF-04 (HLZ-51): changing any field of the user form shows "Guardando…" and then "Guardado hace Xs" (relative time updates). Explicit section save also shows "Guardado hace Xs".
- RF-05 (HLZ-50): `/security/users/create` uses `UserFormHeader` (pill "cambiar" returns to step 0) and `UserFormFooter` (X% completo, "Crear usuario" disabled below 50%). In Edit the pill is not shown (a role cannot be swapped for an existing user; it used to navigate to the index, which was misleading).

## Out of scope

- Rendering S03-S17 in Create (Create only collects S01+S02; the store endpoint only accepts those). Completion in Create is computed over the sections Create renders.
- Real draft persistence ("Guardar borrador" has no backend; the button is not rendered in Create).
- The 19 unrelated `types:check` errors (enrollment, scheduling, Register, tests/js, ...).
