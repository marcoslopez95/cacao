# Tasks — User form HLZ fixes (feature 23)

- [x] Task 1 — HLZ-54: `.topbar-menu-btn` into `@layer components` (RED: "T22 desktop (1440px)")
- [x] Task 2 — HLZ-52: fix the 7 vue-tsc errors of the user form module without `any`
- [x] Task 3 — HLZ-53: scroll spy tracks all intersecting sections + bottom-of-page rule (RED: "scroll spy por tab")
- [x] Task 4 — HLZ-51: autosave watcher in `useUserEditForm` + `UserFormAutosave` with "Guardado hace Xs" (RED: 2 "SPEC autosave" tests)
- [x] Task 5 — HLZ-50: Create uses Header/Footer/useUserFormPage; pill only in Create; 50% gate (RED: "SPEC pill", "SPEC Crear usuario")
- [x] Task 6 — Verification: Dusk 14/14, Feature/Security, existing Dusk Security, build, types:check; update backlog and user-form-views Task 22

Notes:
- Task 5 test adjustment: "T22 SPEC pill cambiar" ran on the Edit page and expected step 0; the spec only defines the pill for creation mode, so the test now runs on `/security/users/create` (assertions unchanged) and a new test asserts Edit has no "cambiar" button. The "Cambiar rol" Create test now clicks `.uf-role-pill-change` (the standalone button was removed).
