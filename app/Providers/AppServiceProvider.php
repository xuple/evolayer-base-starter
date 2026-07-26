<?php

namespace App\Providers;

use App\Support\EvoLayer\StarterApplicationProfileContributor;
use App\Support\EvoLayer\StarterApplicationVerificationCheck;
use App\Support\EvoLayer\StarterGeneratedContractsVerificationCheck;
use App\Support\EvoLayer\StarterProfilePaths;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Xuple\EvoLayer\Base\Support\ProfileTransitions\ProfileDefinition;
use Xuple\EvoLayer\Base\Support\ProfileTransitions\ProfileRegistry;
use Xuple\EvoLayer\Base\Support\ProfileTransitions\ProfileTransitionManager;
use Xuple\EvoLayer\Base\Support\ProfileTransitions\ProfileVerificationManager;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(StarterProfilePaths::class);
        $this->app->tag(
            StarterApplicationProfileContributor::class,
            ProfileTransitionManager::CONTRIBUTOR_TAG,
        );
        $this->app->tag([
            StarterApplicationVerificationCheck::class,
            StarterGeneratedContractsVerificationCheck::class,
        ], ProfileVerificationManager::CHECK_TAG);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(ProfileRegistry $profiles): void
    {
        $profiles->register(new ProfileDefinition(
            id: 'application',
            schemaVersion: 1,
            examples: array_fill_keys(array_keys((array) config('evolayer.base.examples')), false),
            features: array_fill_keys(array_keys((array) config('evolayer.base.features')), false),
            requiredCapabilities: [
                'profile.committed-intent',
                'profile.environment-projection',
                'profile.managed-surfaces',
                'starter.registration',
                'starter.seeding',
                'starter.guidance',
                'starter.frontend',
                'starter.page-surfaces',
            ],
            allowedOverrides: ['examples', 'features'],
            verificationRequirements: ['generated-contracts', 'starter-application'],
        ));

        $this->configureDefaults();
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
