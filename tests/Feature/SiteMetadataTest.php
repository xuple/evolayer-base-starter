<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Support\SiteMetadata;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Inertia\Inertia;
use Tests\TestCase;

class SiteMetadataTest extends TestCase
{
    public function test_env_example_documents_the_site_and_social_contract(): void
    {
        $env = (string) file_get_contents(base_path('.env.example'));
        $requiredKeys = [
            'SITE_NAME=',
            'SITE_URL=',
            'SITE_TITLE_TEMPLATE=',
            'SITE_DESCRIPTION=',
            'SITE_OG_LOCALE=en_GB',
            'SITE_THEME_COLOR="#064e3b"',
            'SITE_ROBOTS_DEFAULT="index,follow"',
            'SOCIAL_IMAGE="/social/og-default.png"',
            'SOCIAL_IMAGE_ALT=',
            'SOCIAL_IMAGE_WIDTH=1200',
            'SOCIAL_IMAGE_HEIGHT=630',
            'SOCIAL_IMAGE_TYPE="image/png"',
            'SOCIAL_IMAGE_VERSION=',
            'SOCIAL_TWITTER_SITE=',
            'SOCIAL_TWITTER_CREATOR=',
            'SITE_JSONLD_ENABLED=true',
            'SITE_LOGO=',
            'SITE_JSONLD_TYPE=',
            'SITE_JSONLD_TELEPHONE=',
            'SITE_JSONLD_EMAIL=',
            'SITE_JSONLD_AREA_SERVED=',
            'SITE_JSONLD_PRICE_RANGE=',
            'SITE_JSONLD_SAME_AS=',
            'SITE_ASSET_VERSION=',
        ];

        foreach ($requiredKeys as $key) {
            $this->assertStringContainsString($key, $env);
        }

        $this->assertStringNotContainsString('SOCIAL_TWITTER_CARD', $env);
        $this->assertStringNotContainsString('SITE_ROBOTS_PUBLIC', $env);
        $this->assertStringNotContainsString('SITE_ROBOTS_PRIVATE', $env);
        $this->assertStringNotContainsString('VITE_SITE_', $env);
        $this->assertStringNotContainsString('VITE_SOCIAL_', $env);
    }

    public function test_site_defaults_fall_back_to_app_name_and_app_url_when_site_values_are_blank(): void
    {
        Config::set('app.name', 'Fallback App');
        Config::set('app.url', 'https://app.example');
        Config::set('evolayer.base.brand.name', '');
        Config::set('site.identity.name', '');
        Config::set('site.identity.title_template', '');
        Config::set('site.canonical.base_url', '');

        $site = SiteMetadata::inertiaDefaults();

        $this->assertSame('Fallback App', $site['name']);
        $this->assertSame('https://app.example', $site['url']);
        $this->assertSame('%s | Fallback App', $site['titleTemplate']);
    }

    public function test_blank_site_identity_falls_back_to_the_public_brand_before_app_name(): void
    {
        Config::set('app.name', 'Fallback App');
        Config::set('evolayer.base.brand.name', 'Example Brand');
        Config::set('site.identity.name', '');
        Config::set('site.identity.title_template', '');

        $site = SiteMetadata::inertiaDefaults();

        $this->assertSame('Example Brand', $site['name']);
        $this->assertSame('%s | Example Brand', $site['titleTemplate']);
    }

    public function test_site_url_overrides_the_canonical_base(): void
    {
        Config::set('site.canonical.base_url', 'https://public.example/');

        $this->assertSame('https://public.example', SiteMetadata::inertiaDefaults()['url']);
    }

    public function test_site_title_formatting_uses_template_without_duplicating_the_site_name(): void
    {
        $this->useStarterSiteDefaults();

        $this->assertSame('Welcome | EvoLayer Base', SiteMetadata::formatTitle('Welcome'));
        $this->assertSame('EvoLayer Base', SiteMetadata::formatTitle('EvoLayer Base'));
        $this->assertSame('EvoLayer Base', SiteMetadata::formatTitle());
    }

    public function test_social_image_paths_resolve_against_the_canonical_base(): void
    {
        Config::set('site.canonical.base_url', 'https://public.example');

        $this->assertSame(
            'https://public.example/social/og-default.png',
            SiteMetadata::absoluteUrl('/social/og-default.png'),
        );
    }

    public function test_social_image_version_is_appended_only_when_configured(): void
    {
        Config::set('site.canonical.base_url', 'https://public.example');

        $this->assertSame(
            'https://public.example/social/og-default.png',
            SiteMetadata::absoluteUrl('/social/og-default.png'),
        );
        $this->assertSame(
            'https://public.example/social/og-default.png?v=card-1',
            SiteMetadata::absoluteUrl('/social/og-default.png', null, 'card-1'),
        );
        $this->assertSame(
            'https://public.example/social/og-default.png?existing=1&v=card-1',
            SiteMetadata::absoluteUrl('/social/og-default.png?existing=1', null, 'card-1'),
        );
        $this->assertSame(
            'https://public.example/social/og-default.png?v=already',
            SiteMetadata::absoluteUrl('/social/og-default.png?v=already', null, 'card-1'),
        );
    }

    public function test_asset_version_is_exposed_on_site_defaults_when_configured(): void
    {
        $this->assertNull(SiteMetadata::inertiaDefaults()['assetVersion']);

        Config::set('site.assets.version', 'assets-7');

        $this->assertSame('assets-7', SiteMetadata::inertiaDefaults()['assetVersion']);
    }

    public function test_site_defaults_are_shared_once_under_a_namespaced_public_prop(): void
    {
        $this->useStarterSiteDefaults();

        $sharedOnce = $this->app->make(HandleInertiaRequests::class)
            ->shareOnce(Request::create('/'));

        $this->assertArrayHasKey('site', $sharedOnce);

        $site = $sharedOnce['site']();

        $this->assertSame('EvoLayer Base', $site['name']);
        $this->assertSame('index,follow', $site['robots']['default']);
        $this->assertSame('/social/og-default.png', $site['social']['image']['url']);
        $this->assertArrayHasKey('twitter', $site['social']);
        $this->assertArrayNotHasKey('auth', $site);
    }

    public function test_public_landing_initial_html_contains_default_preview_metadata(): void
    {
        // Simulate a generated app whose .env has customised these fields;
        // useStarterSiteDefaults() must neutralise them so the starter-default
        // assertions below stay valid. This doubles as a hermeticity guard.
        Config::set('site.identity.theme_color', '#ff0000');
        Config::set('site.identity.og_locale', 'fr_FR');
        Config::set('site.robots.default', 'noindex,nofollow');
        Config::set('site.social.image_alt', 'Custom app preview');
        Config::set('site.social.image_version', 'app-v9');
        Config::set('site.social.twitter_site', '@customapp');
        Config::set('site.structured_data.enabled', false);

        $this->useStarterSiteDefaults();
        Config::set('site.canonical.base_url', 'https://starter.example');

        // Force the deterministic Blade fallback path (resources/views/app.blade.php)
        // instead of the SSR-rendered path. Without this, the result depends on
        // whether an SSR server happens to be reachable at config('inertia.ssr.url'),
        // which is never true in CI and is not guaranteed in any other environment.
        Inertia::disableSsr();

        $response = $this->get(route('welcome'));

        $response
            ->assertOk()
            ->assertSee('head-key="description"', false)
            ->assertSee('rel="canonical"', false)
            ->assertSee('href="https://starter.example/"', false)
            ->assertSee('name="robots" content="index,follow"', false)
            ->assertSee('name="theme-color" content="#064e3b"', false)
            ->assertSee('property="og:title"', false)
            ->assertSee('property="og:type" content="website"', false)
            ->assertSee('property="og:description"', false)
            ->assertSee('property="og:url" content="https://starter.example/"', false)
            ->assertSee('property="og:site_name" content="EvoLayer Base"', false)
            ->assertSee('property="og:locale" content="en_GB"', false)
            ->assertSee('property="og:image" content="https://starter.example/social/og-default.png"', false)
            ->assertSee('property="og:image:secure_url" content="https://starter.example/social/og-default.png"', false)
            ->assertSee('property="og:image:type" content="image/png"', false)
            ->assertSee('property="og:image:width" content="1200"', false)
            ->assertSee('property="og:image:height" content="630"', false)
            ->assertSee('property="og:image:alt" content="EvoLayer Base preview image"', false)
            ->assertSee('name="twitter:card" content="summary_large_image"', false)
            ->assertSee('name="twitter:title"', false)
            ->assertSee('name="twitter:description"', false)
            ->assertSee('name="twitter:image" content="https://starter.example/social/og-default.png"', false)
            ->assertSee('name="twitter:image:alt" content="EvoLayer Base preview image"', false)
            ->assertSee('type="application/ld+json"', false)
            ->assertDontSee('name="twitter:site"', false)
            ->assertDontSee('name="twitter:creator"', false);
    }

    public function test_social_image_version_is_reflected_in_fallback_metadata(): void
    {
        $this->useStarterSiteDefaults();
        Config::set('site.canonical.base_url', 'https://starter.example');
        Config::set('site.social.image_version', 'preview-2');

        $this->get(route('welcome'))
            ->assertOk()
            ->assertSee(
                'property="og:image" content="https://starter.example/social/og-default.png?v=preview-2"',
                false,
            );
    }

    public function test_configured_business_node_renders_server_side_in_initial_html(): void
    {
        $this->useStarterSiteDefaults();
        Config::set('site.canonical.base_url', 'https://starter.example');
        Config::set('site.structured_data.business_type', 'LocalBusiness,HomeAndConstructionBusiness');
        Config::set('site.structured_data.telephone', '+10000000000');
        Config::set('site.structured_data.email', 'hello@example.com');
        Config::set('site.structured_data.area_served', 'Example Region');
        Config::set('site.structured_data.same_as', 'https://example.com/a, https://example.com/b');

        $this->get(route('welcome'))
            ->assertOk()
            ->assertSee('"@type":["LocalBusiness","HomeAndConstructionBusiness"]', false)
            ->assertSee('"telephone":"+10000000000"', false)
            ->assertSee('"email":"hello@example.com"', false)
            ->assertSee('"areaServed":"Example Region"', false)
            ->assertSee('"sameAs":["https://example.com/a","https://example.com/b"]', false);
    }

    public function test_private_layouts_use_a_noindex_robots_override_without_public_preview_tags(): void
    {
        $appLayout = (string) file_get_contents(resource_path('js/layouts/app-layout.tsx'));
        $authLayout = (string) file_get_contents(resource_path('js/layouts/auth-layout.tsx'));

        foreach ([$appLayout, $authLayout] as $layout) {
            $this->assertStringContainsString('<SiteHead preview={false} robots="noindex,nofollow" />', $layout);
        }
    }

    public function test_non_public_initial_html_uses_private_robots_without_rich_preview_fallbacks(): void
    {
        $this->useStarterSiteDefaults();
        Config::set('site.canonical.base_url', 'https://starter.example');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('name="robots" content="noindex,nofollow"', false)
            ->assertDontSee('property="og:image"', false)
            ->assertDontSee('name="twitter:image"', false)
            ->assertDontSee('type="application/ld+json"', false)
            ->assertDontSee('rel="canonical"', false);
    }

    public function test_site_head_supports_page_level_metadata_overrides(): void
    {
        $siteHead = (string) file_get_contents(resource_path('js/components/site-head.tsx'));

        foreach (['canonical?:', 'robots?:', 'ogType?:', 'image?:', 'jsonLd?:'] as $contract) {
            $this->assertStringContainsString($contract, $siteHead);
        }

        $this->assertStringContainsString('head-key="canonical"', $siteHead);
        $this->assertStringContainsString('head-key="robots"', $siteHead);
        $this->assertStringContainsString('safeJsonLd(jsonLdPayload)', $siteHead);
    }

    public function test_default_social_preview_image_asset_exists_with_expected_dimensions(): void
    {
        $path = public_path('social/og-default.png');

        $this->assertFileExists($path);

        [$width, $height, $type] = getimagesize($path);

        $this->assertSame(1200, $width);
        $this->assertSame(630, $height);
        $this->assertSame(IMAGETYPE_PNG, $type);
    }

    /**
     * Pin every host-overridable field that the starter-default preview
     * assertions check, so this fixture stays hermetic even inside a generated
     * app whose .env customises the theme colour, social image, robots, locale,
     * Twitter handles or structured-data toggle. Values mirror config/site.php
     * and the .env.example starter defaults; without this pinning a downstream
     * app inherits false failures the moment it brands its own social preview.
     */
    private function useStarterSiteDefaults(): void
    {
        Config::set('app.name', 'EvoLayer Base');
        Config::set('evolayer.base.brand.name', 'EvoLayer Base');
        Config::set('site.identity.name', 'EvoLayer Base');
        Config::set('site.identity.title_template', '%s | EvoLayer Base');
        Config::set('site.identity.og_locale', 'en_GB');
        Config::set('site.identity.theme_color', '#064e3b');
        Config::set('site.robots.default', 'index,follow');
        Config::set('site.social.image', '/social/og-default.png');
        Config::set('site.social.image_alt', 'EvoLayer Base preview image');
        Config::set('site.social.image_width', 1200);
        Config::set('site.social.image_height', 630);
        Config::set('site.social.image_type', 'image/png');
        Config::set('site.social.image_version', null);
        Config::set('site.social.twitter_site', null);
        Config::set('site.social.twitter_creator', null);
        Config::set('site.structured_data.enabled', true);
        Config::set('site.structured_data.business_type', null);
        Config::set('site.structured_data.telephone', null);
        Config::set('site.structured_data.email', null);
        Config::set('site.structured_data.area_served', null);
        Config::set('site.structured_data.price_range', null);
        Config::set('site.structured_data.same_as', null);
    }
}
