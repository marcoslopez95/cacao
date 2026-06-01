<?php

use App\Http\Controllers\Academic\CareerCategoryController;
use App\Http\Controllers\Academic\CareerController;
use App\Http\Controllers\Academic\PensumController;
use App\Http\Controllers\Academic\StudentController;
use App\Http\Controllers\Academic\SubjectController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\DemographicProfileController;
use App\Http\Controllers\Admin\FamilyProfileController;
use App\Http\Controllers\Admin\GradeConfigController;
use App\Http\Controllers\Admin\GuardianProfileController;
use App\Http\Controllers\Admin\HealthProfileController;
use App\Http\Controllers\Admin\HousingProfileController;
use App\Http\Controllers\Admin\SocioeconomicProfileController;
use App\Http\Controllers\Admin\StaffProfileController;
use App\Http\Controllers\Admin\StudentBackgroundController;
use App\Http\Controllers\Admin\StudentBenefitController;
use App\Http\Controllers\Admin\StudentLanguageController;
use App\Http\Controllers\Admin\UserAddressController;
use App\Http\Controllers\Admin\UserDocumentController;
use App\Http\Controllers\Auth\AcceptInvitationController;
use App\Http\Controllers\Enrollment\EnrollmentController;
use App\Http\Controllers\Guardian;
use App\Http\Controllers\Infrastructure\BuildingController;
use App\Http\Controllers\Infrastructure\ClassroomController;
use App\Http\Controllers\Professor;
use App\Http\Controllers\Scheduling\LapseController;
use App\Http\Controllers\Scheduling\PeriodController;
use App\Http\Controllers\Scheduling\ProfessorController;
use App\Http\Controllers\Scheduling\ScheduleController;
use App\Http\Controllers\Scheduling\SchoolSectionController;
use App\Http\Controllers\Scheduling\UniversitySectionController;
use App\Http\Controllers\Security\CoordinationAssignmentController;
use App\Http\Controllers\Security\CoordinationController;
use App\Http\Controllers\Security\InvitationController;
use App\Http\Controllers\Security\RoleController;
use App\Http\Controllers\Security\UserController;
use App\Http\Controllers\Student;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Controllers\UserConsentController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::get('invitations/{token}', [AcceptInvitationController::class, 'show'])->name('invitation.show')->whereUuid('token');
Route::post('invitations/{token}', [AcceptInvitationController::class, 'store'])->name('invitation.store')->whereUuid('token');

// Reserved prefixes must not be captured by {current_team}.
Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->where(['current_team' => '^(?!security|academic|infrastructure|scheduling|enrollment|professor|student|guardian|settings|profile|invitations|_test)[^/]+$'])
    ->group(function () {
        Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    });

Route::middleware(['auth'])->group(function () {
    Route::get('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
});

Route::middleware(['auth', 'verified'])->prefix('security')->name('security.')->group(function () {
    Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
    Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
    Route::patch('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

    // Users
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::get('users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::patch('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::patch('users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
    Route::patch('users/{user}/identity', [UserController::class, 'updateIdentity'])->name('users.identity.update');
    Route::post('users/{user}/credentials', [UserController::class, 'updateCredentials'])->name('users.credentials.update');

    // Invitations
    Route::post('invitations', [InvitationController::class, 'store'])->name('invitations.store');
    Route::delete('invitations/{invitation}', [InvitationController::class, 'destroy'])->name('invitations.destroy');

    // Coordinations
    Route::get('coordinations', [CoordinationController::class, 'index'])->name('coordinations.index');
    Route::post('coordinations', [CoordinationController::class, 'store'])->name('coordinations.store');
    Route::patch('coordinations/{coordination}', [CoordinationController::class, 'update'])->name('coordinations.update');
    Route::delete('coordinations/{coordination}', [CoordinationController::class, 'destroy'])->name('coordinations.destroy');

    // Coordination Assignments
    Route::get('coordinations/{coordination}/assignments', [CoordinationAssignmentController::class, 'index'])->name('coordinations.assignments.index');
    Route::post('coordinations/{coordination}/assignments', [CoordinationAssignmentController::class, 'store'])->name('coordinations.assignments.store');

    // User Addresses
    Route::prefix('users/{user}/addresses')->name('users.addresses.')->group(function () {
        Route::get('/', [UserAddressController::class, 'index'])->name('index');
        Route::post('/', [UserAddressController::class, 'store'])->name('store');
        Route::put('/{address}', [UserAddressController::class, 'update'])->name('update');
        Route::delete('/{address}', [UserAddressController::class, 'destroy'])->name('destroy');
    });

    // User Documents
    Route::prefix('users/{user}/documents')->name('users.documents.')->group(function () {
        Route::get('/', [UserDocumentController::class, 'index'])->name('index');
        Route::post('/', [UserDocumentController::class, 'store'])->name('store');
        Route::delete('/{document}', [UserDocumentController::class, 'destroy'])->name('destroy');
        Route::patch('/{document}/verify', [UserDocumentController::class, 'verify'])->name('verify');
    });

    // Guardian Profiles
    Route::put('guardians/{guardian}/profile', [GuardianProfileController::class, 'upsert'])
        ->name('guardians.profile.upsert');

    // Student Background
    Route::put('students/{student}/background', [StudentBackgroundController::class, 'upsert'])
        ->name('students.background.upsert');

    // Student Languages
    Route::post('students/{student}/languages', [StudentLanguageController::class, 'store'])
        ->name('students.languages.store');
    Route::delete('students/{student}/languages/{language}', [StudentLanguageController::class, 'destroy'])
        ->name('students.languages.destroy');

    // Family Profile
    Route::put('students/{student}/family-profile', [FamilyProfileController::class, 'upsert'])
        ->name('students.family-profile.upsert');

    // Demographic Profile
    Route::put('users/{user}/demographic-profile', [DemographicProfileController::class, 'upsert'])
        ->name('users.demographic-profile.upsert');

    // User Consents
    Route::post('users/{user}/consents', [UserConsentController::class, 'store'])
        ->name('users.consents.store');
    Route::patch('users/{user}/consents/{consent}/revoke', [UserConsentController::class, 'revoke'])
        ->name('users.consents.revoke');

    // Socioeconomic Profile
    Route::put('students/{student}/socioeconomic-profile', [SocioeconomicProfileController::class, 'upsert'])
        ->name('students.socioeconomic-profile.upsert');

    // Student Benefits
    Route::post('students/{student}/benefits/{benefit}', [StudentBenefitController::class, 'store'])
        ->name('students.benefits.store');
    Route::delete('students/{student}/benefits/{benefit}', [StudentBenefitController::class, 'destroy'])
        ->name('students.benefits.destroy');

    // Health Profile
    Route::put('users/{user}/health-profile', [HealthProfileController::class, 'upsert'])
        ->name('users.health-profile.upsert');

    // Housing Profile
    Route::put('students/{student}/housing-profile', [HousingProfileController::class, 'upsert'])
        ->name('students.housing-profile.upsert');
    Route::patch('students/{student}/housing-profile/services', [HousingProfileController::class, 'syncServices'])
        ->name('students.housing-profile.services.sync');

    // Grade Configs
    Route::get('grade-configs', [GradeConfigController::class, 'index'])->name('grade-configs.index');
    Route::get('grade-configs/create', [GradeConfigController::class, 'create'])->name('grade-configs.create');
    Route::post('grade-configs', [GradeConfigController::class, 'store'])->name('grade-configs.store');
    Route::get('grade-configs/{gradeConfig}/edit', [GradeConfigController::class, 'edit'])->name('grade-configs.edit');
    Route::patch('grade-configs/{gradeConfig}', [GradeConfigController::class, 'update'])->name('grade-configs.update');

    // Admin Attendance
    Route::get('attendance', [AdminAttendanceController::class, 'index'])->name('attendance.index');
    Route::get('sections/{section}/attendance', [AdminAttendanceController::class, 'sectionIndex'])->name('sections.attendance.index');
    Route::post('sections/{section}/attendance/sessions', [AdminAttendanceController::class, 'storeSession'])->name('sections.attendance.sessions.store');
    Route::get('sections/{section}/attendance/sessions/{classSession}', [AdminAttendanceController::class, 'sheet'])->name('sections.attendance.sheet');
    Route::put('sections/{section}/attendance/sessions/{classSession}', [AdminAttendanceController::class, 'upsertAttendance'])->name('sections.attendance.upsert');
});

Route::middleware(['auth', 'verified'])->prefix('academic')->name('academic.')->group(function () {
    Route::get('students/{student}', [StudentController::class, 'show'])->name('students.show');
    Route::get('students', [StudentController::class, 'index'])->name('students.index');

    Route::put('professors/{professor}/staff-profile', [StaffProfileController::class, 'upsert'])
        ->name('professors.staff-profile.upsert');

    Route::get('career-categories', [CareerCategoryController::class, 'index'])->name('career-categories.index');
    Route::post('career-categories', [CareerCategoryController::class, 'store'])->name('career-categories.store');
    Route::patch('career-categories/{careerCategory}', [CareerCategoryController::class, 'update'])->name('career-categories.update');
    Route::delete('career-categories/{careerCategory}', [CareerCategoryController::class, 'destroy'])->name('career-categories.destroy');

    Route::get('careers', [CareerController::class, 'index'])->name('careers.index');
    Route::post('careers', [CareerController::class, 'store'])->name('careers.store');
    Route::patch('careers/{career}', [CareerController::class, 'update'])->name('careers.update');
    Route::delete('careers/{career}', [CareerController::class, 'destroy'])->name('careers.destroy');

    Route::scopeBindings()->group(function () {
        Route::get('careers/{career}/pensums', [PensumController::class, 'index'])->name('pensums.index');
        Route::post('careers/{career}/pensums', [PensumController::class, 'store'])->name('pensums.store');
        Route::patch('careers/{career}/pensums/{pensum}', [PensumController::class, 'update'])->name('pensums.update');
        Route::delete('careers/{career}/pensums/{pensum}', [PensumController::class, 'destroy'])->name('pensums.destroy');

        Route::prefix('careers/{career}/pensums/{pensum}/subjects')->name('subjects.')->group(function () {
            Route::get('/', [SubjectController::class, 'index'])->name('index');
            Route::post('/', [SubjectController::class, 'store'])->name('store');
            Route::patch('/{subject}', [SubjectController::class, 'update'])->name('update');
            Route::delete('/{subject}', [SubjectController::class, 'destroy'])->name('destroy');
            Route::post('/{subject}/prerequisites/sync', [SubjectController::class, 'syncPrerequisites'])->name('prerequisites.sync');
        });
    });
});

Route::middleware(['auth', 'verified'])->prefix('infrastructure')->name('infrastructure.')->group(function () {
    Route::get('buildings', [BuildingController::class, 'index'])->name('buildings.index');
    Route::post('buildings', [BuildingController::class, 'store'])->name('buildings.store');
    Route::patch('buildings/{building}', [BuildingController::class, 'update'])->name('buildings.update');
    Route::delete('buildings/{building}', [BuildingController::class, 'destroy'])->name('buildings.destroy');

    Route::get('classrooms', [ClassroomController::class, 'index'])->name('classrooms.index');
    Route::post('classrooms', [ClassroomController::class, 'store'])->name('classrooms.store');
    Route::patch('classrooms/{classroom}', [ClassroomController::class, 'update'])->name('classrooms.update');
    Route::delete('classrooms/{classroom}', [ClassroomController::class, 'destroy'])->name('classrooms.destroy');
});

Route::middleware(['auth', 'verified'])->prefix('scheduling')->name('scheduling.')->group(function () {
    Route::get('periods', [PeriodController::class, 'index'])->name('periods.index');
    Route::post('periods', [PeriodController::class, 'store'])->name('periods.store');
    Route::patch('periods/{period}', [PeriodController::class, 'update'])->name('periods.update');
    Route::delete('periods/{period}', [PeriodController::class, 'destroy'])->name('periods.destroy');
    Route::patch('periods/{period}/activate', [PeriodController::class, 'activate'])->name('periods.activate');
    Route::patch('periods/{period}/close', [PeriodController::class, 'close'])->name('periods.close');

    Route::scopeBindings()->group(function () {
        Route::post('periods/{period}/lapses', [LapseController::class, 'store'])->name('lapses.store');
        Route::patch('periods/{period}/lapses/{lapse}', [LapseController::class, 'update'])->name('lapses.update');
        Route::delete('periods/{period}/lapses/{lapse}', [LapseController::class, 'destroy'])->name('lapses.destroy');
    });

    Route::get('professors', [ProfessorController::class, 'index'])->name('professors.index');
    Route::post('professors', [ProfessorController::class, 'store'])->name('professors.store');
    Route::patch('professors/{professor}', [ProfessorController::class, 'update'])->name('professors.update');
    Route::delete('professors/{professor}', [ProfessorController::class, 'destroy'])->name('professors.destroy');

    Route::get('sections/university', [UniversitySectionController::class, 'index'])->name('sections.university.index');
    Route::post('sections/university', [UniversitySectionController::class, 'store'])->name('sections.university.store');
    Route::patch('sections/university/{section}', [UniversitySectionController::class, 'update'])->name('sections.university.update');
    Route::delete('sections/university/{section}', [UniversitySectionController::class, 'destroy'])->name('sections.university.destroy');

    Route::get('sections/school', [SchoolSectionController::class, 'index'])->name('sections.school.index');
    Route::post('sections/school', [SchoolSectionController::class, 'store'])->name('sections.school.store');
    Route::patch('sections/school/{section}', [SchoolSectionController::class, 'update'])->name('sections.school.update');
    Route::delete('sections/school/{section}', [SchoolSectionController::class, 'destroy'])->name('sections.school.destroy');

    Route::get('schedules', [ScheduleController::class, 'index'])->name('schedules.index');
    Route::post('schedules', [ScheduleController::class, 'store'])->name('schedules.store');
    Route::patch('schedules/{schedule}', [ScheduleController::class, 'update'])->name('schedules.update');
    Route::delete('schedules/{schedule}', [ScheduleController::class, 'destroy'])->name('schedules.destroy');
});

Route::middleware(['auth', 'verified', 'role:Estudiante,Representante'])->prefix('enrollment')->name('enrollment.')->group(function () {
    Route::get('/', [EnrollmentController::class, 'index'])->name('index');
    Route::post('/', [EnrollmentController::class, 'store'])->name('store');
    Route::post('{enrollment}/detail', [EnrollmentController::class, 'addDetail'])->name('detail.store');
    Route::delete('{enrollment}/detail/{enrollmentDetail}', [EnrollmentController::class, 'removeDetail'])->name('detail.destroy');
    Route::post('{enrollment}/confirm', [EnrollmentController::class, 'confirm'])->name('confirm');
});

Route::middleware(['auth', 'verified', 'role:Profesor,Coordinador de Area'])
    ->prefix('professor')->name('professor.')
    ->group(function () {
        Route::get('dashboard', [Professor\DashboardController::class, 'index'])
            ->name('dashboard');
        Route::get('sections/{section}/grades', [Professor\GradeController::class, 'sheet'])
            ->name('grades.sheet');
        Route::put('sections/{section}/grades/entries', [Professor\GradeController::class, 'upsertEntry'])
            ->name('grades.entries.upsert');
        Route::post('sections/{section}/grades/publish', [Professor\GradeController::class, 'publishSlot'])
            ->name('grades.publish');
        Route::post('sections/{section}/enrollment-details/{enrollmentDetail}/remedial', [Professor\RemedialController::class, 'store'])
            ->name('grades.remedial.store');

        // Attendance
        Route::get('sections/{section}/attendance', [Professor\AttendanceController::class, 'index'])
            ->name('sections.attendance.index');
        Route::post('sections/{section}/attendance/sessions', [Professor\AttendanceController::class, 'storeSession'])
            ->name('sections.attendance.sessions.store');
        Route::get('sections/{section}/attendance/sessions/{classSession}', [Professor\AttendanceController::class, 'sheet'])
            ->name('sections.attendance.sheet');
        Route::put('sections/{section}/attendance/sessions/{classSession}', [Professor\AttendanceController::class, 'upsertAttendance'])
            ->name('sections.attendance.upsert');
    });

Route::middleware(['auth', 'verified', 'role:Estudiante'])
    ->prefix('student')->name('student.')
    ->group(function () {
        Route::get('dashboard', [Student\DashboardController::class, 'index'])
            ->name('dashboard');
        Route::get('grades', [Student\GradeController::class, 'index'])
            ->name('grades.index');
    });

Route::middleware(['auth', 'verified', 'role:Representante'])
    ->prefix('guardian')->name('guardian.')
    ->group(function () {
        Route::get('dashboard', [Guardian\DashboardController::class, 'index'])
            ->name('dashboard');
        Route::get('grades', [Guardian\GradeController::class, 'index'])
            ->name('grades.index');
    });

require __DIR__.'/settings.php';

if (app()->environment(['testing', 'local'])) {
    Route::get('/_test/trigger-401', fn () => abort(401))->name('test.trigger401');
    Route::get('/_test/trigger-500', fn () => abort(500))->name('test.trigger500');
}
