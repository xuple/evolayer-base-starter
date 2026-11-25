<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ShellContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_home_is_host_owned_and_package_home_is_absent(): void
    {
        $this->assertSame('/', route('welcome', absolute: false));
        $this->assertSame('/home', route('home', absolute: false));
        $this->assertSame('/home', config('fortify.home'));

        // /home is host-owned; the package must not register an auth landing route.
        $this->assertNull(Route::getRoutes()->getByName('evolayer.base.home'));
    }

    public function test_ssr_listener_follows_the_sources_laravel_reads(): void
    {
        $default = ['host' => '127.0.0.1', 'port' => 13714];

        // No .env and no process value: the default from config/inertia.php.
        $this->assertSame($default, $this->resolveSsrListener(null, null)['listener']);

        // .env beats the default; quotes are handled like phpdotenv.
        $this->assertSame(
            ['host' => '127.0.0.1', 'port' => 14001],
            $this->resolveSsrListener("INERTIA_SSR_URL=\"http://127.0.0.1:14001\"\n", null)['listener'],
        );

        // The process environment beats .env.
        $this->assertSame(
            ['host' => '10.1.2.3', 'port' => 14004],
            $this->resolveSsrListener("INERTIA_SSR_URL=http://127.0.0.1:14001\n", 'http://10.1.2.3:14004')['listener'],
        );

        // Files Vite would layer, but Laravel never reads, are ignored.
        $this->assertSame(
            ['host' => '127.0.0.1', 'port' => 14001],
            $this->resolveSsrListener(
                "INERTIA_SSR_URL=http://127.0.0.1:14001\n",
                null,
                ['.env.local' => "INERTIA_SSR_URL=http://10.8.8.8:15003\n", '.env.production' => "INERTIA_SSR_URL=http://10.9.9.9:15002\n"],
            )['listener'],
        );
        $this->assertSame(
            $default,
            $this->resolveSsrListener("APP_NAME=Starter\n", null, ['.env.local' => "INERTIA_SSR_URL=http://10.8.8.8:15003\n"])['listener'],
        );

        // A set but blank value fails instead of binding the default.
        foreach ([["INERTIA_SSR_URL=\n", null], ["INERTIA_SSR_URL=\"\"\n", null], ["INERTIA_SSR_URL=http://127.0.0.1:14001\n", ''], [null, '   ']] as [$dotenv, $processValue]) {
            $result = $this->resolveSsrListener($dotenv, $processValue);

            $this->assertNull($result['listener']);
            $this->assertStringContainsString('INERTIA_SSR_URL is set but blank', $result['error']);
        }
    }

    /**
     * Runs the resolver exported by vite.config.ts in a scratch directory.
     *
     * @param  array<string, string>  $extraFiles
     * @return array{listener: array{host: string, port: int}|null, error: string}
     */
    private function resolveSsrListener(?string $dotenv, ?string $processValue, array $extraFiles = []): array
    {
        $directory = sys_get_temp_dir().'/ssr-listener-'.bin2hex(random_bytes(6));
        mkdir($directory);

        $files = $extraFiles + ($dotenv === null ? [] : ['.env' => $dotenv]);

        foreach ($files as $name => $contents) {
            file_put_contents($directory.'/'.$name, $contents);
        }

        $script = <<<'JS'
            const { pathToFileURL } = await import('node:url');
            const config = await import(pathToFileURL(process.argv[1]).href);
            process.stdout.write(JSON.stringify(config.resolveConfiguredInertiaSsrListener(process.argv[2])));
            JS;

        $process = new Process(
            ['node', '--input-type=module', '--eval', $script, base_path('vite.config.ts'), $directory],
            base_path(),
            // false removes the variable from the child; the test must not inherit one.
            ['INERTIA_SSR_URL' => $processValue ?? false],
        );
        $process->run();

        foreach (array_keys($files) as $name) {
            unlink($directory.'/'.$name);
        }

        rmdir($directory);

        return [
            'listener' => $process->isSuccessful() ? json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR) : null,
            'error' => $process->getErrorOutput(),
        ];
    }

    public function test_public_landing_renders_the_evolayer_base_explainer(): void
    {
        $component = config('evolayer.base.examples.marketing_pages')
            ? 'evolayer/base'
            : 'welcome';

        $this->get(route('welcome'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component($component),
            );
    }

    public function test_authenticated_home_requires_authentication(): void
    {
        $this->get(route('home'))
            ->assertRedirect(route('login'));
    }

    public function test_verified_user_can_visit_authenticated_home(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('home'),
            );
    }

    public function test_shell_mounts_the_global_command_palette(): void
    {
        $app = (string) file_get_contents(resource_path('js/app.tsx'));
        $shell = (string) file_get_contents(resource_path('js/components/app-shell.tsx'));
        $header = (string) file_get_contents(resource_path('js/components/app-header.tsx'));

        $this->assertSame(1, substr_count($app, '<CommandPaletteProvider>'));
        $this->assertSame(2, substr_count($shell, '<CommandPaletteDialog />'));
        $this->assertStringContainsString('useCommandPalette()', $header);
        $this->assertStringContainsString('onClick={open}', $header);
    }

    public function test_inertia_title_callback_uses_the_app_name_suffix_contract(): void
    {
        $app = (string) file_get_contents(resource_path('js/app.tsx'));

        $this->assertStringContainsString(
            "const appName = import.meta.env.VITE_APP_NAME || 'EvoLayer Base';",
            $app,
        );
        $this->assertStringContainsString(
            'const site = page.props.site as { name?: string } | undefined;',
            $app,
        );
        $this->assertStringContainsString('const siteName = site?.name?.trim() || appName;', $app);
        $this->assertStringContainsString('title: formatInertiaTitle,', $app);
        $this->assertStringContainsString('cleanTitle.endsWith(` | ${siteName}`)', $app);
        $this->assertStringNotContainsString('`${title} - ${appName}`', $app);
    }

    public function test_public_landing_chrome_uses_the_evolayer_brand_contract(): void
    {
        $layout = (string) file_get_contents(resource_path('js/layouts/public-layout.tsx'));
        $config = (string) file_get_contents(config_path('site.php'));
        $env = (string) file_get_contents(base_path('.env.example'));

        $this->assertStringContainsString("import { useBrand } from '@/hooks/use-brand';", $layout);
        $this->assertStringContainsString('const brand = useBrand();', $layout);
        $this->assertStringContainsString('const resolvedTitle = title ?? brand.name;', $layout);
        $this->assertStringContainsString('const resolvedDescription = description ?? brand.description;', $layout);
        $this->assertStringContainsString('{brand.name}', $layout);
        $this->assertStringNotContainsString('const { auth, name } = usePage().props;', $layout);

        if (config('evolayer.base.examples.marketing_pages')) {
            $base = (string) file_get_contents(resource_path('js/pages/evolayer/base.tsx'));

            $this->assertStringNotContainsString('<Head title={brand.name} />', $base);
            $this->assertStringNotContainsString('title="EvoLayer Base"', $base);
        } else {
            $this->assertFileDoesNotExist(resource_path('js/pages/evolayer/base.tsx'));
        }
        $this->assertStringContainsString("\$brandName = \$value('EVOLAYER_BASE_BRAND_NAME', \$appName);", $config);
        $this->assertStringContainsString("\$siteName = \$value('SITE_NAME', \$brandName);", $config);
        $this->assertStringContainsString('SITE_TITLE_TEMPLATE=', $env);
    }

    public function test_registration_links_use_the_nullable_server_route_contract(): void
    {
        $layout = (string) file_get_contents(resource_path('js/layouts/public-layout.tsx'));
        $login = (string) file_get_contents(resource_path('js/pages/auth/login.tsx'));
        $welcome = (string) file_get_contents(resource_path('js/pages/welcome.tsx'));

        foreach ([$layout, $login, $welcome] as $source) {
            $this->assertStringContainsString('auth.registrationUrl', $source);
            $this->assertStringNotContainsString('@/routes/register', $source);
            $this->assertStringNotContainsString('register()', $source);
        }
    }

    public function test_inertia_layout_resolver_contract_stays_documented(): void
    {
        $app = (string) file_get_contents(resource_path('js/app.tsx'));
        $agents = (string) file_get_contents(base_path('AGENTS.md'));
        $claude = (string) file_get_contents(base_path('CLAUDE.md'));

        $surfaces = (string) file_get_contents(resource_path('js/lib/page-surfaces.ts'));

        $this->assertStringContainsString('classifyPageSurface(name)', $app);
        $this->assertStringContainsString("case 'authentication':", $app);
        $this->assertStringContainsString("case 'settings':", $app);
        $this->assertStringContainsString('return [AppLayout, SettingsLayout];', $app);
        $this->assertStringContainsString('return AppLayout;', $app);
        $this->assertStringContainsString("return 'public';", $surfaces);
        $this->assertStringContainsString("'administration'", $surfaces);
        $this->assertStringContainsString(
            'Page.layout = (page: ReactElement) => <>{page}</>;',
            $agents,
        );
        $this->assertStringContainsString('Page.layout = (page) => page;', $agents);
        $this->assertSame($agents, $claude);
    }

    public function test_vite_dev_server_proxy_contract_is_env_driven(): void
    {
        $env = (string) file_get_contents(base_path('.env.example'));
        $inertia = (string) file_get_contents(base_path('config/inertia.php'));
        $vite = (string) file_get_contents(base_path('vite.config.ts'));
        $nginx = (string) file_get_contents(base_path('docs/nginx-dev-vhost.example.conf'));

        $this->assertStringContainsString('INERTIA_SSR_ENABLED=true', $env);
        $this->assertStringContainsString('INERTIA_SSR_URL=http://127.0.0.1:13714', $env);
        $this->assertStringContainsString('INERTIA_SSR_HOT_URL=', $env);
        $this->assertStringContainsString("env('INERTIA_SSR_ENABLED', true)", $inertia);
        $this->assertStringContainsString("env('INERTIA_SSR_URL', 'http://127.0.0.1:13714')", $inertia);
        $this->assertStringContainsString("env('INERTIA_SSR_HOT_URL')", $inertia);
        $this->assertStringContainsString('function resolveInertiaSsrListener(', $vite);
        $this->assertStringContainsString('inertia({ ssr: inertiaSsrListener })', $vite);
        $this->assertStringContainsString('const inertiaSsrListener = resolveConfiguredInertiaSsrListener();', $vite);
        $this->assertStringContainsString('Rebuild (`npm run build`) after changing either.', $env);
        $this->assertStringContainsString('VITE_DEV_SERVER_PORT=5186', $env);
        $this->assertStringContainsString('VITE_DEV_SERVER_ORIGIN="${APP_URL}"', $env);
        $this->assertStringContainsString('function resolveDevServerOrigin(', $vite);
        $this->assertStringContainsString(
            "const devServer = command === 'serve' ? resolveDevServer(env) : undefined;",
            $vite,
        );
        $this->assertStringContainsString(
            'VITE_DEV_SERVER_ORIGIN requires VITE_DEV_SERVER_PORT',
            $vite,
        );
        $this->assertStringContainsString('origin: devServerOrigin.origin', $vite);
        $this->assertStringContainsString(
            'clientPort: resolveDevServerOriginPort(devServerOrigin)',
            $vite,
        );
        $this->assertStringContainsString("path: '/vite-hmr'", $vite);
        $this->assertStringContainsString('location = /vite-hmr', $nginx);
    }

    public function test_frontend_navigation_uses_the_authenticated_home_contract(): void
    {
        $navigation = (string) file_get_contents(resource_path('js/config/navigation.ts'));
        $settingsLayout = (string) file_get_contents(resource_path('js/layouts/settings/layout.tsx'));
        $header = (string) file_get_contents(resource_path('js/components/app-header.tsx'));

        $homePosition = strpos($navigation, "title: 'Home'");
        $dashboardPosition = strpos($navigation, "title: 'Dashboard'");

        $this->assertIsInt($homePosition);
        $this->assertIsInt($dashboardPosition);
        $this->assertLessThan($dashboardPosition, $homePosition);
        $this->assertStringContainsString("import { dashboard, home } from '@/routes';", $navigation);
        $this->assertStringNotContainsString('@/routes/evolayer/base', $navigation);
        $this->assertStringContainsString('href: home()', $navigation);
        $this->assertStringContainsString('export const settingsSectionNavItems', $navigation);
        $this->assertStringContainsString('settingsSectionNavItems', $settingsLayout);
        $this->assertStringContainsString('configuredMainNavItems', $header);
    }

    public function test_primary_navigation_closes_the_mobile_sidebar(): void
    {
        $navigation = (string) file_get_contents(resource_path('js/components/nav-main.tsx'));

        $this->assertStringContainsString(
            'const { isMobile, setOpenMobile } = useSidebar();',
            $navigation,
        );
        $this->assertStringContainsString('if (isMobile) {', $navigation);
        $this->assertStringContainsString('setOpenMobile(false);', $navigation);
    }
}
