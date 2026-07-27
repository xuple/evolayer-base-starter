<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
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
        $vite = (string) file_get_contents(base_path('vite.config.ts'));
        $nginx = (string) file_get_contents(base_path('docs/nginx-dev-vhost.example.conf'));

        $this->assertStringContainsString('VITE_DEV_SERVER_PORT=', $env);
        $this->assertStringContainsString('VITE_DEV_SERVER_ORIGIN=', $env);
        $this->assertStringContainsString('function resolveDevServerOrigin(', $vite);
        $this->assertStringContainsString(
            "const devServer = command === 'serve' ? resolveDevServer(mode) : undefined;",
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

    public function test_command_palette_restores_focus_to_its_opener(): void
    {
        $provider = (string) file_get_contents(
            resource_path('js/providers/command-palette-provider.tsx'),
        );
        $palette = (string) file_get_contents(
            resource_path('js/components/command-palette-dialog.tsx'),
        );

        $this->assertStringContainsString(
            'previouslyFocusedElement.current =',
            $provider,
        );
        $this->assertStringContainsString(
            'previouslyFocusedElement.current?.isConnected',
            $provider,
        );
        $this->assertStringContainsString('onCloseAutoFocus={(event) => {', $palette);
        $this->assertStringContainsString('restoreFocus();', $palette);
    }
}
