<?php

namespace App\Providers;

use App\Models\Career;
use App\Models\CareerCategory;
use App\Models\Catalog;
use App\Models\Catalogs\AcademicShift;
use App\Models\Catalogs\AcademicStatus;
use App\Models\Catalogs\AdmissionType;
use App\Models\Catalogs\AttachmentDocumentType;
use App\Models\Catalogs\BasicService;
use App\Models\Catalogs\BloodType;
use App\Models\Catalogs\CommuteTime;
use App\Models\Catalogs\ConstructionMaterial;
use App\Models\Catalogs\ContractType;
use App\Models\Catalogs\DedicationType;
use App\Models\Catalogs\DigitalLevel;
use App\Models\Catalogs\DisabilityType;
use App\Models\Catalogs\DocumentType;
use App\Models\Catalogs\EducationLevel;
use App\Models\Catalogs\EmploymentStatus;
use App\Models\Catalogs\EmploymentType;
use App\Models\Catalogs\Gender;
use App\Models\Catalogs\GeographicZone;
use App\Models\Catalogs\HouseholdHeadType;
use App\Models\Catalogs\HousingType;
use App\Models\Catalogs\IncomeRange;
use App\Models\Catalogs\IncomeSource;
use App\Models\Catalogs\InstitutionalBenefit;
use App\Models\Catalogs\InstitutionType;
use App\Models\Catalogs\InsuranceType;
use App\Models\Catalogs\KinshipType;
use App\Models\Catalogs\Language;
use App\Models\Catalogs\LanguageLevel;
use App\Models\Catalogs\LivingArrangement;
use App\Models\Catalogs\MaritalStatus;
use App\Models\Catalogs\Religion;
use App\Models\Catalogs\SchoolGrade;
use App\Models\Catalogs\StudyModality;
use App\Models\Catalogs\TenureType;
use App\Models\Catalogs\TransferReason;
use App\Models\Catalogs\TransportType;
use App\Models\ClassSession;
use App\Models\Coordination;
use App\Models\DemographicProfile;
use App\Models\Enrollment;
use App\Models\EnrollmentDetail;
use App\Models\FamilyProfile;
use App\Models\Guardian;
use App\Models\HealthProfile;
use App\Models\HousingProfile;
use App\Models\Pensum;
use App\Models\SocioeconomicProfile;
use App\Models\StaffProfile;
use App\Models\StudentBackground;
use App\Models\StudentBenefit;
use App\Models\StudentLanguage;
use App\Models\Subject;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserConsent;
use App\Models\UserDocument;
use App\Observers\CatalogObserver;
use App\Policies\Academic\CareerCategoryPolicy;
use App\Policies\Academic\CareerPolicy;
use App\Policies\Academic\PensumPolicy;
use App\Policies\Academic\SubjectPolicy;
use App\Policies\ClassSessionPolicy;
use App\Policies\CoordinationPolicy;
use App\Policies\DemographicProfilePolicy;
use App\Policies\EnrollmentDetailPolicy;
use App\Policies\EnrollmentPolicy;
use App\Policies\FamilyProfilePolicy;
use App\Policies\GuardianPolicy;
use App\Policies\HealthProfilePolicy;
use App\Policies\HousingProfilePolicy;
use App\Policies\RolePolicy;
use App\Policies\SocioeconomicProfilePolicy;
use App\Policies\StaffProfilePolicy;
use App\Policies\StudentBackgroundPolicy;
use App\Policies\StudentBenefitPolicy;
use App\Policies\StudentLanguagePolicy;
use App\Policies\UserAddressPolicy;
use App\Policies\UserConsentPolicy;
use App\Policies\UserDocumentPolicy;
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
        $this->configureCatalogObservers();
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
        Gate::policy(UserAddress::class, UserAddressPolicy::class);
        Gate::policy(UserDocument::class, UserDocumentPolicy::class);
        Gate::policy(StaffProfile::class, StaffProfilePolicy::class);
        Gate::policy(StudentBackground::class, StudentBackgroundPolicy::class);
        Gate::policy(FamilyProfile::class, FamilyProfilePolicy::class);
        Gate::policy(DemographicProfile::class, DemographicProfilePolicy::class);
        Gate::policy(UserConsent::class, UserConsentPolicy::class);
        Gate::policy(SocioeconomicProfile::class, SocioeconomicProfilePolicy::class);
        Gate::policy(Guardian::class, GuardianPolicy::class);
        Gate::policy(StudentBenefit::class, StudentBenefitPolicy::class);
        Gate::policy(StudentLanguage::class, StudentLanguagePolicy::class);
        Gate::policy(HealthProfile::class, HealthProfilePolicy::class);
        Gate::policy(HousingProfile::class, HousingProfilePolicy::class);
        Gate::policy(ClassSession::class, ClassSessionPolicy::class);
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
     * Register CatalogObserver for all catalog models.
     */
    protected function configureCatalogObservers(): void
    {
        foreach ($this->catalogModels() as $model) {
            $model::observe(CatalogObserver::class);
        }
    }

    /**
     * @return list<class-string<Catalog>>
     */
    private function catalogModels(): array
    {
        return [
            AcademicShift::class,
            AcademicStatus::class,
            AdmissionType::class,
            AttachmentDocumentType::class,
            BasicService::class,
            BloodType::class,
            CommuteTime::class,
            ConstructionMaterial::class,
            ContractType::class,
            DedicationType::class,
            DigitalLevel::class,
            DisabilityType::class,
            DocumentType::class,
            EducationLevel::class,
            EmploymentStatus::class,
            EmploymentType::class,
            Gender::class,
            GeographicZone::class,
            HouseholdHeadType::class,
            HousingType::class,
            IncomeRange::class,
            IncomeSource::class,
            InstitutionalBenefit::class,
            InstitutionType::class,
            InsuranceType::class,
            KinshipType::class,
            Language::class,
            LanguageLevel::class,
            LivingArrangement::class,
            MaritalStatus::class,
            Religion::class,
            SchoolGrade::class,
            StudyModality::class,
            TenureType::class,
            TransferReason::class,
            TransportType::class,
        ];
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
