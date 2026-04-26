<?php

namespace App\Providers;

use App\Models\Career;
use App\Models\CareerCategory;
use App\Models\Coordination;
use App\Models\Pensum;
use App\Models\Subject;
use App\Models\User;
use App\Policies\Academic\CareerCategoryPolicy;
use App\Policies\Academic\CareerPolicy;
use App\Policies\Academic\PensumPolicy;
use App\Policies\Academic\SubjectPolicy;
use App\Policies\CoordinationPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
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
