# Tasks — guardian-enrollment-entry

- [x] T1 — Frontend: agregar botón CTA "Inscribir / Continuar inscripción / Ver inscripción" por card de estudiante en `guardian/Dashboard.vue`, enlazando a `enrollment.index?student_id={id}` (`dusk="guardian-enroll-btn"`)
- [x] T2 — Frontend: breadcrumb dinámico con el nombre del estudiante en `enrollment/Index.vue` (en vez del texto estático "Estudiante")
- [x] T3 — Test de browser Pest (`tests/Browser/Guardian/GuardianEnrollmentTest.php`), mismo patrón que `EnrollmentFlowTest.php`. **No ejecutable en este sandbox**: Selenium no llega a la app (`net::ERR_CONNECTION_REFUSED`) — falla preexistente del entorno, confirmada porque `EnrollmentFlowTest.php` (ya existente, sin tocar) falla idéntico. No es una regresión de este cambio.
- [x] T4 — `npm run types:check` sin errores nuevos en los archivos tocados; `vendor/bin/sail bin pint --dirty --format agent` → pass; `--filter=Enrollment` (54 passed) y `--filter=Guardian` (66 passed) sin regresión
