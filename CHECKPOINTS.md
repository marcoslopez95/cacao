# Checkpoints — Criterios de Done

> Este archivo es la referencia de verificación para el agente `reviewer`.
> Un task solo puede marcarse `[x]` cuando **todos** los checkpoints de su capa pasan.

---

## Backend — Por entidad nueva

### FormRequest (`app/Http/Requests/{Dominio}/`)
- [ ] Archivo existe en la carpeta correcta
- [ ] `authorize()` delega a una Policy — sin `$user->role` inline
- [ ] `rules()` valida todos los campos de entrada con reglas explícitas
- [ ] `exists:tabla,id` presente para toda FK
- [ ] Getters tipados para resolución de modelos (ej. `getStudent(): Student`)

### Wrapper (`app/Http/Wrappers/{Dominio}/`)
- [ ] Extiende `Illuminate\Support\Collection`
- [ ] Getters semánticos para modelos: `getStudent()`, `getSubject()`, etc.
- [ ] Sin lógica de negocio — solo resolución/transformación de datos
- [ ] El Action nunca recibe `array $validated` — siempre recibe un Wrapper tipado

### Action (`app/Actions/{Dominio}/`)
- [ ] Nombre con sufijo `Action`: `CreateEnrollmentAction`, `ConfirmEnrollmentAction`
- [ ] Método único `handle()` con return type explícito
- [ ] Sin manipulación de datos crudos (hasheo, casting, etc.) — eso va en el Wrapper
- [ ] Solo lógica de negocio: DB writes, side effects, eventos

### Resource (`app/Http/Resources/{Dominio}/`)
- [ ] Extiende `JsonResource`
- [ ] `toArray()` retorna todos los campos definidos en `specs/{feature}/design.md`
- [ ] Sin `any` en TypeScript del lado del consumidor

### Policy (`app/Policies/`)
- [ ] Existe el archivo Policy para cada modelo protegido
- [ ] Policy registrada en `AppServiceProvider` via `Gate::policy()`
- [ ] Sin `if ($user->role === ...)` en controladores

### Controller
- [ ] Cada método tiene máximo 8 líneas de código
- [ ] Sin lógica de negocio — solo: crear Wrapper, inyectar Action, retornar Resource/Redirect
- [ ] `authorize()` o `$this->authorize()` presente donde aplica
- [ ] Nombre de rutas registradas en `routes/web.php`

### Tests Pest
- [ ] Tests pasan: `vendor/bin/sail artisan test --compact`
- [ ] Sin tests eliminados
- [ ] Feature tests primero; unit tests para lógica aislada (Services, Validators)

### Pint
- [ ] Sin errores: `vendor/bin/sail bin pint --dirty --format agent`

---

## Frontend — Por página nueva

### Composable de form (`composables/forms/use{Entity}Form.ts`)
- [ ] Posee `useForm` de Inertia
- [ ] Métodos `create()`, `update()`, `remove()` según aplique
- [ ] Rutas via Wayfinder — sin strings hardcodeados ni `route()` raw
- [ ] `router.post/.put/.delete` solo dentro de este composable — nunca en páginas

### Composable de permisos (`composables/permissions/use{Entity}Permissions.ts`)
- [ ] Envuelve CASL, expone `canCreate`, `canEdit`, `canDelete`, etc.
- [ ] `usePage().props.auth` solo en este composable — nunca en páginas ni componentes

### Types (`types/{entity}.ts`)
- [ ] Refleja exactamente el Laravel Resource correspondiente
- [ ] Sin `any` — usar `Pick<>`, `Omit<>`, `Partial<>` para variantes

### Página Vue (`pages/{dominio}/{Entidad}/Index.vue`)
- [ ] Solo imports + template declarativo
- [ ] Sin `router.post/.put/.delete` directos
- [ ] Un solo elemento raíz

---

## General

- [ ] Migraciones corren sin error: `vendor/bin/sail artisan migrate:fresh`
- [ ] Sin lógica de negocio en controladores
- [ ] Sin `if ($user->role === 'admin')` directo en ningún archivo
- [ ] FKs con RESTRICT — sin cascadas en datos críticos
- [ ] `EnrollmentDetail` es el pivote de historial académico — grades/attendances/submissions apuntan a `enrollment_detail_id`
