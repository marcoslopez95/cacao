# Design — guardian-student-relations

## Backend

### Rutas (`routes/web.php`, dentro del grupo `academic` ya existente)

```php
Route::get('guardians', [GuardianController::class, 'index'])->name('guardians.index');
Route::get('guardians/{guardian}', [GuardianController::class, 'show'])->name('guardians.show');
```

Mismo middleware que `students.*` (`auth`, `verified`, sin Policy — ver "Fuera de alcance").

### `App\Http\Controllers\Academic\GuardianController`

- `index(Request $request)`: paginado (`per_page`, default 25, igual a `StudentController`), `withCount('students')`, `search` sobre `users.name`/`users.email` vía join. Devuelve `Inertia::render('admin/Guardians/Index', ...)`.
- `show(Guardian $guardian)`: `load(['user', 'students.user', 'students' => fn ($q) => $q->withPivot(['kinship_type_id','is_primary','is_emergency_contact'])])`. Devuelve `Inertia::render('admin/Guardians/Show', ['guardian' => (new GuardianShowResource($guardian))->resolve()])`.

### Resolución de parentesco (evitar N+1)

`KinshipType::pluck('name', 'id')` una sola vez, pasado a los Resources como closure o resuelto en el propio Resource vía `App\Models\Catalogs\KinshipType::pluck('name', 'id')->get($id)` cacheado en request (Laravel cachea la query si se llama una vez por el mismo Resource::collection — para simplicidad y consistencia con el resto del código, que no usa cache manual en ningún otro Resource, se acepta 1 query adicional por página, no por fila).

### Resources nuevos

`App\Http\Resources\Academic\GuardianListResource`:
```
id, user_id, name, email, students_count
```

`App\Http\Resources\Academic\GuardianShowResource`:
```
id, user_id, name, email,
students: [{ id, name, email, educational_level, kinship, primary, emergency_contact }]
```
(mismo shape que `StudentShowResource::guardians`, invertido — reutiliza el mismo criterio de nombres de campo para consistencia).

### `UserController::index()` — exponer conteo + id de relación por fila

`Edit.vue` es un wizard rígido de 17 secciones sin lugar para paneles nuevos sin invadir esa arquitectura, y `EditUserModal.vue` es un modal liviano (nombre/correo/roles) fuera de alcance — la relación se expone en el **índice**, no en la edición (ver UC-03).

En `$query->with([...])` agregar:
- `'student:id,user_id,educational_level'` (ya existe desde la feature anterior)
- `'student.guardians:id'` (solo para contar — no se listan datos aquí)
- `'guardian:id,user_id'`
- `'guardian.students:id'`

`UserResource` agrega:
```php
'student_id' => $this->whenLoaded('student', fn () => $this->student?->id),
'guardians_count' => $this->whenLoaded('student', fn () => $this->student?->guardians->count()),
'guardian_id' => $this->whenLoaded('guardian', fn () => $this->guardian?->id),
'students_count' => $this->whenLoaded('guardian', fn () => $this->guardian?->students->count()),
```

Sin Wrapper/Action nuevo — es lectura pura, mismo patrón que `student_level` agregado en la feature anterior.

## Frontend

### Tipos nuevos — `resources/js/types/guardian.ts`

```ts
export type GuardianStudentRow = {
    id: number; name: string; email: string;
    educational_level: string; kinship: string | null;
    primary: boolean; emergency_contact: boolean;
};

export type GuardianListItem = { id: number; user_id: number; name: string; email: string; students_count: number };
export type GuardianShowData = { id: number; user_id: number; name: string; email: string; students: GuardianStudentRow[] };
export interface GuardianCollection { data: GuardianListItem[]; meta: PaginationMeta; links: PaginationLink[]; }
```

Extender `types/security.ts` — `UserRow`/props del Edit no cambian (el Edit ya usa objetos ad-hoc `props.student`/`props.guardian`, sin tipo fuerte declarado en el componente; se documenta el shape nuevo con un comentario TS, sin introducir `any`).

### Páginas nuevas

- `resources/js/pages/admin/Guardians/Index.vue` — tabla simple (Usuario, Estudiantes a cargo, Acciones→ver), buscador, paginación. Mismo layout visual que `security/Users/Index.vue` (estilos inline con tokens CSS existentes, sin introducir un framework de tabla nuevo).
- `resources/js/pages/admin/Guardians/Show.vue` — identidad + tabla de estudiantes a cargo, enlace a `academic/students/{id}` vía Wayfinder (`show` de `academic/students`). Mismo estilo visual que `admin/Students/Show.vue`.

### `AppSidebar.vue`

Agregar en `academicItems`, junto a "Estudiantes":
```ts
if (page.props.auth?.roles?.includes('Admin')) {
    academicItems.push({ icon: 'graduation-cap', label: 'Estudiantes', href: studentsIndex.url() })
    academicItems.push({ icon: 'users', label: 'Representantes', href: guardiansIndex.url() })
}
```

### `security/Users/Index.vue` — columna "Relación"

Nueva columna entre "Nivel" y "Estado":
```vue
<td>
    <Link v-if="user.student_id && user.guardians_count" :href="`/academic/students/${user.student_id}`" class="rel-link">
        Representantes ({{ user.guardians_count }})
    </Link>
    <Link v-else-if="user.guardian_id && user.students_count" :href="`/academic/guardians/${user.guardian_id}`" class="rel-link">
        Estudiantes ({{ user.students_count }})
    </Link>
    <span v-else style="color:var(--text-muted);font-style:italic;font-size:var(--text-sm);">—</span>
</td>
```
`UserRow` (types/security.ts) gana `student_id`, `guardian_id`, `guardians_count`, `students_count` (todos `number | null`).
