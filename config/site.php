<?php

$value = static fn (string $key, mixed $default = null): mixed => filled(env($key))
    ? env($key)
    : $default;

$boolean = static function (string $key, bool $default) use ($value): bool {
    $raw = $value($key, $default);

    if (is_bool($raw)) {
        return $raw;
    }

    $parsed = filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

    return $parsed ?? $default;
};

$integer = static function (string $key, int $default) use ($value): int {
    $raw = $value($key, $default);

    return is_numeric($raw) ? (int) $raw : $default;
};

$appName = $value('APP_NAME', 'EvoLayer Base');
$brandName = $value('EVOLAYER_BASE_BRAND_NAME', $appName);
$siteName = $value('SITE_NAME', $brandName);
$appUrl = rtrim((string) $value('APP_URL', 'http://localhost'), '/');

return [
    'identity' => [
        'name' => $siteName,
        'title_template' => $value('SITE_TITLE_TEMPLATE', "%s | {$siteName}"),
        'description' => $value(
            'SITE_DESCRIPTION',
            'AI, ontology, and block-first Laravel starter for modern product teams.',
        ),
        'og_locale' => $value('SITE_OG_LOCALE', 'en_GB'),
        'theme_color' => $value('SITE_THEME_COLOR', '#064e3b'),
    ],

    'canonical' => [
        'base_url' => rtrim((string) $value('SITE_URL', $appUrl), '/'),
    ],

    'robots' => [
        'default' => $value('SITE_ROBOTS_DEFAULT', 'index,follow'),
    ],

    'social' => [
        'image' => $value('SOCIAL_IMAGE', '/social/og-default.png'),
        'image_alt' => $value('SOCIAL_IMAGE_ALT', 'EvoLayer Base preview image'),
        'image_width' => $integer('SOCIAL_IMAGE_WIDTH', 1200),
        'image_height' => $integer('SOCIAL_IMAGE_HEIGHT', 630),
        'image_type' => $value('SOCIAL_IMAGE_TYPE', 'image/png'),
        'image_version' => $value('SOCIAL_IMAGE_VERSION'),
        'twitter_site' => $value('SOCIAL_TWITTER_SITE'),
        'twitter_creator' => $value('SOCIAL_TWITTER_CREATOR'),
    ],

    'structured_data' => [
        'enabled' => $boolean('SITE_JSONLD_ENABLED', true),
        'logo' => $value('SITE_LOGO'),
    ],
];
