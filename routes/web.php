<?php

use App\Http\Controllers\Academic\CareerCategoryController;
use App\Http\Controllers\Academic\CareerController;
use App\Http\Controllers\Academic\PensumController;
use App\Http\Controllers\Auth\AcceptInvitationController;
use App\Http\Controllers\Security\CoordinationAssignmentController;
use App\Http\Controllers\Security\CoordinationController;
use App\Http\Controllers\Security\InvitationController;
use App\Http\Controllers\Security\RoleController;
use App\Http\Controllers\Security\UserController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::get('invitations/{token}', [AcceptInvitationController::class, 'show'])->name('invitation.show')->whereUuid('token');
Route::post('invitations/{token}', [AcceptInvitationController::class, 'store'])->name('invitation.store')->whereUuid('token');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
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
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::patch('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::patch('users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');

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
});

Route::middleware(['auth', 'verified'])->prefix('academic')->name('academic.')->group(function () {
    Route::get('career-categories', [CareerCategoryController::class, 'index'])->name('career-categories.index');
    Route::post('career-categories', [CareerCategoryController::class, 'store'])->name('career-categories.store');
    Route::patch('career-categories/{careerCategory}', [CareerCategoryController::class, 'update'])->name('career-categories.update');
    Route::delete('career-categories/{careerCategory}', [CareerCategoryController::class, 'destroy'])->name('career-categories.destroy');

    Route::get('careers', [CareerController::class, 'index'])->name('careers.index');
    Route::post('careers', [CareerController::class, 'store'])->name('careers.store');
    Route::patch('careers/{career}', [CareerController::class, 'update'])->name('careers.update');
    Route::delete('careers/{career}', [CareerController::class, 'destroy'])->name('careers.destroy');

    Route::get('careers/{career}/pensums', [PensumController::class, 'index'])->name('pensums.index');
    Route::post('careers/{career}/pensums', [PensumController::class, 'store'])->name('pensums.store');
    Route::patch('careers/{career}/pensums/{pensum}', [PensumController::class, 'update'])->name('pensums.update');
    Route::delete('careers/{career}/pensums/{pensum}', [PensumController::class, 'destroy'])->name('pensums.destroy');
});

require __DIR__.'/settings.php';
