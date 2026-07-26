<?php

namespace App\Support\EvoLayer;

use App\Models\User;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Schema;
use Laravel\Fortify\Features;
use Xuple\EvoLayer\Base\Contracts\ProfileVerificationCheck;
use Xuple\EvoLayer\Base\Support\ProfileTransitions\ProfileVerificationContext;
use Xuple\EvoLayer\Base\Support\ProfileTransitions\ProfileVerificationManager;
use Xuple\EvoLayer\Base\Support\ProfileTransitions\ProfileVerificationResult;

final readonly class StarterApplicationVerificationCheck implements ProfileVerificationCheck
{
    public function __construct(private StarterProfilePaths $paths, private Router $router) {}

    public function id(): string
    {
        return 'starter.application-posture';
    }

    public function apiVersion(): int
    {
        return ProfileVerificationManager::CONTRACT_VERSION;
    }

    public function capability(): string
    {
        return 'starter-application';
    }

    public function verify(ProfileVerificationContext $context): ProfileVerificationResult
    {
        if ($context->expected->definition->id !== 'application') {
            return ProfileVerificationResult::pass();
        }

        if (Features::enabled(Features::registration())
            || $this->router->getRoutes()->getByName('register') !== null
            || is_file($this->paths->path('resources/js/pages/auth/register.tsx'))
            || $this->frontendContainsRegistrationContract() !== false) {
            return ProfileVerificationResult::fail(
                'starter-registration-drift',
                'Clear configuration and routes, reconcile Starter registration source, regenerate Wayfinder, then retry.',
            );
        }

        if ($this->knownDemoUserExists()) {
            return ProfileVerificationResult::fail(
                'starter-demo-credentials-present',
                'Run php artisan evolayer:starter:reconcile-demo-user or manually reconcile the modified account, then retry.',
            );
        }

        if (! $this->guidanceMatchesApplication()) {
            return ProfileVerificationResult::fail(
                'starter-guidance-drift',
                'Restore or explicitly reconcile the generated application profile guidance, then retry.',
            );
        }

        if (! $this->pageSurfaceContractIsCurrent()) {
            return ProfileVerificationResult::fail(
                'starter-page-surface-drift',
                'Restore the reviewed Starter page-surface resolver and retry.',
            );
        }

        if (! $this->packageExamplesAreSafelyReferenced()) {
            return ProfileVerificationResult::fail(
                'starter-disabled-surface-drift',
                'Reconcile the reviewed Starter navigation and remove package route or action imports, then retry.',
            );
        }

        if (! $this->publicLandingMatchesExpectedSurface($context)) {
            return ProfileVerificationResult::fail(
                'starter-public-landing-drift',
                'Restore the reviewed Starter public landing route and expected landing source, then retry.',
            );
        }

        return ProfileVerificationResult::pass();
    }

    public function fingerprint(ProfileVerificationContext $context): array
    {
        return [
            'files' => $this->fileFingerprints([
                'AGENTS.md',
                'CLAUDE.md',
                'README.md',
                'config/fortify.php',
                'database/seeders/DatabaseSeeder.php',
                'resources/js/app.tsx',
                'resources/js/layouts/public-layout.tsx',
                'resources/js/lib/page-surfaces.ts',
                'resources/js/pages/auth/login.tsx',
                'resources/js/pages/auth/register.tsx',
                'resources/js/pages/welcome.tsx',
                'routes/web.php',
                ...StarterSurfaceInventory::packageExampleSources(),
            ]),
            'registration_enabled' => Features::enabled(Features::registration()),
            'registration_route' => $this->router->getRoutes()->getByName('register') !== null,
            'known_demo_user_count' => $this->knownDemoUserExists() ? 1 : 0,
        ];
    }

    private function frontendContainsRegistrationContract(): bool
    {
        foreach (StarterSurfaceInventory::registrationSources() as $relative) {
            $contents = $this->read($relative);

            if ($contents === null
                || str_contains($contents, '@/routes/register')
                || str_contains($contents, 'register()')) {
                return true;
            }
        }

        return false;
    }

    private function knownDemoUserExists(): bool
    {
        return Schema::hasTable('users')
            && User::query()->where('email', 'test@example.com')->exists();
    }

    private function guidanceMatchesApplication(): bool
    {
        foreach (['README.md', 'AGENTS.md', 'CLAUDE.md'] as $relative) {
            $contents = $this->read($relative);

            if ($contents === null
                || ! StarterApplicationProfileContributor::hasApplicationGuidance($contents)) {
                return false;
            }
        }

        return true;
    }

    private function pageSurfaceContractIsCurrent(): bool
    {
        $contents = $this->read('resources/js/lib/page-surfaces.ts');

        return $contents !== null
            && str_contains($contents, "return 'public';")
            && str_contains($contents, "'administration'")
            && str_contains($contents, "name.startsWith('auth/')")
            && str_contains($contents, "name.startsWith('settings/')");
    }

    private function packageExamplesAreSafelyReferenced(): bool
    {
        foreach (StarterSurfaceInventory::packageExampleSources() as $relative) {
            $contents = $this->read($relative);

            if ($contents === null
                || ! StarterSurfaceInventory::packageExamplesAreSafelyReferenced($contents)) {
                return false;
            }
        }

        return true;
    }

    private function publicLandingMatchesExpectedSurface(ProfileVerificationContext $context): bool
    {
        $route = $this->router->getRoutes()->getByName('welcome');
        $component = $route?->defaults['component'] ?? null;
        $marketingPagesEnabled = (bool) ($context->expected->examples['marketing_pages'] ?? false);

        if ($marketingPagesEnabled) {
            return $component === 'evolayer/base'
                && is_file($this->paths->path('resources/js/pages/evolayer/base.tsx'));
        }

        return $component === 'welcome'
            && is_file($this->paths->path('resources/js/pages/welcome.tsx'));
    }

    /** @param list<string> $paths @return array<string, string|null> */
    private function fileFingerprints(array $paths): array
    {
        $fingerprints = [];

        foreach ($paths as $relative) {
            $path = $this->paths->path($relative);
            $fingerprints[$relative] = is_file($path) && is_readable($path)
                ? (@hash_file('sha256', $path) ?: null)
                : null;
        }

        return $fingerprints;
    }

    private function read(string $relative): ?string
    {
        $path = $this->paths->path($relative);

        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }

        $contents = @file_get_contents($path);

        return $contents === false ? null : $contents;
    }
}
