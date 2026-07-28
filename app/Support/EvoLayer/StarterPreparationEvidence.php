<?php

namespace App\Support\EvoLayer;

use JsonException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

final readonly class StarterPreparationEvidence
{
    public const CONTRACT_VERSION = 2;

    public function __construct(
        private StarterProfilePaths $paths,
        private StarterBaseDependencyIdentity $base,
        private StarterEffectiveRouteFingerprint $routes,
    ) {}

    /** @param array<string, bool> $examples @param array<string, bool> $features */
    public function write(string $profile, array $examples, array $features): void
    {
        $bindings = $this->bindings($profile, $examples, $features);

        if ($bindings === null) {
            throw new \RuntimeException('Starter preparation inputs or generated outputs are missing or unreadable.');
        }

        $receipt = [
            'schema_version' => 1,
            'preparation_contract_version' => self::CONTRACT_VERSION,
            'bindings' => $bindings,
            'prepared_at' => date(DATE_ATOM),
        ];
        $path = $this->paths->preparationReceipt();
        $directory = dirname($path);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new \RuntimeException('Unable to create the Starter preparation receipt directory.');
        }

        $temporary = tempnam($directory, '.evolayer-starter-preparation-');

        if ($temporary === false) {
            throw new \RuntimeException('Unable to stage the Starter preparation receipt.');
        }

        try {
            $encoded = json_encode($receipt, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;

            if (file_put_contents($temporary, $encoded, LOCK_EX) === false || ! rename($temporary, $path)) {
                throw new \RuntimeException('Unable to atomically write the Starter preparation receipt.');
            }
        } catch (Throwable $exception) {
            @unlink($temporary);

            throw $exception;
        }
    }

    /** @param array<string, bool> $examples @param array<string, bool> $features */
    public function state(string $profile, array $examples, array $features): string
    {
        $path = $this->paths->preparationReceipt();

        if (! is_file($path)) {
            return 'missing';
        }

        $contents = @file_get_contents($path);

        if ($contents === false) {
            return 'invalid';
        }

        try {
            $receipt = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return 'invalid';
        }

        if (! is_array($receipt)
            || ($receipt['schema_version'] ?? null) !== 1
            || ($receipt['preparation_contract_version'] ?? null) !== self::CONTRACT_VERSION
            || ! is_array($receipt['bindings'] ?? null)
            || ! is_string($receipt['prepared_at'] ?? null)) {
            return 'invalid';
        }

        $bindings = $this->bindings($profile, $examples, $features);

        return $bindings !== null && $receipt['bindings'] === $bindings ? 'current' : 'stale';
    }

    /** @param array<string, bool> $examples @param array<string, bool> $features @return array<string, mixed>|null */
    public function bindings(string $profile, array $examples, array $features): ?array
    {
        $routeInputs = $this->treeFingerprints(['routes', 'app/Http/Controllers']);
        $ontologyInputs = $this->fileFingerprints(['ontology.yaml', 'config/evolayer.php']);
        $frontendInputs = $this->fileFingerprints(StarterSurfaceInventory::frontendFingerprintPaths());
        $generatedOutputs = $this->treeFingerprints([
            'resources/js/actions',
            'resources/js/routes',
            'resources/js/wayfinder',
            'resources/js/types/ontology.ts',
        ]);
        $base = $this->base->binding($this->paths->root);
        $effectiveRoutesSha = $this->routes->hash();

        if ($routeInputs === null
            || $ontologyInputs === null
            || $frontendInputs === null
            || $generatedOutputs === null
            || $base === null
            || $effectiveRoutesSha === '') {
            return null;
        }

        ksort($examples);
        ksort($features);

        return [
            'profile' => $profile,
            'examples' => $examples,
            'features' => $features,
            'base' => $base,
            'effective_routes_sha256' => $effectiveRoutesSha,
            'route_inputs_sha256' => $this->hash($routeInputs),
            'ontology_inputs_sha256' => $this->hash($ontologyInputs),
            'frontend_inputs_sha256' => $this->hash($frontendInputs),
            'generated_outputs_sha256' => $this->hash($generatedOutputs),
        ];
    }

    /** @param list<string> $relativePaths @return array<string, string>|null */
    private function fileFingerprints(array $relativePaths): ?array
    {
        $fingerprints = [];

        foreach ($relativePaths as $relative) {
            $path = $this->paths->path($relative);

            if (! is_file($path) || ! is_readable($path)) {
                return null;
            }

            $checksum = @hash_file('sha256', $path);

            if ($checksum === false) {
                return null;
            }

            $fingerprints[$relative] = $checksum;
        }

        ksort($fingerprints);

        return $fingerprints;
    }

    /** @param list<string> $relativePaths @return array<string, string>|null */
    private function treeFingerprints(array $relativePaths): ?array
    {
        $fingerprints = [];

        try {
            foreach ($relativePaths as $relative) {
                $path = $this->paths->path($relative);

                if (is_file($path)) {
                    $files = $this->fileFingerprints([$relative]);

                    if ($files === null) {
                        return null;
                    }

                    $fingerprints += $files;

                    continue;
                }

                if (! is_dir($path) || ! is_readable($path)) {
                    return null;
                }

                $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

                foreach ($iterator as $file) {
                    if (! $file->isFile() || ! $file->isReadable()) {
                        continue;
                    }

                    $key = str_replace($this->paths->root.'/', '', $file->getPathname());
                    $checksum = @hash_file('sha256', $file->getPathname());

                    if ($checksum === false) {
                        return null;
                    }

                    $fingerprints[$key] = $checksum;
                }
            }
        } catch (Throwable) {
            return null;
        }

        if ($fingerprints === []) {
            return null;
        }

        ksort($fingerprints);

        return $fingerprints;
    }

    /** @param array<string, mixed> $value */
    private function hash(array $value): string
    {
        return hash('sha256', json_encode($value, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }
}
