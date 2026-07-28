<?php

use Composer\InstalledVersions;
use Illuminate\Support\Facades\File;
use Xuple\EvoLayer\Base\Support\PublishMap;

beforeEach(function () {
    $this->manifestRoot = sys_get_temp_dir().'/evolayer-starter-manifest-'.bin2hex(random_bytes(4));
    File::ensureDirectoryExists($this->manifestRoot.'/package');
    File::ensureDirectoryExists($this->manifestRoot.'/host/resources/js');
    file_put_contents($this->manifestRoot.'/package/example.ts', "export const example = true;\n");
    file_put_contents($this->manifestRoot.'/host/resources/js/example.ts', "export const example = true;\n");
    file_put_contents($this->manifestRoot.'/host/composer.lock', json_encode([
        'packages' => [[
            'name' => 'xuple/evolayer-base',
            'version' => InstalledVersions::getPrettyVersion('xuple/evolayer-base'),
            'dist' => [
                'type' => 'zip',
                'url' => 'https://example.invalid/evolayer-base.zip',
                'reference' => InstalledVersions::getReference('xuple/evolayer-base'),
            ],
        ]],
        'packages-dev' => [],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);

    $root = $this->manifestRoot;
    app()->instance(PublishMap::class, new class($root) extends PublishMap
    {
        public function __construct(private readonly string $root) {}

        public function packageRoot(): string
        {
            return $this->root.'/package';
        }

        public function hostRoot(): string
        {
            return $this->root.'/host';
        }

        public function manifestPath(): string
        {
            return $this->hostRoot().'/.evolayer/resync.lock.json';
        }

        public function core(): array
        {
            return [
                $this->packageRoot().'/example.ts' => $this->hostRoot().'/resources/js/example.ts',
            ];
        }

        public function features(): array
        {
            return ['feature' => []];
        }
    });
});

afterEach(function () {
    File::deleteDirectory($this->manifestRoot);
});

test('manifest maintenance refreshes and checks canonical managed provenance', function () {
    $this->artisan('evolayer:manifest:refresh')->assertSuccessful();
    $this->artisan('evolayer:manifest:refresh', ['--check' => true])->assertSuccessful();

    $manifest = json_decode(
        (string) file_get_contents($this->manifestRoot.'/host/.evolayer/resync.lock.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($manifest)
        ->toHaveKeys(['package_version', 'package_reference', 'files'])
        ->and($manifest['files'])->toHaveKey('resources/js/example.ts');

    file_put_contents($this->manifestRoot.'/host/resources/js/example.ts', "export const modified = true;\n");

    $this->artisan('evolayer:manifest:refresh')->assertFailed();
});

test('manifest maintenance rejects ejections and dependency provenance drift', function () {
    $this->artisan('evolayer:manifest:refresh')->assertSuccessful();
    $manifestPath = $this->manifestRoot.'/host/.evolayer/resync.lock.json';
    $manifest = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
    $manifest['surfaces'] = ['feature' => 'ejected'];
    file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);

    $this->artisan('evolayer:manifest:refresh')->assertFailed();

    $manifest['surfaces'] = [];
    file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    $lock = json_decode(
        (string) file_get_contents($this->manifestRoot.'/host/composer.lock'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
    $lock['packages'][0]['dist']['reference'] = str_repeat('0', 40);
    file_put_contents(
        $this->manifestRoot.'/host/composer.lock',
        json_encode($lock, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL,
    );

    $this->artisan('evolayer:manifest:refresh')->assertFailed();
});
