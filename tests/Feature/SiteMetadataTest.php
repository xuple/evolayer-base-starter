<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Support\SiteMetadata;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class SiteMetadataTest extends TestCase
{
    public function test_env_example_documents_the_site_and_social_contract(): void
    {
        $env = (string) file_get_contents(base_path('.env.example'));
        $requiredKeys = [
            'SITE_NAME=',
            'SITE_URL=',
            'SITE_TITLE_TEMPLATE="%s | EvoLayer Base"',
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
        Config::set('site.identity.name', '');
        Config::set('site.identity.title_template', '');
        Config::set('site.canonical.base_url', '');

        $site = SiteMetadata::inertiaDefaults();

        $this->assertSame('Fallback App', $site['name']);
        $this->assertSame('https://app.example', $site['url']);
        $this->assertSame('%s | Fallback App', $site['titleTemplate']);
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
        $this->useStarterSiteDefaults();
        Config::set('site.canonical.base_url', 'https://starter.example');

        $response = $this->get(route('home'));

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

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(
                'property="og:image" content="https://starter.example/social/og-default.png?v=preview-2"',
                false,
            );
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

    private function useStarterSiteDefaults(): void
    {
        Config::set('app.name', 'EvoLayer Base');
        Config::set('site.identity.name', 'EvoLayer Base');
        Config::set('site.identity.title_template', '%s | EvoLayer Base');
    }
}
