<?php

use App\Models\User;
use App\Support\EvoLayer\StarterApplicationProfileContributor;
use App\Support\EvoLayer\StarterApplicationVerificationCheck;
use App\Support\EvoLayer\StarterBaseDependencyIdentity;
use App\Support\EvoLayer\StarterEffectiveRouteFingerprint;
use App\Support\EvoLayer\StarterGeneratedContractsVerificationCheck;
use App\Support\EvoLayer\StarterPreparationEvidence;
use App\Support\EvoLayer\StarterProfilePaths;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Spatie\Permission\Models\Role;
use Symfony\Component\Process\Process;
use Xuple\EvoLayer\Base\Support\ManagedPathPolicy;
use Xuple\EvoLayer\Base\Support\ProfileTransitions\ExpectedProfileState;
use Xuple\EvoLayer\Base\Support\ProfileTransitions\ProfileRegistry;
use Xuple\EvoLayer\Base\Support\ProfileTransitions\ProfileTransitionContext;
use Xuple\EvoLayer\Base\Support\ProfileTransitions\ProfileTransitionExecutor;
use Xuple\EvoLayer\Base\Support\ProfileTransitions\ProfileTransitionPlan;
use Xuple\EvoLayer\Base\Support\ProfileTransitions\ProfileVerificationContext;
use Xuple\EvoLayer\Base\Support\ProfileTransitions\ProfileVerificationManager;

uses(RefreshDatabase::class);

function applicationProfileFixture(): string
{
    $root = sys_get_temp_dir().'/evolayer-starter-profile-'.bin2hex(random_bytes(4));
    File::ensureDirectoryExists($root.'/.evolayer');
    File::ensureDirectoryExists($root.'/resources/js/pages/auth');
    File::ensureDirectoryExists($root.'/stubs/profiles/demo/resources/js/pages/auth');
    $registration = (string) file_get_contents(base_path('stubs/profiles/demo/resources/js/pages/auth/register.tsx'));
    file_put_contents($root.'/resources/js/pages/auth/register.tsx', $registration);
    file_put_contents($root.'/stubs/profiles/demo/resources/js/pages/auth/register.tsx', $registration);
    file_put_contents($root.'/.evolayer/project.json', json_encode([
        'schema_version' => 2,
        'kind' => 'generated-application',
        'profile' => 'demo',
        'overrides' => ['examples' => [], 'features' => []],
        'applied_with' => ['base' => '0.2.0-rc.1', 'starter' => '0.2.0-rc.1'],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    $guidance = <<<'MD'
# Generated application

<!-- evolayer-application-profile:start -->
> **Operational profile: demo.** Public registration and bundled examples are enabled for local exploration. The seeded test@example.com / password account is demonstration-only. Apply the application profile before treating this repository as an application deployment.
<!-- evolayer-application-profile:end -->
MD;

    foreach (['README.md', 'AGENTS.md', 'CLAUDE.md'] as $document) {
        file_put_contents($root.'/'.$document, $guidance.PHP_EOL);
    }

    return $root;
}

function applicationProfileContext(string $profile): ProfileTransitionContext
{
    $definition = app(ProfileRegistry::class)->get($profile);

    return new ProfileTransitionContext(
        profile: $profile,
        environmentPath: '/tmp/unused-profile-environment',
        currentExamples: [],
        targetExamples: $definition?->examples ?? [],
        currentFeatures: [],
        targetFeatures: $definition?->features ?? [],
        definition: $definition,
        writeEnvironment: false,
    );
}

/**
 * @param  array<string, bool>  $exampleOverrides
 * @param  array<string, bool>  $featureOverrides
 */
function applicationVerificationContext(
    array $exampleOverrides = [],
    array $featureOverrides = [],
): ProfileVerificationContext {
    $definition = app(ProfileRegistry::class)->get('application');

    return new ProfileVerificationContext(
        expected: new ExpectedProfileState(
            $definition,
            array_replace($definition->examples, $exampleOverrides),
            array_replace($definition->features, $featureOverrides),
        ),
        status: [],
        bindings: [
            'intent' => hash('sha256', 'intent'),
            'effective' => hash('sha256', 'effective'),
            'manifest' => hash('sha256', 'manifest'),
            'managed' => hash('sha256', 'managed'),
        ],
        baseVersion: '0.2.0-rc.1',
        baseReference: null,
        hostVersion: '0.2.0-rc.1',
        hostReference: null,
    );
}

function copyApplicationVerificationSource(string $root): void
{
    foreach ([
        'config/fortify.php',
        'database/seeders/DatabaseSeeder.php',
        'resources/js/app.tsx',
        'resources/js/layouts/public-layout.tsx',
        'resources/js/lib/page-surfaces.ts',
        'resources/js/pages/auth/login.tsx',
        'resources/js/pages/welcome.tsx',
        'resources/js/config/navigation.ts',
    ] as $relative) {
        File::ensureDirectoryExists(dirname($root.'/'.$relative));
        File::copy(base_path($relative), $root.'/'.$relative);
    }
}

function prepareGeneratedContractFixture(string $root): StarterPreparationEvidence
{
    copyApplicationVerificationSource($root);

    foreach ([
        'routes/web.php',
        'app/Http/Controllers/Controller.php',
        'ontology.yaml',
        'config/evolayer.php',
        'composer.lock',
    ] as $relative) {
        File::ensureDirectoryExists(dirname($root.'/'.$relative));
        File::copy(base_path($relative), $root.'/'.$relative);
    }

    foreach ([
        'resources/js/actions/index.ts',
        'resources/js/routes/index.ts',
        'resources/js/wayfinder/index.ts',
        'resources/js/types/ontology.ts',
    ] as $relative) {
        File::ensureDirectoryExists(dirname($root.'/'.$relative));
        file_put_contents($root.'/'.$relative, "export {};\n");
    }

    $context = applicationVerificationContext();
    $evidence = new StarterPreparationEvidence(
        new StarterProfilePaths($root),
        app(StarterBaseDependencyIdentity::class),
        app(StarterEffectiveRouteFingerprint::class),
    );
    $evidence->write(
        $context->expected->definition->id,
        $context->expected->examples,
        $context->expected->features,
    );

    return $evidence;
}

function configureApplicationVerificationRoutes(): void
{
    app('router')->setRoutes(new RouteCollection);
    Route::inertia('/', 'welcome')->name('welcome');
    app('router')->getRoutes()->refreshNameLookups();
}

afterEach(function () {
    if (isset($this->profileRoot)) {
        File::deleteDirectory($this->profileRoot);
    }
});

test('the Starter registers application policy without changing the Base generic profiles', function () {
    $registry = app(ProfileRegistry::class);
    $application = $registry->get('application');

    expect($registry->ids())->toContain('application', 'demo', 'lean')
        ->and($application)->not->toBeNull()
        ->and($application->requiredCapabilities)->toContain(
            'starter.registration',
            'starter.seeding',
            'starter.guidance',
            'starter.frontend',
            'starter.page-surfaces',
        )
        ->and($application->verificationRequirements)->toBe([
            'generated-contracts',
            'starter-application',
        ])
        ->and($application->examples)->each->toBeFalse()
        ->and($application->features)->each->toBeFalse();
});

test('application and demo transitions are exact and idempotent for Starter-owned source', function () {
    $this->profileRoot = applicationProfileFixture();
    $contributor = new StarterApplicationProfileContributor(new StarterProfilePaths($this->profileRoot));
    $executor = new ProfileTransitionExecutor(new ManagedPathPolicy);

    $applicationPlan = new ProfileTransitionPlan;
    $contributor->contribute(applicationProfileContext('application'), $applicationPlan);
    $executor->execute($applicationPlan);

    expect($this->profileRoot.'/resources/js/pages/auth/register.tsx')->not->toBeFile();

    foreach (['README.md', 'AGENTS.md', 'CLAUDE.md'] as $document) {
        expect(file_get_contents($this->profileRoot.'/'.$document))
            ->toContain('**Operational profile: application.**')
            ->not->toContain('test@example.com');
    }

    $repeated = new ProfileTransitionPlan;
    $contributor->contribute(applicationProfileContext('application'), $repeated);
    expect($repeated->affectedPaths())->toBe([]);

    $demoPlan = new ProfileTransitionPlan;
    $contributor->contribute(applicationProfileContext('demo'), $demoPlan);
    $executor->execute($demoPlan);

    expect(file_get_contents($this->profileRoot.'/resources/js/pages/auth/register.tsx'))
        ->toBe(file_get_contents($this->profileRoot.'/stubs/profiles/demo/resources/js/pages/auth/register.tsx'));
});

test('modified registration source blocks application transition without mutation', function () {
    $this->profileRoot = applicationProfileFixture();
    $target = $this->profileRoot.'/resources/js/pages/auth/register.tsx';
    file_put_contents($target, file_get_contents($target)."\n// host-owned\n");
    $before = file_get_contents($target);
    $contributor = new StarterApplicationProfileContributor(new StarterProfilePaths($this->profileRoot));
    $plan = new ProfileTransitionPlan;

    $contributor->contribute(applicationProfileContext('application'), $plan);

    expect($plan->conflicts())->toContain(
        'Starter registration source is modified or host-owned; reconcile it explicitly before disabling registration.',
    )->and(file_get_contents($target))->toBe($before);
});

test('legacy application mode remains identity and may transition only by explicit selection', function () {
    $this->profileRoot = applicationProfileFixture();
    file_put_contents($this->profileRoot.'/.evolayer/project.json', json_encode([
        'mode' => 'application',
        'distribution' => 'xuple/evolayer-base-starter',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    $contributor = new StarterApplicationProfileContributor(new StarterProfilePaths($this->profileRoot));
    $plan = new ProfileTransitionPlan;

    $contributor->contribute(applicationProfileContext('application'), $plan);

    expect($plan->conflicts())->toBeEmpty()
        ->and(json_decode((string) file_get_contents($this->profileRoot.'/.evolayer/project.json'), true))
        ->not->toHaveKey('profile');
});

test('application posture removes registration routes while retaining the remaining auth surface', function () {
    $process = new Process(
        [PHP_BINARY, 'artisan', 'route:list', '--json'],
        base_path(),
        ['EVOLAYER_BASE_PROFILE' => 'application'],
    );
    $process->mustRun();
    $routes = collect(json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR));
    $names = $routes->pluck('name')->filter()->all();

    expect($names)->not->toContain('register', 'register.store')
        ->and($names)->toContain(
            'login',
            'login.store',
            'password.request',
            'password.email',
            'verification.notice',
            'two-factor.login',
            'passkey.login',
        );
});

test('application posture resolves the public landing route to host-owned source', function () {
    $process = new Process(
        [PHP_BINARY, 'artisan', 'route:list', '--json'],
        base_path(),
        ['EVOLAYER_BASE_PROFILE' => 'application'],
    );
    $process->mustRun();

    $route = collect(json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR))
        ->firstWhere('name', 'welcome');

    expect($route)->not->toBeNull()
        ->and(base_path('resources/js/pages/welcome.tsx'))->toBeFile();
});

test('application seeding never creates the known demonstration account', function () {
    config()->set('evolayer.base.profile', 'application');

    $this->seed(DatabaseSeeder::class);

    expect(User::query()->where('email', 'test@example.com')->exists())->toBeFalse();
});

test('demo-user reconciliation never deletes a legacy account without provenance', function () {
    config()->set('evolayer.base.profile', 'application');
    $role = Role::firstOrCreate(['name' => 'admin']);
    $demo = User::factory()->create([
        'name' => 'Ada Lovelace',
        'email' => 'test@example.com',
        'password' => 'password',
    ]);
    $demo->assignRole($role);
    $other = User::factory()->create();

    $this->artisan('evolayer:starter:reconcile-demo-user')->assertFailed();

    expect($demo->fresh())->not->toBeNull()
        ->and($other->fresh())->not->toBeNull();
});

test('the Starter application posture check passes only for reconciled host state', function () {
    $this->profileRoot = applicationProfileFixture();
    copyApplicationVerificationSource($this->profileRoot);
    $contributor = new StarterApplicationProfileContributor(new StarterProfilePaths($this->profileRoot));
    $plan = new ProfileTransitionPlan;
    $contributor->contribute(applicationProfileContext('application'), $plan);
    (new ProfileTransitionExecutor(new ManagedPathPolicy))->execute($plan);
    config()->set('fortify.features', array_values(array_filter(
        config('fortify.features'),
        fn (string $feature): bool => $feature !== Features::registration(),
    )));
    configureApplicationVerificationRoutes();
    $check = new StarterApplicationVerificationCheck(
        new StarterProfilePaths($this->profileRoot),
        app('router'),
    );

    expect($check->verify(applicationVerificationContext())->passed)->toBeTrue()
        ->and($check->fingerprint(applicationVerificationContext()))
        ->not->toHaveKey('environment');

    file_put_contents(
        $this->profileRoot.'/resources/js/layouts/public-layout.tsx',
        "import { register } from '@/routes/register';\n",
    );

    $result = $check->verify(applicationVerificationContext());
    expect($result->passed)->toBeFalse()
        ->and($result->errorCode)->toBe('starter-registration-drift');
});

test('application verification accepts the package landing when marketing pages are re-enabled', function () {
    $this->profileRoot = applicationProfileFixture();
    copyApplicationVerificationSource($this->profileRoot);
    File::ensureDirectoryExists($this->profileRoot.'/resources/js/pages/evolayer');
    File::put(
        $this->profileRoot.'/resources/js/pages/evolayer/base.tsx',
        "export default function Base() { return null; }\n",
    );
    $contributor = new StarterApplicationProfileContributor(new StarterProfilePaths($this->profileRoot));
    $plan = new ProfileTransitionPlan;
    $contributor->contribute(applicationProfileContext('application'), $plan);
    (new ProfileTransitionExecutor(new ManagedPathPolicy))->execute($plan);
    config()->set('fortify.features', array_values(array_filter(
        config('fortify.features'),
        fn (string $feature): bool => $feature !== Features::registration(),
    )));
    app('router')->setRoutes(new RouteCollection);
    Route::inertia('/', 'evolayer/base')->name('welcome');
    app('router')->getRoutes()->refreshNameLookups();
    $check = new StarterApplicationVerificationCheck(
        new StarterProfilePaths($this->profileRoot),
        app('router'),
    );

    expect($check->verify(applicationVerificationContext([
        'marketing_pages' => true,
    ]))->passed)->toBeTrue();
});

test('the internal preparation recorder is hidden from the command list', function () {
    $this->artisan('list')
        ->doesntExpectOutputToContain('evolayer:starter:record-preparation')
        ->assertSuccessful();
});

test('application guidance validation ignores unrelated downstream credential references', function () {
    $this->profileRoot = applicationProfileFixture();
    copyApplicationVerificationSource($this->profileRoot);
    $contributor = new StarterApplicationProfileContributor(new StarterProfilePaths($this->profileRoot));
    $plan = new ProfileTransitionPlan;
    $contributor->contribute(applicationProfileContext('application'), $plan);
    (new ProfileTransitionExecutor(new ManagedPathPolicy))->execute($plan);
    file_put_contents(
        $this->profileRoot.'/README.md',
        file_get_contents($this->profileRoot.'/README.md')."\nUser-authored example: test@example.com\n",
    );
    config()->set('fortify.features', array_values(array_filter(
        config('fortify.features'),
        fn (string $feature): bool => $feature !== Features::registration(),
    )));
    configureApplicationVerificationRoutes();

    $check = new StarterApplicationVerificationCheck(
        new StarterProfilePaths($this->profileRoot),
        app('router'),
    );

    expect($check->verify(applicationVerificationContext())->passed)->toBeTrue();
});

test('application verification rejects Starter navigation coupled to disabled package routes', function () {
    $this->profileRoot = applicationProfileFixture();
    copyApplicationVerificationSource($this->profileRoot);
    $contributor = new StarterApplicationProfileContributor(new StarterProfilePaths($this->profileRoot));
    $plan = new ProfileTransitionPlan;
    $contributor->contribute(applicationProfileContext('application'), $plan);
    (new ProfileTransitionExecutor(new ManagedPathPolicy))->execute($plan);
    file_put_contents(
        $this->profileRoot.'/resources/js/config/navigation.ts',
        "import { contact } from '@/routes/evolayer';\n",
    );
    config()->set('fortify.features', array_values(array_filter(
        config('fortify.features'),
        fn (string $feature): bool => $feature !== Features::registration(),
    )));
    configureApplicationVerificationRoutes();

    $check = new StarterApplicationVerificationCheck(
        new StarterProfilePaths($this->profileRoot),
        app('router'),
    );

    $result = $check->verify(applicationVerificationContext());
    expect($result->passed)->toBeFalse()
        ->and($result->errorCode)->toBe('starter-disabled-surface-drift');
});

test('application verification permits a downstream navigation that removes an example entry', function () {
    $this->profileRoot = applicationProfileFixture();
    copyApplicationVerificationSource($this->profileRoot);
    $contributor = new StarterApplicationProfileContributor(new StarterProfilePaths($this->profileRoot));
    $plan = new ProfileTransitionPlan;
    $contributor->contribute(applicationProfileContext('application'), $plan);
    (new ProfileTransitionExecutor(new ManagedPathPolicy))->execute($plan);
    file_put_contents(
        $this->profileRoot.'/resources/js/config/navigation.ts',
        "export const sidebarPrimaryNavItems = [];\n",
    );
    config()->set('fortify.features', array_values(array_filter(
        config('fortify.features'),
        fn (string $feature): bool => $feature !== Features::registration(),
    )));
    configureApplicationVerificationRoutes();

    $check = new StarterApplicationVerificationCheck(
        new StarterProfilePaths($this->profileRoot),
        app('router'),
    );

    expect($check->verify(applicationVerificationContext())->passed)->toBeTrue();
});

test('application verification fails cleanly when an inventoried host source is missing', function () {
    $this->profileRoot = applicationProfileFixture();
    copyApplicationVerificationSource($this->profileRoot);
    $contributor = new StarterApplicationProfileContributor(new StarterProfilePaths($this->profileRoot));
    $plan = new ProfileTransitionPlan;
    $contributor->contribute(applicationProfileContext('application'), $plan);
    (new ProfileTransitionExecutor(new ManagedPathPolicy))->execute($plan);
    config()->set('fortify.features', array_values(array_filter(
        config('fortify.features'),
        fn (string $feature): bool => $feature !== Features::registration(),
    )));
    configureApplicationVerificationRoutes();
    $check = new StarterApplicationVerificationCheck(new StarterProfilePaths($this->profileRoot), app('router'));

    $layout = $this->profileRoot.'/resources/js/layouts/public-layout.tsx';
    $layoutContents = file_get_contents($layout);
    File::delete($layout);
    expect($check->verify(applicationVerificationContext())->errorCode)->toBe('starter-registration-drift');
    file_put_contents($layout, $layoutContents);

    $readme = $this->profileRoot.'/README.md';
    $readmeContents = file_get_contents($readme);
    File::delete($readme);
    expect($check->verify(applicationVerificationContext())->errorCode)->toBe('starter-guidance-drift');
    file_put_contents($readme, $readmeContents);

    File::delete($this->profileRoot.'/resources/js/config/navigation.ts');
    expect($check->verify(applicationVerificationContext())->errorCode)->toBe('starter-disabled-surface-drift');
});

test('the generated-contract check requires current preparation evidence', function () {
    $this->profileRoot = applicationProfileFixture();
    $evidence = prepareGeneratedContractFixture($this->profileRoot);
    $context = applicationVerificationContext();

    $check = new StarterGeneratedContractsVerificationCheck(
        new StarterProfilePaths($this->profileRoot),
        $evidence,
        app(StarterEffectiveRouteFingerprint::class),
    );

    $bindings = $evidence->bindings(
        $context->expected->definition->id,
        $context->expected->examples,
        $context->expected->features,
    );

    expect($check->verify($context)->passed)->toBeTrue()
        ->and($check->fingerprint($context)['generated_tree'])
        ->not->toBeEmpty()
        ->and($check->fingerprint($context))
        ->toHaveKeys(['effective_profile', 'frontend_scope', 'routes', 'preparation'])
        ->and($bindings)->toHaveKeys(['base', 'effective_routes_sha256'])
        ->and($bindings['base'])->toHaveKeys([
            'installed_version',
            'installed_reference',
            'locked_source_reference',
            'locked_dist_reference',
            'composer_lock_sha256',
        ]);

    $lock = $this->profileRoot.'/composer.lock';
    $lockContents = (string) file_get_contents($lock);
    file_put_contents($lock, $lockContents.PHP_EOL);
    expect($evidence->state(
        $context->expected->definition->id,
        $context->expected->examples,
        $context->expected->features,
    ))->toBe('stale');
    file_put_contents($lock, $lockContents);

    file_put_contents($this->profileRoot.'/routes/web.php', "<?php\n// stale\n");

    $result = $check->verify($context);
    expect($result->passed)->toBeFalse()
        ->and($result->errorCode)->toBe('starter-preparation-stale');
});

test('generated-contract verification fails cleanly for missing outputs and malformed evidence', function () {
    $this->profileRoot = applicationProfileFixture();
    $evidence = prepareGeneratedContractFixture($this->profileRoot);
    $check = new StarterGeneratedContractsVerificationCheck(
        new StarterProfilePaths($this->profileRoot),
        $evidence,
        app(StarterEffectiveRouteFingerprint::class),
    );

    File::deleteDirectory($this->profileRoot.'/resources/js/routes');
    expect($check->verify(applicationVerificationContext())->errorCode)
        ->toBe('starter-generated-contracts-failed');

    File::ensureDirectoryExists($this->profileRoot.'/resources/js/routes');
    file_put_contents($this->profileRoot.'/resources/js/routes/index.ts', "export {};\n");
    File::delete($this->profileRoot.'/resources/js/types/ontology.ts');
    expect($check->verify(applicationVerificationContext())->errorCode)
        ->toBe('starter-generated-contracts-failed');

    file_put_contents($this->profileRoot.'/resources/js/types/ontology.ts', "export {};\n");
    file_put_contents($this->profileRoot.'/storage/framework/cache/data/evolayer-starter-preparation.json', '{invalid');

    expect($check->verify(applicationVerificationContext())->errorCode)
        ->toBe('starter-preparation-invalid');
});

test('application verification fails closed when Starter capabilities are absent', function () {
    $results = (new ProfileVerificationManager)->verify(applicationVerificationContext());

    expect($results)->toContainEqual([
        'id' => 'required.generated-contracts',
        'capability' => 'generated-contracts',
        'passed' => false,
        'error' => 'verification-capability-missing',
        'corrective_action' => 'Register a profile verification check that provides [generated-contracts].',
        'fingerprint' => null,
    ])->and($results)->toContainEqual([
        'id' => 'required.starter-application',
        'capability' => 'starter-application',
        'passed' => false,
        'error' => 'verification-capability-missing',
        'corrective_action' => 'Register a profile verification check that provides [starter-application].',
        'fingerprint' => null,
    ]);
});
