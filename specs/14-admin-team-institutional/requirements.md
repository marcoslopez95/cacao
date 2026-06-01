# Admin Team Institucional — Requirements

**Feature:** 14-admin-team-institutional
**Fecha:** 2026-05-31

---

## Contexto

El sistema usa Jetstream Teams como mecanismo de resolución de portal post-login.
Actualmente cada usuario tiene un *personal team* y el redirect post-login lo resuelve
`ResolvesLoginRedirect` buscando el personal team del usuario. Para el rol Admin esto crea
un acoplamiento implícito: el portal `/admin/dashboard` queda atado a un team personal que
no tiene semántica institucional.

Este feature introduce el **team institucional `admin`** (slug=`admin`, `is_personal=false`)
como pieza de infraestructura. Garantiza que todo usuario Admin sea miembro del team,
que el redirect post-login lo resuelva vía ese slug, y que el team no pueda eliminarse
desde la UI.

---

## Alcance

### Incluye
- `AdminTeamSeeder`: crea el team `admin` (idempotente) y asigna todos los admins existentes
- `CreateUserAction`: al crear un usuario con rol Admin, lo añade al team `admin` automáticamente
- `ResolvesLoginRedirect`: para el rol Admin, resuelve el team por slug `admin` y redirige a `/admin/dashboard`; fallback al personal team si no hay membresía
- `TeamPolicy`: bloquear `delete` sobre el team con slug `admin`
- `DatabaseSeeder`: registrar `AdminTeamSeeder` y ejecutarlo en entorno de desarrollo

### Excluye explícitamente
- UI de gestión del team institucional (agregar/quitar miembros, renombrar)
- Cambios en la lógica de personal teams (se mantienen intactos)
- Teams institucionales para otros roles (profesor, estudiante, representante)
- Invitaciones, roles de team, o cualquier feature de gestión de membresía

---

## Actores y permisos

| Actor | Acción | Permitida |
|-------|--------|-----------|
| Admin | Pertenecer al team `admin` (automático) | Sí |
| Admin | Eliminar el team `admin` | No — bloqueado por Policy |
| Admin | Ver/usar el team `admin` para acceder a `/admin/dashboard` | Sí |
| Cualquier rol | Ver o eliminar el team `admin` | No |
| Sistema | Crear membresía al crear Admin | Sí (automático en CreateUserAction) |

---

## Reglas de negocio

1. El team `admin` tiene slug `admin`, `is_personal = false`, y se crea exactamente una vez en la DB (idempotente: si ya existe, el seeder no lo crea de nuevo ni falla).
2. Todo usuario con rol `admin` DEBE ser miembro del team `admin`. Esta invariante se mantiene en el momento de creación del usuario.
3. `CreateUserAction` añade al usuario al team `admin` inmediatamente después de crear el registro, si el rol asignado es `admin`.
4. El redirect post-login para un Admin va a `/admin/dashboard`, resuelto a través del team con slug `admin`. Si por algún motivo el Admin no tiene membresía en ese team, el fallback es el personal team del usuario.
5. Los Admins mantienen su personal team sin cambios — el team institucional es adicional.
6. El team `admin` no puede ser eliminado desde ninguna acción de UI. `TeamPolicy::delete()` retorna `false` si `$team->slug === 'admin'`.
7. Si un Admin es removido del team `admin` directamente en DB (escenario de mantenimiento), el middleware de resolución de team captura la ausencia gracefully y hace fallback al personal team.

---

## Casos de error y comportamiento esperado

| Escenario | Comportamiento esperado |
|-----------|------------------------|
| `AdminTeamSeeder` ejecutado dos veces | No crea un segundo team; no lanza excepción |
| Admin recién creado sin membresía en team `admin` | Imposible en flujo normal (CreateUserAction lo garantiza) |
| Admin sin membresía en team `admin` (DB inconsistente) | Redirect fallback al personal team; sin error 500 |
| Intento de DELETE sobre team `admin` via UI | Policy retorna `false`; respuesta 403 |
| Usuario no-Admin intenta acceder a `/admin/dashboard` | Middleware de rol bloquea; sin cambio en este feature |
