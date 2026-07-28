<?php

namespace App\Support\EvoLayer;

use Composer\InstalledVersions;
use JsonException;

final class StarterBaseDependencyIdentity
{
    private const PACKAGE = 'xuple/evolayer-base';

    /**
     * @return array{
     *     installed_version: string,
     *     installed_reference: string,
     *     locked_source_reference: string|null,
     *     locked_dist_reference: string|null,
     *     composer_lock_sha256: string
     * }|null
     */
    public function binding(string $root, bool $requireDistribution = false): ?array
    {
        if (! InstalledVersions::isInstalled(self::PACKAGE)) {
            return null;
        }

        $version = InstalledVersions::getPrettyVersion(self::PACKAGE);
        $reference = InstalledVersions::getReference(self::PACKAGE);
        $lockPath = rtrim($root, '/').'/composer.lock';

        if (! is_string($version) || $version === ''
            || ! is_string($reference) || $reference === ''
            || ! is_file($lockPath) || ! is_readable($lockPath)) {
            return null;
        }

        $contents = @file_get_contents($lockPath);
        $checksum = @hash_file('sha256', $lockPath);

        if ($contents === false || $checksum === false) {
            return null;
        }

        try {
            $lock = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        $package = collect(array_merge(
            is_array($lock['packages'] ?? null) ? $lock['packages'] : [],
            is_array($lock['packages-dev'] ?? null) ? $lock['packages-dev'] : [],
        ))->firstWhere('name', self::PACKAGE);

        if (! is_array($package)) {
            return null;
        }

        $lockedSourceReference = $package['source']['reference'] ?? null;
        $lockedDistReference = $package['dist']['reference'] ?? null;
        $distType = $package['dist']['type'] ?? null;

        if (($package['version'] ?? null) !== $version
            || ! in_array($reference, [$lockedSourceReference, $lockedDistReference], true)
            || ($requireDistribution && $distType !== 'zip')) {
            return null;
        }

        return [
            'installed_version' => $version,
            'installed_reference' => $reference,
            'locked_source_reference' => is_string($lockedSourceReference) ? $lockedSourceReference : null,
            'locked_dist_reference' => is_string($lockedDistReference) ? $lockedDistReference : null,
            'composer_lock_sha256' => $checksum,
        ];
    }
}
