<?php

namespace App\Http\Responses\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\Fortify;

trait ResolvesLoginRedirect
{
    protected function resolveRedirect(Request $request): string
    {
        $user = $request->user();

        if ($user?->hasRole('Admin')) {
            $adminTeam = $user->teams()->where('slug', 'admin')->first();
            $targetTeam = $adminTeam ?? $user->personalTeam();

            if ($targetTeam) {
                $user->switchTeam($targetTeam);
            }

            return '/admin/dashboard';
        }

        $team = $user?->currentTeam ?? $user?->personalTeam();

        if ($team) {
            URL::defaults(['current_team' => $team->slug]);

            return "/{$team->slug}".Fortify::redirects('login');
        }

        return match (true) {
            $user?->hasAnyRole(['Profesor', 'Coordinador de Area']) => route('professor.dashboard'),
            $user?->hasRole('Estudiante') => route('student.dashboard'),
            $user?->hasRole('Representante') => route('guardian.dashboard'),
            default => '/',
        };
    }
}
