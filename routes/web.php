<?php

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

require __DIR__.'/settings.php';
