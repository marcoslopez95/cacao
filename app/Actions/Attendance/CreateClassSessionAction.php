<?php

namespace App\Actions\Attendance;

use App\Enums\ClassSessionStatus;
use App\Http\Wrappers\Attendance\ClassSessionWrapper;
use App\Models\ClassSession;

class CreateClassSessionAction
{
    public function handle(ClassSessionWrapper $wrapper): ClassSession
    {
        return ClassSession::create([
            'section_id' => $wrapper->getSectionId(),
            'type' => $wrapper->getType(),
            'status' => ClassSessionStatus::Scheduled,
            'linked_session_id' => $wrapper->getLinkedSessionId(),
            'topic' => $wrapper->getTopic(),
            'held_at' => $wrapper->getHeldAt(),
            'professor_present' => $wrapper->isProfessorPresent(),
        ]);
    }
}
