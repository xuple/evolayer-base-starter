<?php

namespace App\Support\EvoLayer;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;
use Xuple\EvoLayer\Base\Contracts\ProfileVerificationCheck;
use Xuple\EvoLayer\Base\Support\ProfileTransitions\ProfileVerificationContext;
use Xuple\EvoLayer\Base\Support\ProfileTransitions\ProfileVerificationManager;
use Xuple\EvoLayer\Base\Support\ProfileTransitions\ProfileVerificationResult;

final readonly class StarterGeneratedContractsVerificationCheck implements ProfileVerificationCheck
{
    public function __construct(
        private StarterProfilePaths $paths,
        private StarterPreparationEvidence $preparation,
        private StarterEffectiveRouteFingerprint $routes,
    ) {}

    public function id(): string
    {
        return 'starter.generated-contracts';
    }

    public function apiVersion(): int
    {
        return ProfileVerificationManager::CONTRACT_VERSION;
    }

    public function capability(): string
    {
        return 'generated-contracts';
    }

    public function verify(ProfileVerificationContext $context): ProfileVerificationResult
    {
        if (! $this->contractsExist()
            || ($context->expected->definition->id === 'application' && $this->generatedRegistrationRouteExists())) {
            return ProfileVerificationResult::fail(
                'starter-generated-contracts-failed',
                'Regenerate Wayfinder and ontology contracts, then run the reviewed Starter profile preparation script.',
            );
        }

        $preparationState = $this->preparation->state(
            $context->expected->definition->id,
            $context->expected->examples,
            $context->expected->features,
        );

        if ($preparationState !== 'current') {
            return ProfileVerificationResult::fail(
                'starter-preparation-'.$preparationState,
                'Run npm run profile:prepare, then retry verification.',
            );
        }

        return ProfileVerificationResult::pass();
    }

    public function fingerprint(ProfileVerificationContext $context): array
    {
        return [
            'effective_profile' => $context->expected->definition->id,
            'effective_examples' => $context->expected->examples,
            'effective_features' => $context->expected->features,
            'generated_tree' => $this->treeFingerprints(),
            'frontend_scope' => $this->frontendFingerprints(),
            'routes' => $this->routes->routes(),
            'preparation' => $this->preparation->bindings(
                $context->expected->definition->id,
                $context->expected->examples,
                $context->expected->features,
            ),
        ];
    }

    private function contractsExist(): bool
    {
        return is_dir($this->paths->path('resources/js/actions'))
            && is_dir($this->paths->path('resources/js/routes'))
            && is_dir($this->paths->path('resources/js/wayfinder'))
            && is_file($this->paths->path('resources/js/types/ontology.ts'));
    }

    private function generatedRegistrationRouteExists(): bool
    {
        $path = $this->paths->path('resources/js/routes/register');

        return is_dir($path) || is_file($path.'.ts');
    }

    /** @return array<string, string> */
    private function treeFingerprints(): array
    {
        $fingerprints = [];

        try {
            foreach (['resources/js/actions', 'resources/js/routes', 'resources/js/wayfinder', 'resources/js/types/ontology.ts'] as $relative) {
                $path = $this->paths->path($relative);

                if (is_file($path) && is_readable($path)) {
                    $checksum = @hash_file('sha256', $path);

                    if ($checksum !== false) {
                        $fingerprints[$relative] = $checksum;
                    }

                    continue;
                }

                if (! is_dir($path) || ! is_readable($path)) {
                    continue;
                }

                $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

                foreach ($iterator as $file) {
                    if ($file->isFile() && $file->isReadable()) {
                        $key = str_replace($this->paths->root.'/', '', $file->getPathname());
                        $checksum = @hash_file('sha256', $file->getPathname());

                        if ($checksum !== false) {
                            $fingerprints[$key] = $checksum;
                        }
                    }
                }
            }
        } catch (Throwable) {
            return [];
        }

        ksort($fingerprints);

        return $fingerprints;
    }

    /** @return array<string, string|null> */
    private function frontendFingerprints(): array
    {
        $fingerprints = [];

        foreach (StarterSurfaceInventory::frontendFingerprintPaths() as $relative) {
            $path = $this->paths->path($relative);
            $fingerprints[$relative] = is_file($path) ? hash_file('sha256', $path) : null;
        }

        return $fingerprints;
    }
}
