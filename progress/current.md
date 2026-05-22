# Feature completada

**Feature:** `05-multi-role-login`
**Plan:** `specs/multi-role-login/tasks.md`
**Estado:** COMPLETADA — todas las 7 tasks implementadas y pasando

## Tareas completadas

- [x] Task 1 — Middleware `EnsureRole`
- [x] Task 2 — Trait `ResolvesLoginRedirect` + fix `LoginResponse` y `TwoFactorLoginResponse`
- [x] Task 3 — Rutas de portales por rol
- [x] Task 4 — Controladores de dashboard por rol
- [x] Task 5 — Feature tests de login multi-rol y acceso a portales (12/12)
- [x] Task 6 — Páginas Vue de dashboard por rol
- [x] Task 7 — `AppSidebar.vue` — navegación contextual por rol

## Resumen de la feature

- `EnsureRole` middleware con alias `role` — protege portales con Spatie `hasAnyRole()`
- `ResolvesLoginRedirect` trait compartido entre `LoginResponse` y `TwoFactorLoginResponse`
- Admin (con team) → `/{team}/dashboard`, Profesor → `/professor/dashboard`, Estudiante → `/student/dashboard`, Representante → `/guardian/dashboard`
- 3 portal dashboards (controllers + Vue pages) con greeting básico
- `AppSidebar.vue` refactorizado: navGroups por rol (`portalRole` computed)
- `AppHeader.vue` actualizado con `dashboardUrl` por rol
- Enrollment routes endurecidas: `role:Estudiante,Representante`
- Suite: 12 tests nuevos + suite completa pasando

## Feature anterior completada

**Feature:** `04-testing-vitest-dusk`
Task 8 completada: Dusk academic tests + feature_list.json marcado como completado.
- Vitest configurado con jsdom, @vue/test-utils, coverage v8
- Dusk instalado con Selenium container en `http://selenium:4444`
- Tests Dusk: Auth, Dashboard, Enrollment, Academic (Careers + Subjects + Sections)
