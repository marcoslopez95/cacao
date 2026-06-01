# HLZ-29 — Diseño del fix

## Cambios necesarios (3 archivos)

### 1. `app/Http/Requests/Admin/StoreSocioeconomicProfileRequest.php`

Cambiar `study_date` de `required` a `nullable`:

```php
// ANTES
'study_date' => ['required', 'date'],

// DESPUÉS
'study_date' => ['nullable', 'date'],
```

### 2. `app/Http/Wrappers/Admin/SocioeconomicProfileWrapper.php`

Cambiar tipo de retorno de `getStudyDate()` a nullable:

```php
// ANTES
public function getStudyDate(): string
{
    return $this->get('study_date');
}

// DESPUÉS
public function getStudyDate(): ?string
{
    return $this->get('study_date');
}
```

### 3. `app/Actions/Admin/UpsertSocioeconomicProfileAction.php`

Usar `Carbon::today()` como default cuando `study_date` es null:

```php
// Añadir use al top del archivo
use Illuminate\Support\Carbon;

// En el updateOrCreate:
'study_date' => $wrapper->getStudyDate() ?? Carbon::today()->toDateString(),
```

## Sin cambios en

- Migración — la columna permanece NOT NULL (correcto, siempre tendrá un valor)
- Resource — `study_date` ya se expone correctamente
- Frontend — el composable no necesita cambiar (nunca envió este campo)
