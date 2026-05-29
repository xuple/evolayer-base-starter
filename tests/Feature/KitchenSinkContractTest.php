<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Locks the documented kitchen-sink install posture.
 *
 * The canonical list of EVOLAYER_BASE_* flags lives in config/evolayer.php
 * (package-owned). This starter promises every one of them is enabled by
 * default in .env.example, and that the same set surfaces on the shared
 * Inertia prop. Disabling a flag must drop it from the prop, not merely
 * hide a nav entry — the rule documented in CONTRIBUTING.md.
 */
class KitchenSinkContractTest extends TestCase
{
    public function test_env_example_enables_every_documented_flag(): void
    {
        $envKeys = $this->envKeysReadByConfig();
        $envExample = (string) file_get_contents(base_path('.env.example'));

        $this->assertNotEmpty(
            $envKeys,
            'Expected config/evolayer.php to read at least one EVOLAYER_BASE_* env key.',
        );

        foreach ($envKeys as $envKey) {
            $this->assertMatchesRegularExpression(
                "/^{$envKey}=true\s*$/m",
                $envExample,
                ".env.example must set {$envKey}=true to preserve kitchen-sink install posture (README#features).",
            );
        }
    }

    public function test_shared_prop_carries_examples_and_features_under_evolayer_base(): void
    {
        $shared = $this->app->make(HandleInertiaRequests::class)
            ->share(Request::create('/'));

        $this->assertArrayHasKey('evolayer', $shared);
        $this->assertArrayHasKey('base', $shared['evolayer']);
        $this->assertArrayHasKey('examples', $shared['evolayer']['base']);
        $this->assertArrayHasKey('features', $shared['evolayer']['base']);

        foreach (array_keys((array) config('evolayer.base.examples')) as $key) {
            $this->assertArrayHasKey(
                $key,
                $shared['evolayer']['base']['examples'],
                "Shared prop is missing evolayer.base.examples.{$key}.",
            );
        }
        foreach (array_keys((array) config('evolayer.base.features')) as $key) {
            $this->assertArrayHasKey(
                $key,
                $shared['evolayer']['base']['features'],
                "Shared prop is missing evolayer.base.features.{$key}.",
            );
        }
    }

    public function test_disabling_an_example_flag_drops_it_from_the_shared_prop(): void
    {
        // Picking the first key dynamically keeps the test resilient to
        // upstream renames; the rule under test is structural, not per-feature.
        $exampleKeys = array_keys((array) config('evolayer.base.examples'));
        $this->assertNotEmpty($exampleKeys, 'No EVOLAYER_BASE_EXAMPLE_* keys are configured.');

        $victim = $exampleKeys[0];
        Config::set("evolayer.base.examples.{$victim}", false);

        $shared = $this->app->make(HandleInertiaRequests::class)
            ->share(Request::create('/'));

        $this->assertFalse(
            $shared['evolayer']['base']['examples'][$victim],
            "Disabling evolayer.base.examples.{$victim} should propagate to the shared prop.",
        );
    }

    /**
     * Parse config/evolayer.php for the EVOLAYER_BASE_* keys it actually
     * reads, so the contract anchors on the package-owned config rather
     * than a hand-maintained list. Snake-case → SCREAMING_SNAKE_CASE
     * mapping is enforced by what env() is actually called with.
     *
     * @return list<string>
     */
    private function envKeysReadByConfig(): array
    {
        $configPath = config_path('evolayer.php');
        $this->assertFileExists($configPath, 'config/evolayer.php must exist for the kitchen-sink contract.');

        $configText = (string) file_get_contents($configPath);

        preg_match_all("/env\(['\"](EVOLAYER_BASE_[A-Z_]+)['\"]/", $configText, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }
}
