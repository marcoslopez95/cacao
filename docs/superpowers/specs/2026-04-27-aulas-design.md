# Módulo de Infraestructura: Edificios y Aulas

**Fecha:** 2026-04-27
**Estado:** Aprobado

---

## Contexto

Se agrega el módulo de Infraestructura como nuevo grupo de navegación, separado de Académico. Contiene dos entidades: Edificios y Aulas. El objetivo inmediato es permitir al admin gestionar el inventario de aulas para luego asignarlas al armar horarios de secciones.

---

## Modelo de datos

### `buildings`

| campo | tipo | notas |
|---|---|---|
| `id` | bigint PK | |
| `name` | string(100) | único |
| `created_at` / `updated_at` | timestamps | |

### `classrooms`

| campo | tipo | notas |
|---|---|---|
| `id` | bigint PK | |
| `building_id` | FK → buildings | RESTRICT on delete |
| `identifier` | string(50) | ej. "301", "Lab A" |
| `type` | enum: `theory` / `laboratory` | |
| `capacity` | smallint unsigned | |
| `created_at` / `updated_at` | timestamps | |
| unique | (`building_id`, `identifier`) | no duplicar identificador en mismo edificio |

Las FKs `theory_classroom_id` y `lab_classroom_id` en `sections` se agregan al construir el módulo de Secciones (fuera del alcance de este spec).

---

## Backend

Patrón: `FormRequest → Controller → Wrapper → Action → Resource`.

### Rutas

```
GET|POST     /infrastructure/buildings                   infrastructure.buildings.index / store
PATCH|DELETE /infrastructure/buildings/{building}        infrastructure.buildings.update / destroy

GET|POST     /infrastructure/classrooms                  infrastructure.classrooms.index / store
PATCH|DELETE /infrastructure/classrooms/{classroom}      infrastructure.classrooms.update / destroy
```

### Archivos PHP

```
app/Models/
    Building.php
    Classroom.php

app/Actions/Infrastructure/
    CreateBuildingAction.php
    UpdateBuildingAction.php
    DeleteBuildingAction.php
    CreateClassroomAction.php
    UpdateClassroomAction.php
    DeleteClassroomAction.php

app/Http/Requests/Infrastructure/
    StoreBuildingRequest.php
    UpdateBuildingRequest.php
    StoreClassroomRequest.php
    UpdateClassroomRequest.php

app/Http/Wrappers/Infrastructure/
    BuildingWrapper.php
    ClassroomWrapper.php

app/Http/Resources/Infrastructure/
    BuildingResource.php
    ClassroomResource.php

app/Http/Controllers/Infrastructure/
    BuildingController.php
    ClassroomController.php

app/Policies/
    BuildingPolicy.php
    ClassroomPolicy.php
```

### Filtro de aulas por edificio

`ClassroomController@index` acepta query param `?building_id=` opcional, resuelto con `->when()` en el query Eloquent. No requiere ruta anidada.

### Permisos

```
buildings.view-any   buildings.create   buildings.update   buildings.delete
classrooms.view-any  classrooms.create  classrooms.update  classrooms.delete
```

Registrados en el seeder de roles; el rol `Admin` recibe todos. Las Policies delegan a estos permisos con `$user->can('...')`.

---

## Frontend

Patrón: `FormComposable → Page → PermissionComposable → Type`.

### Páginas

```
resources/js/pages/infrastructure/Buildings/Index.vue
resources/js/pages/infrastructure/Classrooms/Index.vue
```

- **Buildings/Index**: tabla con columnas Nombre · Acciones. Modales para crear, editar y eliminar.
- **Classrooms/Index**: tabla con columnas Identificador · Edificio · Tipo · Capacidad · Acciones. `<select>` de filtro por edificio en la parte superior (mismo patrón que el filtro de período en Materias).

### Composables y tipos

```
resources/js/composables/forms/useBuildingForm.ts
resources/js/composables/forms/useClassroomForm.ts
resources/js/composables/permissions/useBuildingPermissions.ts
resources/js/composables/permissions/useClassroomPermissions.ts
resources/js/composables/filters/useClassroomFilters.ts
resources/js/types/infrastructure.ts
```

- `useClassroomFilters.ts` maneja el estado de `?building_id=` con `router.get` debounced.
- `types/infrastructure.ts` exporta: `Building`, `Classroom`, `BuildingCollection`, `ClassroomCollection`.

### Componentes modales

```
resources/js/components/infrastructure/
    CreateBuildingModal.vue
    EditBuildingModal.vue
    DeleteBuildingModal.vue
    CreateClassroomModal.vue
    EditClassroomModal.vue
    DeleteClassroomModal.vue
```

### Navegación

Se agrega un nuevo grupo **"Infraestructura"** en el sidebar con dos ítems: Edificios (`/infrastructure/buildings`) y Aulas (`/infrastructure/classrooms`).

---

## Testing

- Feature tests con Pest para cada endpoint (index, store, update, destroy) de ambos controladores.
- Casos: admin puede hacer todo, usuario sin permisos recibe 403.
- Test de unicidad: no se puede crear dos aulas con el mismo identificador en el mismo edificio.
- Test de integridad referencial: no se puede eliminar un edificio que tenga aulas.
