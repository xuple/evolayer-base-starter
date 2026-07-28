<?php

namespace App\Console\Commands;

use App\Support\EvoLayer\StarterBaseDependencyIdentity;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;
use Xuple\EvoLayer\Base\Support\PublishMap;
use Xuple\EvoLayer\Base\Support\ResyncManifestRepository;

#[Signature('evolayer:manifest:refresh {--check : Check the tracked manifest without writing it}')]
#[Description('Refresh the distribution-strict Starter manifest from canonical Base descriptors and exact shipped files.')]
final class RefreshEvoLayerManifestCommand extends Command
{
    public function handle(
        PublishMap $map,
        ResyncManifestRepository $manifests,
        StarterBaseDependencyIdentity $base,
    ): int {
        try {
            $current = $manifests->read($map);

            if (($current['surfaces'] ?? []) !== []) {
                throw new \RuntimeException('Distribution manifests cannot contain ejected surfaces.');
            }

            $expected = $this->expectedManifest($map, $base);
            $currentComparable = $current;
            unset($currentComparable['generated_at'], $expected['generated_at']);

            if ((bool) $this->option('check')) {
                if ($currentComparable !== $expected) {
                    $this->components->error('The tracked Starter manifest is stale.');

                    return self::FAILURE;
                }

                $this->components->info('The tracked Starter manifest is current.');

                return self::SUCCESS;
            }

            $expected['generated_at'] = date(DATE_ATOM);
            $manifests->write($map, $expected);
            $this->components->info('The tracked Starter manifest was refreshed.');

            return self::SUCCESS;
        } catch (Throwable) {
            $this->components->error('The tracked Starter manifest could not be refreshed safely.');

            return self::FAILURE;
        }
    }

    /** @return array<string, mixed> */
    private function expectedManifest(PublishMap $map, StarterBaseDependencyIdentity $base): array
    {
        $identity = $base->binding($map->hostRoot(), requireDistribution: true);

        if ($identity === null) {
            throw new \RuntimeException('The locked Base dependency is not an immutable distribution.');
        }

        $files = [];

        foreach ($map->managedFiles() as $key => $managed) {
            if (! is_file($managed['source']) || ! is_readable($managed['source'])
                || ! is_file($managed['target']) || ! is_readable($managed['target'])) {
                throw new \RuntimeException('A managed source or target is missing.');
            }

            $sourceSha = hash_file('sha256', $managed['source']);
            $installedSha = hash_file('sha256', $managed['target']);

            if ($sourceSha === false || $installedSha === false
                || $installedSha !== $sourceSha) {
                throw new \RuntimeException('A managed target does not exactly match the locked Base source.');
            }

            $files[$key] = [
                'surface' => $managed['surface'],
                'source_sha' => $sourceSha,
                'installed_sha' => $installedSha,
            ];
        }

        ksort($files);

        return [
            'schema_version' => ResyncManifestRepository::SCHEMA_VERSION,
            'package_version' => $identity['installed_version'],
            'package_reference' => $identity['installed_reference'],
            'surfaces' => [],
            'files' => $files,
        ];
    }
}
