<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ShellContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_and_authenticated_home_routes_are_distinct(): void
    {
        $this->assertSame('/', route('home', absolute: false));
        $this->assertSame('/home', route('evolayer.base.home', absolute: false));
        $this->assertSame('/home', config('fortify.home'));
    }

    public function test_authenticated_home_requires_authentication(): void
    {
        $this->get(route('evolayer.base.home'))
            ->assertRedirect(route('login'));
    }

    public function test_verified_user_can_visit_authenticated_home(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('evolayer.base.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('evolayer/home'),
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
            'title: (title) => (title ? `${title} | ${appName}` : appName),',
            $app,
        );
        $this->assertStringNotContainsString('`${title} - ${appName}`', $app);
    }

    public function test_inertia_layout_resolver_contract_stays_documented(): void
    {
        $app = (string) file_get_contents(resource_path('js/app.tsx'));
        $agents = (string) file_get_contents(base_path('AGENTS.md'));
        $claude = (string) file_get_contents(base_path('CLAUDE.md'));

        $this->assertStringContainsString("case name === 'welcome':", $app);
        $this->assertStringContainsString("case name.startsWith('auth/'):", $app);
        $this->assertStringContainsString("case name.startsWith('settings/'):", $app);
        $this->assertStringContainsString('return [AppLayout, SettingsLayout];', $app);
        $this->assertStringContainsString('return AppLayout;', $app);
        $this->assertStringContainsString(
            'Page.layout = (page: ReactElement) => <>{page}</>;',
            $agents,
        );
        $this->assertStringContainsString('Page.layout = (page) => page;', $agents);
        $this->assertSame($agents, $claude);
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
        $this->assertStringContainsString('import { home as evolayerHome }', $navigation);
        $this->assertStringContainsString('href: evolayerHome()', $navigation);
        $this->assertStringContainsString('export const settingsSectionNavItems', $navigation);
        $this->assertStringContainsString('settingsSectionNavItems', $settingsLayout);
        $this->assertStringContainsString('configuredMainNavItems', $header);
    }
}
