<?php

namespace App\Http\Responses\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\Fortify;

trait ResolvesLoginRedirect
{
    private function resolveRedirect(Request $request): string
    {
        $user = $request->user();
        $team = $user?->currentTeam ?? $user?->personalTeam();

        if ($team) {
            URL::defaults(['current_team' => $team->slug]);

            return "/{$team->slug}".Fortify::redirects('login');
        }

        return match (true) {
            $user?->hasAnyRole(['Profesor', 'Coordinador de Area']) => route('professor.dashboard'),
            $user?->hasRole('Estudiante') => route('student.dashboard'),
            $user?->hasRole('Representante') => route('guardian.dashboard'),
            default => route('home'),
        };
    }
}
