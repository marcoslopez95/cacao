<?php

namespace App\Actions\Attendance;

use App\Enums\ClassSessionStatus;
use App\Models\ClassSession;
use Illuminate\Validation\ValidationException;

class CancelClassSessionAction
{
    public function handle(ClassSession $classSession): ClassSession
    {
        if (! in_array($classSession->status, [ClassSessionStatus::Scheduled, ClassSessionStatus::Held], true)) {
            throw ValidationException::withMessages([
                'status' => ['Esta sesión no se puede cancelar.'],
            ]);
        }

        $classSession->update(['status' => ClassSessionStatus::Cancelled]);

        return $classSession;
    }
}
