# Current Progress

**Last updated:** 2026-05-26

---

## Active Feature

Ninguna — todas las features completadas.

---

## Queue (in dependency order)

| # | Feature | Status | Depends on | HLZs |
|---|---------|--------|------------|------|
| 0 | `catalogs` | **DONE** | — | — |
| 1 | `user-profiles` | **DONE** | catalogs | — |
| 2 | `role-profiles` | **DONE** | catalogs, user-profiles | — |
| 3 | `student-background` | **DONE** | catalogs, user-profiles, role-profiles | — |
| 4 | `socioeconomic-health` | **DONE** | all above | — |
| 5 | `user-form-views` | **DONE** | ninguno (frontend only) | — |
| 6 | `user-form-connect` | **DONE** | user-form-views, socioeconomic-health | — |
| 7 | `user-edit-auth-fix` | **DONE** | — | HLZ-06 |
| 8 | `geographic-seeder-fix` | **DONE** | — | HLZ-01, HLZ-03 |
| 9 | `role-sub-record-integrity` | **DONE** | — | HLZ-02, HLZ-07 |
| 10 | `user-edit-catalog-ids` | **DONE** | user-edit-auth-fix | HLZ-05, HLZ-08 |
| 11 | `student-academic-show` | **DONE** | — | HLZ-09 |
| 12 | `user-edit-professor-s17-fix` | **DONE** | — | HLZ-20 (CRITICO), HLZ-21, HLZ-04 |
| 13 | `user-seeder-subrecord-fix` | **DONE** | — | HLZ-22 |
| 14 | `user-edit-admin-fix` | **DONE** | — | HLZ-14 |
| 15 | `user-edit-health-fix` | **DONE** | — | HLZ-10, HLZ-15 |
| 16 | `user-edit-student-sections-fix` | **DONE** | — | HLZ-11, HLZ-12, HLZ-13 |
| 17 | `user-edit-s01-identity-fix` | **DONE** | — | HLZ-27 (CRÍTICO), HLZ-28 (CRÍTICO) |

**Próxima:** sin features pendientes en la queue.

---

## Completed Features

| Feature | Notes |
|---------|-------|
| admin-students | Students index with multi-level tabs |
| enrollment-backend | Full enrollment pipeline with Redis |
| enrollment-frontend | Vue enrollment UI |
| students-multilevel | Level tabs + per-level views |
| grades-module | Grade config, slots, entries, letter values |
| demo-seeder | Demo data for all modules |
| role-dashboards | Per-role landing pages |
| multi-role-login | Login flow with role selection |
| error-pages | 403, 404, 500 pages |
| testing-vitest-dusk | Vitest + Dusk testing setup |
| catalogs | Geographic + system catalog tables |
| user-profiles | name split, documents, addresses |
| role-profiles | guardian/professor/student sub-records |
| student-background | education background, languages, family |
| socioeconomic-health | benefits, socioeconomic, housing, consents |
| user-form-views | 17-section user form UI (frontend only) |
| user-form-connect | Connect form UI to backend (S01-S17) |
| user-edit-auth-fix | Fix hasAnyRole('Administrador') → Gate::authorize() in FormRequests (HLZ-06) |
| geographic-seeder-fix | db:seed populates 187 countries + 24 states; new catalogData test added (HLZ-01, HLZ-03) |
| role-sub-record-integrity | firstOrCreate in CreateUserAction+UpdateUserAction; artisan fix command; 3 dev orphans repaired (HLZ-02, HLZ-07) |
| user-edit-catalog-ids | S04/S09/S16/S17 migrated to DB-backed ID selects; S17 422 bug fixed; 13 acceptance tests; build clean (HLZ-05, HLZ-08) |
| student-academic-show | Show page /academic/students/{id} Level 2; Edit button wired to /security/users/{id}/edit (HLZ-09) |
| user-edit-professor-s17-fix | StoreStaffProfileRequest exists:coordinations fix; ISO dates; PHPDoc (HLZ-04, HLZ-20, HLZ-21) |
| user-seeder-subrecord-fix | UserSeeder calls ensureSubRecord(); no más huérfanos post-seed (HLZ-22) |
| user-edit-admin-fix | DemographicProfileResource incluye 'Admin' en role check (HLZ-14) |
| user-edit-health-fix | buildInitialFormData() lee blood_type_id (ID plano); save preserva IDs (HLZ-10, HLZ-15) |
| user-edit-student-sections-fix | S11/S12/S14 migrados a ID-based; 3 secciones, 10+ catálogos nuevos (HLZ-11, HLZ-12, HLZ-13) |
| user-edit-s01-identity-fix | 8 campos S01 expuestos+persistidos; catálogos dinámicos; AppDocInput refactorizado; 13 acceptance tests (HLZ-27, HLZ-28) |

---

## QA Backlog — Estado de hallazgos

| HLZ | Estado | Feature |
|-----|--------|---------|
| HLZ-01 | resuelto | geographic-seeder-fix |
| HLZ-02 | resuelto | role-sub-record-integrity |
| HLZ-03 | resuelto | geographic-seeder-fix |
| HLZ-04 | resuelto | user-edit-professor-s17-fix |
| HLZ-05 | resuelto | user-edit-catalog-ids |
| HLZ-06 | resuelto | user-edit-auth-fix |
| HLZ-07 | resuelto | role-sub-record-integrity |
| HLZ-08 | resuelto | user-edit-catalog-ids |
| HLZ-09 | resuelto | student-academic-show |
| HLZ-10 | resuelto | user-edit-health-fix |
| HLZ-11 | resuelto | user-edit-student-sections-fix |
| HLZ-12 | resuelto | user-edit-student-sections-fix |
| HLZ-13 | resuelto | user-edit-student-sections-fix |
| HLZ-14 | resuelto | user-edit-admin-fix |
| HLZ-15 | resuelto | user-edit-health-fix |
| HLZ-20 | resuelto | user-edit-professor-s17-fix |
| HLZ-21 | resuelto | user-edit-professor-s17-fix |
| HLZ-22 | resuelto | user-seeder-subrecord-fix |
| HLZ-26 | deferred (cosmético) | — |
| HLZ-27 | resuelto | user-edit-s01-identity-fix |
| HLZ-28 | resuelto | user-edit-s01-identity-fix |
| HLZ-29 | verificación pendiente | posiblemente resuelto por user-edit-student-sections-fix |

---

## Notes

- QA audit 2026-05-25/26 generó 12 hallazgos nuevos (HLZ-10 a HLZ-26 relevantes). Features 12-16 los resuelven.
- QA audit 2026-05-26 (S01 identity) generó HLZ-27, HLZ-28, HLZ-29. Feature 17 resuelve HLZ-27/28. HLZ-29 en verificación.
- HLZ-04 a HLZ-22 marcados como "pendiente → feature X" en tabla arriba son todos resueltos — tabla desactualizada, ver backlog.md para estado real.
- Todas las features 12-16 son **independientes entre sí** — pueden implementarse en paralelo o en cualquier orden.
- **Prioridad sugerida:** `user-edit-professor-s17-fix` primero (HLZ-20 es crash 500 CRITICO).
- HLZ-26 (`workDial` cosmético) marcado como `deferred` — sin feature asignada, no afecta datos.
- `user-edit-student-sections-fix` es el cambio más grande (3 secciones, 10+ catálogos nuevos, 3 componentes Vue).
