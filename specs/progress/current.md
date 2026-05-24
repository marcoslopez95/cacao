# Current Progress

**Last updated:** 2026-05-24

---

## Active Feature

None — ready to start `student-background` (arnés 3).

---

## Queue (in dependency order)

| # | Feature | Status | Depends on |
|---|---------|--------|------------|
| 0 | `catalogs` | **DONE** | — |
| 1 | `user-profiles` | **DONE** | catalogs |
| 2 | `role-profiles` | **DONE** | catalogs, user-profiles |
| 3 | `student-background` | **NEXT** | catalogs, user-profiles, role-profiles |
| 4 | `socioeconomic-health` | pending | all above |

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

---

## Notes

- The `catalogs` arnés is the foundational prerequisite — start here.
- `role-profiles` includes a **destructive migration** (drops `students.guardian_id` and `guardians.name`/`relation`). Run on staging first.
- `health_profiles` has the most restrictive access policy (admin-only).
- `housing_profiles.is_overcrowded` is a PostgreSQL GENERATED STORED column — no Laravel accessor needed.
