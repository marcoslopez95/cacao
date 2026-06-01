<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Attendance\CreateAdvanceSessionAction;
use App\Actions\Attendance\CreateClassSessionAction;
use App\Actions\Attendance\CreateMakeupSessionAction;
use App\Actions\Attendance\TakeAttendanceAction;
use App\Enums\ClassSessionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminStoreClassSessionRequest;
use App\Http\Requests\Admin\AdminUpsertAttendanceRequest;
use App\Http\Resources\Attendance\AdminPendingSessionResource;
use App\Http\Resources\Attendance\AttendanceSheetResource;
use App\Http\Resources\Attendance\ClassSessionResource;
use App\Http\Resources\Attendance\SectionAttendanceResource;
use App\Http\Wrappers\Attendance\AttendanceSheetWrapper;
use App\Http\Wrappers\Attendance\ClassSessionWrapper;
use App\Models\ClassSession;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', [ClassSession::class, new Section]);

        $pendingSessions = ClassSession::with([
            'section.subject.pensum.career',
            'section.pensum.career',
            'section.mainTeacher.user',
        ])
            ->whereDoesntHave('attendanceRecords')
            ->where('status', 'scheduled')
            ->whereNotNull('held_at')
            ->where('held_at', '<=', now())
            ->orderBy('held_at')
            ->get();

        return Inertia::render('admin/attendance/Index', [
            'pending_sessions' => AdminPendingSessionResource::collection($pendingSessions),
        ]);
    }

    public function sectionIndex(Request $request, Section $section): Response
    {
        Gate::authorize('viewAny', [ClassSession::class, $section]);

        $section->load(['subject', 'schedules', 'mainTeacher.user']);

        $sessions = $section->classSessions()
            ->with(['attendanceRecords', 'linkedSession', 'uploadedBy'])
            ->orderByDesc('held_at')->orderByDesc('created_at')->get();

        return Inertia::render('admin/attendance/SectionIndex', [
            'section' => new SectionAttendanceResource($section),
            'sessions' => ClassSessionResource::collection($sessions),
        ]);
    }

    public function storeSession(
        AdminStoreClassSessionRequest $request,
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

        return to_route('admin.sections.attendance.index', $section);
    }

    public function sheet(Request $request, Section $section, ClassSession $classSession): Response
    {
        Gate::authorize('takeAttendance', $classSession);

        $classSession->load(['attendanceRecords', 'linkedSession', 'uploadedBy', 'section']);

        return Inertia::render('admin/attendance/Sheet', [
            'sheet' => (new AttendanceSheetResource($classSession))->toArray($request),
        ]);
    }

    public function upsertAttendance(
        AdminUpsertAttendanceRequest $request,
        Section $section,
        ClassSession $classSession,
        TakeAttendanceAction $action,
    ): RedirectResponse {
        $action->handle(new AttendanceSheetWrapper(array_merge($request->validated(), [
            'class_session_id' => $classSession->id,
            'professor_present' => false,
        ])));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Asistencia guardada.']);

        return to_route('admin.sections.attendance.sheet', [$section, $classSession]);
    }
}
