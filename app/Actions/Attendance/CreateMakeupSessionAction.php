<?php

namespace App\Actions\Attendance;

use App\Enums\ClassSessionStatus;
use App\Enums\ClassSessionType;
use App\Http\Wrappers\Attendance\ClassSessionWrapper;
use App\Models\ClassSession;
use InvalidArgumentException;

class CreateMakeupSessionAction
{
    public function handle(ClassSessionWrapper $wrapper): ClassSession
    {
        $linkedSessionId = $wrapper->getLinkedSessionId();

        if ($linkedSessionId === null) {
            throw new InvalidArgumentException('linked_session_id is required to create a makeup session.');
        }

        $newSession = ClassSession::create([
            'section_id' => $wrapper->getSectionId(),
            'type' => ClassSessionType::Makeup,
            'status' => ClassSessionStatus::Scheduled,
            'linked_session_id' => $linkedSessionId,
            'topic' => $wrapper->getTopic(),
            'held_at' => $wrapper->getHeldAt(),
            'professor_present' => $wrapper->isProfessorPresent(),
        ]);

        ClassSession::where('id', $linkedSessionId)->update([
            'status' => ClassSessionStatus::Recovered,
            'linked_session_id' => $newSession->id,
        ]);

        return $newSession;
    }
}
