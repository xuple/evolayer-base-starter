<?php

namespace App\Support;

final class SiteMetadata
{
    /**
     * @return array{
     *     name: string,
     *     titleTemplate: string,
     *     description: string,
     *     url: string,
     *     assetVersion: string|null,
     *     ogLocale: string,
     *     themeColor: string|null,
     *     robots: array{default: string},
     *     social: array{
     *         image: array{url: string|null, alt: string|null, width: int|null, height: int|null, type: string|null, version: string|null},
     *         twitter: array{site: string|null, creator: string|null}
     *     },
     *     structuredData: array{
     *         enabled: bool,
     *         logo: string|null,
     *         businessType: list<string>,
     *         telephone: string|null,
     *         email: string|null,
     *         areaServed: string|null,
     *         priceRange: string|null,
     *         sameAs: list<string>
     *     }
     * }
     */
    public static function inertiaDefaults(): array
    {
        $name = self::filledString(config('site.identity.name'))
            ?? self::filledString(config('evolayer.base.brand.name'))
            ?? self::filledString(config('app.name'))
            ?? 'EvoLayer Base';
        $baseUrl = self::normaliseBaseUrl(
            self::filledString(config('site.canonical.base_url'))
                ?? self::filledString(config('app.url'))
                ?? 'http://localhost',
        );

        return [
            'name' => $name,
            'titleTemplate' => self::filledString(config('site.identity.title_template')) ?? "%s | {$name}",
            'description' => self::filledString(config('site.identity.description')) ?? '',
            'url' => $baseUrl,
            'assetVersion' => self::filledString(config('site.assets.version')),
            'ogLocale' => self::filledString(config('site.identity.og_locale')) ?? 'en_GB',
            'themeColor' => self::filledString(config('site.identity.theme_color')),
            'robots' => [
                'default' => self::filledString(config('site.robots.default')) ?? 'index,follow',
            ],
            'social' => [
                'image' => [
                    'url' => self::filledString(config('site.social.image')),
                    'alt' => self::filledString(config('site.social.image_alt')),
                    'width' => self::positiveInt(config('site.social.image_width')),
                    'height' => self::positiveInt(config('site.social.image_height')),
                    'type' => self::filledString(config('site.social.image_type')),
                    'version' => self::filledString(config('site.social.image_version')),
                ],
                'twitter' => [
                    'site' => self::filledString(config('site.social.twitter_site')),
                    'creator' => self::filledString(config('site.social.twitter_creator')),
                ],
            ],
            'structuredData' => [
                'enabled' => (bool) config('site.structured_data.enabled', true),
                'logo' => self::filledString(config('site.structured_data.logo')),
                'businessType' => self::csvList(config('site.structured_data.business_type')),
                'telephone' => self::filledString(config('site.structured_data.telephone')),
                'email' => self::filledString(config('site.structured_data.email')),
                'areaServed' => self::filledString(config('site.structured_data.area_served')),
                'priceRange' => self::filledString(config('site.structured_data.price_range')),
                'sameAs' => self::csvList(config('site.structured_data.same_as')),
            ],
        ];
    }

    public static function formatTitle(?string $title = null): string
    {
        $site = self::inertiaDefaults();
        $cleanTitle = self::filledString($title);

        if ($cleanTitle === null || $cleanTitle === $site['name']) {
            return $site['name'];
        }

        return str_contains($site['titleTemplate'], '%s')
            ? str_replace('%s', $cleanTitle, $site['titleTemplate'])
            : "{$cleanTitle} | {$site['name']}";
    }

    public static function absoluteUrl(?string $value, ?string $baseUrl = null, ?string $version = null): ?string
    {
        $url = self::filledString($value);

        if ($url === null) {
            return null;
        }

        if (! preg_match('#^https?://#i', $url)) {
            $base = self::normaliseBaseUrl($baseUrl ?? self::inertiaDefaults()['url']);
            $url = $base.'/'.ltrim($url, '/');
        }

        return self::appendVersion($url, $version);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function defaultJsonLd(): ?array
    {
        $site = self::inertiaDefaults();

        if (! $site['structuredData']['enabled']) {
            return null;
        }

        $structured = $site['structuredData'];
        $graph = [
            [
                '@type' => 'WebSite',
                'name' => $site['name'],
                'url' => $site['url'],
                'description' => $site['description'],
            ],
        ];

        $logo = self::absoluteUrl($structured['logo'], $site['url']);
        $image = self::absoluteUrl(
            $site['social']['image']['url'],
            $site['url'],
            $site['social']['image']['version'],
        );
        $sameAs = $structured['sameAs'];

        if ($structured['businessType'] !== []) {
            // A configured business node folds the logo in and supersedes the
            // plain Organization-from-logo node to avoid duplicate entities.
            $graph[] = array_filter([
                '@type' => count($structured['businessType']) === 1
                    ? $structured['businessType'][0]
                    : $structured['businessType'],
                'name' => $site['name'],
                'url' => $site['url'],
                'logo' => $logo,
                'image' => $image,
                'telephone' => $structured['telephone'],
                'email' => $structured['email'],
                'areaServed' => $structured['areaServed'],
                'priceRange' => $structured['priceRange'],
                'sameAs' => $sameAs !== [] ? $sameAs : null,
            ], static fn (mixed $field): bool => $field !== null && $field !== []);
        } elseif ($logo !== null) {
            $organization = [
                '@type' => 'Organization',
                'name' => $site['name'],
                'url' => $site['url'],
                'logo' => $logo,
            ];

            if ($sameAs !== []) {
                $organization['sameAs'] = $sameAs;
            }

            $graph[] = $organization;
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => $graph,
        ];
    }

    private static function normaliseBaseUrl(string $url): string
    {
        return rtrim($url, '/');
    }

    private static function appendVersion(string $url, ?string $version): string
    {
        $cleanVersion = self::filledString($version);

        if ($cleanVersion === null) {
            return $url;
        }

        $fragment = '';
        $urlWithoutFragment = $url;

        if (str_contains($url, '#')) {
            [$urlWithoutFragment, $fragment] = explode('#', $url, 2);
            $fragment = '#'.$fragment;
        }

        parse_str((string) parse_url($urlWithoutFragment, PHP_URL_QUERY), $query);

        if (array_key_exists('v', $query)) {
            return $url;
        }

        $separator = str_contains($urlWithoutFragment, '?') ? '&' : '?';

        return $urlWithoutFragment.$separator.'v='.rawurlencode($cleanVersion).$fragment;
    }

    /**
     * Split a comma-separated env value into a trimmed, non-empty list.
     *
     * @return list<string>
     */
    private static function csvList(mixed $value): array
    {
        $string = self::filledString($value);

        if ($string === null) {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn (string $part): string => trim($part), explode(',', $string)),
            static fn (string $part): bool => $part !== '',
        ));
    }

    private static function filledString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    private static function positiveInt(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $integer = (int) $value;

        return $integer > 0 ? $integer : null;
    }
}
