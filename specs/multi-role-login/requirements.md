# Requirements — Multi-Role Login

**Feature:** `05-multi-role-login`

---

## Problema

`LoginResponse` y `TwoFactorLoginResponse` requieren que el usuario tenga un team asignado.
Los usuarios con rol `Profesor`, `Estudiante` o `Representante` no tienen team → reciben 403 al iniciar sesión.

---

## Roles existentes (Spatie, nombres exactos en DB)

| Nombre en DB | Portal destino |
|---|---|
| `Admin` | `/{team}/dashboard` (flujo actual con Teams) |
| `Profesor` | `/professor/dashboard` |
| `Estudiante` | `/student/dashboard` |
| `Representante` | `/guardian/dashboard` |
| `Coordinador de Area` | `/professor/dashboard` (mismo portal que Profesor) |

---

## Requisitos funcionales

### RF-01 Login por rol
- Después de autenticarse, cada usuario es redirigido al portal correspondiente a su rol
- Admin: mantiene el flujo actual basado en Teams
- Profesor y Coordinador de Area: redirigen a `/professor/dashboard`
- Estudiante: redirige a `/student/dashboard`
- Representante: redirige a `/guardian/dashboard`
- Usuario sin rol reconocido y sin team: redirige a `/` (welcome)

### RF-02 Portales protegidos por rol
- `/professor/*` solo accesible para usuarios con rol `Profesor` o `Coordinador de Area`
- `/student/*` solo accesible para usuarios con rol `Estudiante`
- `/guardian/*` solo accesible para usuarios con rol `Representante`
- Intento de acceso con rol incorrecto → 403

### RF-03 Dashboard básico por portal
- Cada portal tiene un dashboard mínimo: saludo con nombre del usuario + mensaje de bienvenida
- Comparten el layout existente (`AppLayout`)

### RF-04 Navegación contextual
- La barra lateral muestra solo los grupos relevantes para el rol del usuario autenticado
- Admin: navegación completa actual (sin cambios)
- Profesor: Dashboard + Mi inscripción (solo si rol incluye permiso) + Mi cuenta
- Estudiante: Dashboard + Inscripciones → Mi inscripción + Mi cuenta
- Representante: Dashboard + Mi cuenta
- La inscripción deja de ser visible para todos — pasa a Estudiante únicamente

---

## Requisitos no funcionales

- Lógica de redirección compartida entre `LoginResponse` y `TwoFactorLoginResponse`
- Middleware `EnsureRole` reutilizable para proteger cualquier grupo de rutas futuro
- Rutas frontend generadas por Wayfinder — sin URLs hardcodeadas
- Tests de feature para cada flujo de redirección y acceso

---

## Fuera de scope

- Páginas de horario por rol (feature separada)
- Dashboards con estadísticas reales (future)
- Portal del profesor con listado de secciones (future)
