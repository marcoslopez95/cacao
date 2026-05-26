# Design — user-edit-professor-s17-fix

**Feature ID:** `user-edit-professor-s17-fix`

---

## Fix 1 — StoreStaffProfileRequest: `exists:departments,id` → `exists:coordinations,id`

**Archivo:** `app/Http/Requests/Admin/StoreStaffProfileRequest.php`

**Línea afectada (30 circa):**
```php
// ANTES (buggy):
'coordinated_department_id' => ['nullable', 'integer', 'exists:departments,id', 'required_if:is_coordinator,true'],

// DESPUÉS (correcto):
'coordinated_department_id' => ['nullable', 'integer', 'exists:coordinations,id', 'required_if:is_coordinator,true'],
```

La tabla en DB es `coordinations` (confirmado por `UserController::edit()` que ya usa `Coordination::where('active', true)->...`). No existe tabla `departments`.

---

## Fix 2 — StaffProfile::coordinatedDepartment(): reemplazar Department por Coordination

**Archivo:** `app/Models/StaffProfile.php`

**Cambios:**
1. Agregar import: `use App\Models\Coordination;`
2. Eliminar el comentario obsoleto y la directiva `@phpstan-ignore-next-line`
3. Cambiar la relación de `Department::class` a `Coordination::class`
4. Agregar PHPDoc de clase explicando la FK indirecta

```php
// ANTES:
use App\Models\Catalogs\ContractType;
use App\Models\Catalogs\DedicationType;
use App\Models\Catalogs\EmploymentStatus;
// ...

/**
 * Relation will be wired once departments table exists.
 *
 * @phpstan-ignore-next-line
 */
public function coordinatedDepartment(): BelongsTo
{
    return $this->belongsTo(Department::class, 'coordinated_department_id');
}

// DESPUÉS:
use App\Models\Catalogs\ContractType;
use App\Models\Catalogs\DedicationType;
use App\Models\Catalogs\EmploymentStatus;
use App\Models\Coordination;
// ...

/**
 * The coordination (department) this professor coordinates, if is_coordinator=true.
 * Note: Uses the `coordinations` table (model Coordination), NOT a `departments` table.
 *
 * @note The FK chain to a user is: staff_profiles.professor_id → professors.user_id → users.id
 *       Do NOT query by user_id on this model — that column does not exist.
 */
public function coordinatedDepartment(): BelongsTo
{
    return $this->belongsTo(Coordination::class, 'coordinated_department_id');
}
```

---

## Fix 3 — buildInitialFormData(): normalizar fechas ISO a YYYY-MM-DD

**Archivo:** `resources/js/composables/forms/useUserEditForm.ts`

**Bloque afectado** (cerca de línea 587 en la sección `if (props.professor?.staffProfile)`):

```typescript
// ANTES (buggy — Carbon ISO 8601 → input[type=date] muestra vacío):
d.hireDate   = p.hire_date          ?? undefined
d.endDate    = p.termination_date   ?? undefined
d.coordSince = p.coordinator_since  ?? undefined

// DESPUÉS (correcto — solo YYYY-MM-DD para input[type=date]):
d.hireDate   = p.hire_date?.substring(0, 10)         ?? undefined
d.endDate    = p.termination_date?.substring(0, 10)  ?? undefined
d.coordSince = p.coordinator_since?.substring(0, 10) ?? undefined
```

**Por qué `.substring(0, 10)` y no el Resource:** La solución en frontend es preferible porque:
- No requiere cambios en PHP ni re-build del backend
- Es idempotente: si en algún futuro el Resource ya devuelve `YYYY-MM-DD`, `.substring(0, 10)` sobre ese string sigue funcionando correctamente
- No afecta `saveStaffProfile()` — el composable envía los valores de `formData.hireDate` al backend, que valida con `'hire_date' => ['required', 'date']` y acepta ambos formatos

---

## Fix 4 — PHPDoc de clase en StaffProfile (HLZ-04)

El PHPDoc en el método `coordinatedDepartment()` (fix 2) ya cubre este punto. Adicionalmente, agregar un comentario de clase-nivel al `StaffProfile` que documente la FK:

```php
/**
 * Staff profile for a professor.
 *
 * FK: `staff_profiles.professor_id` → `professors.id`
 * To find a user's staff profile: User → Professor → StaffProfile
 * Do NOT use StaffProfile::where('user_id', ...) — no such column exists.
 */
class StaffProfile extends Model
```

---

## Impacto total

| Archivo | Tipo de cambio | Líneas estimadas |
|---------|---------------|-----------------|
| `StoreStaffProfileRequest.php` | 1 string cambiado | 1 |
| `StaffProfile.php` | import + relación + PHPDoc | ~12 |
| `useUserEditForm.ts` | 3 `.substring(0, 10)` | 3 |
| `StaffProfileTest.php` | nuevo test case | ~20 |

Sin migraciones. Sin cambios en `catalogData`. Sin cambios en componentes Vue.
