<?php

namespace App\Http\Controllers\Professor;

use App\Actions\Attendance\CreateAdvanceSessionAction;
use App\Actions\Attendance\CreateClassSessionAction;
use App\Actions\Attendance\CreateMakeupSessionAction;
use App\Actions\Attendance\TakeAttendanceAction;
use App\Enums\ClassSessionType;
use App\Enums\PeriodStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Professor\StoreClassSessionRequest;
use App\Http\Requests\Professor\UpsertAttendanceRequest;
use App\Http\Resources\Attendance\AttendanceSheetResource;
use App\Http\Resources\Attendance\ClassSessionResource;
use App\Http\Resources\Attendance\SectionAttendanceResource;
use App\Http\Wrappers\Attendance\AttendanceSheetWrapper;
use App\Http\Wrappers\Attendance\ClassSessionWrapper;
use App\Models\ClassSession;
use App\Models\Period;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function index(Request $request, Section $section): Response
    {
        Gate::authorize('viewAny', [ClassSession::class, $section]);

        $section->load(['subject.pensum.career', 'pensum.career', 'schedules', 'mainTeacher.user', 'theoryClassroom', 'labClassroom', 'classroom']);

        $sessions = $section->classSessions()
            ->with(['attendanceRecords', 'linkedSession', 'uploadedBy'])
            ->orderByDesc('held_at')->orderByDesc('created_at')->get();

        $periodName = Period::where('status', PeriodStatus::Active)->first()?->name;

        return Inertia::render('professor/attendance/Index', [
            'section' => new SectionAttendanceResource($section),
            'sessions' => ClassSessionResource::collection($sessions),
            'period' => $periodName,
        ]);
    }

    public function storeSession(
        StoreClassSessionRequest $request,
        Section $section,
        CreateClassSessionAction $createAction,
        CreateMakeupSessionAction $makeupAction,
        CreateAdvanceSessionAction $advanceAction,
    ): RedirectResponse {
        $wrapper = new ClassSessionWrapper(
            array_merge($request->validated(), ['section_id' => $section->id])
        );

        $action = match ($wrapper->getType()) {
            ClassSessionType::Makeup => $makeupAction,
            ClassSessionType::Advance => $advanceAction,
            default => $createAction,
        };

        $action->handle($wrapper);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sesión creada.']);

        return to_route('professor.sections.attendance.index', $section);
    }

    public function sheet(Request $request, Section $section, ClassSession $classSession): Response
    {
        Gate::authorize('takeAttendance', $classSession);

        $classSession->load(['attendanceRecords', 'linkedSession', 'uploadedBy', 'section']);
        $section->load(['subject.pensum.career', 'pensum.career', 'schedules', 'mainTeacher.user', 'theoryClassroom', 'labClassroom', 'classroom']);

        return Inertia::render('professor/attendance/Sheet', [
            'sheet' => (new AttendanceSheetResource($classSession))->toArray($request),
            'section' => new SectionAttendanceResource($section),
        ]);
    }

    public function upsertAttendance(
        UpsertAttendanceRequest $request,
        Section $section,
        ClassSession $classSession,
        TakeAttendanceAction $action,
    ): RedirectResponse {
        $action->handle(new AttendanceSheetWrapper(array_merge($request->validated(), ['class_session_id' => $classSession->id])));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Asistencia guardada.']);

        return to_route('professor.sections.attendance.sheet', [$section, $classSession]);
    }
}
