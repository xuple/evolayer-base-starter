<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @php
            $site = \App\Support\SiteMetadata::inertiaDefaults();
            $siteTitle = \App\Support\SiteMetadata::formatTitle();
            $siteDescription = $site['description'];
            $publicPreviewComponents = [
                'evolayer/base',
                'evolayer/contact',
                'evolayer/contact-thank-you',
                'welcome',
            ];
            $isPublicPreviewComponent = in_array((string) ($page['component'] ?? ''), $publicPreviewComponents, true);
            $robots = $isPublicPreviewComponent ? $site['robots']['default'] : 'noindex,nofollow';
            $canonicalUrl = $isPublicPreviewComponent
                ? \App\Support\SiteMetadata::absoluteUrl(request()->getPathInfo(), $site['url'])
                : null;
            $socialImage = $isPublicPreviewComponent
                ? \App\Support\SiteMetadata::absoluteUrl(
                    $site['social']['image']['url'],
                    $site['url'],
                    $site['social']['image']['version'],
                )
                : null;
            $jsonLd = $isPublicPreviewComponent ? \App\Support\SiteMetadata::defaultJsonLd() : null;
            $jsonLdJson = $jsonLd === null
                ? null
                : json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
        @endphp

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        <x-inertia::head>
            <title data-inertia>{{ $siteTitle }}</title>
            <meta data-inertia head-key="robots" name="robots" content="{{ $robots }}">
            @if ($isPublicPreviewComponent && $siteDescription !== '')
                <meta data-inertia head-key="description" name="description" content="{{ $siteDescription }}">
            @endif
            @if ($canonicalUrl !== null)
                <link data-inertia head-key="canonical" rel="canonical" href="{{ $canonicalUrl }}">
                <meta data-inertia head-key="og:url" property="og:url" content="{{ $canonicalUrl }}">
            @endif
            @if ($isPublicPreviewComponent && $site['themeColor'] !== null)
                <meta data-inertia head-key="theme-color" name="theme-color" content="{{ $site['themeColor'] }}">
            @endif
            @if ($isPublicPreviewComponent)
                <meta data-inertia head-key="og:title" property="og:title" content="{{ $siteTitle }}">
                <meta data-inertia head-key="og:type" property="og:type" content="website">
                @if ($siteDescription !== '')
                    <meta data-inertia head-key="og:description" property="og:description" content="{{ $siteDescription }}">
                @endif
                <meta data-inertia head-key="og:site_name" property="og:site_name" content="{{ $site['name'] }}">
                <meta data-inertia head-key="og:locale" property="og:locale" content="{{ $site['ogLocale'] }}">
                @if ($socialImage !== null)
                    <meta data-inertia head-key="og:image" property="og:image" content="{{ $socialImage }}">
                    <meta data-inertia head-key="og:image:secure_url" property="og:image:secure_url" content="{{ $socialImage }}">
                    @if ($site['social']['image']['type'] !== null)
                        <meta data-inertia head-key="og:image:type" property="og:image:type" content="{{ $site['social']['image']['type'] }}">
                    @endif
                    @if ($site['social']['image']['width'] !== null)
                        <meta data-inertia head-key="og:image:width" property="og:image:width" content="{{ $site['social']['image']['width'] }}">
                    @endif
                    @if ($site['social']['image']['height'] !== null)
                        <meta data-inertia head-key="og:image:height" property="og:image:height" content="{{ $site['social']['image']['height'] }}">
                    @endif
                    @if ($site['social']['image']['alt'] !== null)
                        <meta data-inertia head-key="og:image:alt" property="og:image:alt" content="{{ $site['social']['image']['alt'] }}">
                    @endif
                @endif
                <meta data-inertia head-key="twitter:card" name="twitter:card" content="{{ $socialImage === null ? 'summary' : 'summary_large_image' }}">
                <meta data-inertia head-key="twitter:title" name="twitter:title" content="{{ $siteTitle }}">
                @if ($siteDescription !== '')
                    <meta data-inertia head-key="twitter:description" name="twitter:description" content="{{ $siteDescription }}">
                @endif
                @if ($socialImage !== null)
                    <meta data-inertia head-key="twitter:image" name="twitter:image" content="{{ $socialImage }}">
                    @if ($site['social']['image']['alt'] !== null)
                        <meta data-inertia head-key="twitter:image:alt" name="twitter:image:alt" content="{{ $site['social']['image']['alt'] }}">
                    @endif
                @endif
                @if ($site['social']['twitter']['site'] !== null)
                    <meta data-inertia head-key="twitter:site" name="twitter:site" content="{{ $site['social']['twitter']['site'] }}">
                @endif
                @if ($site['social']['twitter']['creator'] !== null)
                    <meta data-inertia head-key="twitter:creator" name="twitter:creator" content="{{ $site['social']['twitter']['creator'] }}">
                @endif
            @endif
            @if ($jsonLdJson !== null)
                <script data-inertia head-key="json-ld" type="application/ld+json">{!! $jsonLdJson !!}</script>
            @endif
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
