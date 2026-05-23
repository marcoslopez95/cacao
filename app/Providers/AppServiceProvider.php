<?php

namespace App\Providers;

use App\Models\Career;
use App\Models\CareerCategory;
use App\Models\Coordination;
use App\Models\Enrollment;
use App\Models\EnrollmentDetail;
use App\Models\Pensum;
use App\Models\Subject;
use App\Models\User;
use App\Policies\Academic\CareerCategoryPolicy;
use App\Policies\Academic\CareerPolicy;
use App\Policies\Academic\PensumPolicy;
use App\Policies\Academic\SubjectPolicy;
use App\Policies\CoordinationPolicy;
use App\Policies\EnrollmentDetailPolicy;
use App\Policies\EnrollmentPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Fortify;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthorization();
        $this->configureRedirects();
    }

    /**
     * Configure global authorization rules.
     */
    protected function configureAuthorization(): void
    {
        Gate::before(fn (User $user) => $user->hasRole('Admin') ? true : null);

        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Coordination::class, CoordinationPolicy::class);
        Gate::policy(CareerCategory::class, CareerCategoryPolicy::class);
        Gate::policy(Career::class, CareerPolicy::class);
        Gate::policy(Pensum::class, PensumPolicy::class);
        Gate::policy(Subject::class, SubjectPolicy::class);
        Gate::policy(Enrollment::class, EnrollmentPolicy::class);
        Gate::policy(EnrollmentDetail::class, EnrollmentDetailPolicy::class);
    }

    /**
     * Override the default guest-middleware redirect so authenticated users
     * are sent to their role-specific dashboard instead of route('dashboard'),
     * which requires a {current_team} parameter that non-admin users don't have.
     */
    protected function configureRedirects(): void
    {
        RedirectIfAuthenticated::redirectUsing(function (Request $request): string {
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
                default => '/',
            };
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
