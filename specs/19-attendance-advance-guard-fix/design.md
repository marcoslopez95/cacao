# HLZ-45 — Diseño del fix

## Decisión de diseño

Portar el mismo patrón de guard que ya existe y funciona en `CreateMakeupSessionAction::handle()` (validar el estado de la sesión vinculada antes de crear, lanzar `ValidationException` si ya fue consumida por el mismo tipo de operación). No se introduce ningún concepto nuevo — es exactamente la asimetría que causó el bug: `CreateMakeupSessionAction` valida, `CreateAdvanceSessionAction` no.

**Opción descartada:** agregar una restricción a nivel de base de datos (ej. constraint único condicional sobre `linked_session_id` para sesiones activas). Se descarta porque el guard en la Action ya cierra el caso reportado con el mismo nivel de garantía que usa el resto del módulo (`Recovered` en Makeup), y una constraint de DB condicional sobre un enum de status añadiría complejidad de migración desproporcionada al alcance de este fix.

## Cambio necesario

### `app/Actions/Attendance/CreateAdvanceSessionAction.php`

```php
<?php

namespace App\Actions\Attendance;

use App\Enums\ClassSessionStatus;
use App\Enums\ClassSessionType;
use App\Http\Wrappers\Attendance\ClassSessionWrapper;
use App\Models\ClassSession;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class CreateAdvanceSessionAction
{
    public function handle(ClassSessionWrapper $wrapper): ClassSession
    {
        $linkedSessionId = $wrapper->getLinkedSessionId();

        if ($linkedSessionId === null) {
            throw new InvalidArgumentException('linked_session_id is required to create an advance session.');
        }

        $linkedSession = ClassSession::find($linkedSessionId);

        if ($linkedSession !== null && $linkedSession->status === ClassSessionStatus::Advanced) {
            throw ValidationException::withMessages([
                'linked_session_id' => ['Esta sesión ya fue adelantada.'],
            ]);
        }

        $newSession = ClassSession::create([
            'section_id' => $wrapper->getSectionId(),
            'type' => ClassSessionType::Advance,
            'status' => ClassSessionStatus::Scheduled,
            'linked_session_id' => $linkedSessionId,
            'topic' => $wrapper->getTopic(),
            'held_at' => $wrapper->getHeldAt(),
            'professor_present' => $wrapper->isProfessorPresent(),
        ]);

        ClassSession::where('id', $linkedSessionId)->update([
            'status' => ClassSessionStatus::Advanced,
            'linked_session_id' => $newSession->id,
        ]);

        return $newSession;
    }
}
```

Único cambio: el bloque `if ($linkedSession !== null && ...)` agregado, más el import de `ValidationException`, idéntico en forma a `CreateMakeupSessionAction`.

## Sin cambios en

- `CreateMakeupSessionAction` — ya correcto, sirve de referencia.
- `StoreClassSessionRequest` — la validación de estado no es una regla de formato de campo, vive en la Action (misma decisión ya tomada para Makeup).
- `TakeAttendanceAction` — sin cambios; el guard nuevo hace que el escenario de doble-copia nunca se alcance.
- Frontend (`resources/js/pages/professor/**`) — el filtro del selector "Sesión vinculada" ya excluye correctamente sesiones `advanced`, sin cambios necesarios.
