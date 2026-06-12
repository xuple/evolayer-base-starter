import { Head, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import {
    applyTitleTemplate,
    filled,
    resolveAbsoluteUrl,
    resolveVersionedUrl,
    safeJsonLd,
} from '@/lib/site-meta';
import type {
    JsonLdPayload,
    SiteSharedProps,
    SiteSocialImage,
} from '@/types/site';

type ImageOverride = Partial<SiteSocialImage> | string | null;

type Props = {
    title?: string | null;
    description?: string | null;
    canonical?: string | null;
    robots?: string | null;
    ogType?: string;
    image?: ImageOverride;
    imageAlt?: string | null;
    imageWidth?: number | null;
    imageHeight?: number | null;
    imageType?: string | null;
    jsonLd?: JsonLdPayload | false | null;
    preview?: boolean;
    children?: ReactNode;
};

export function SiteHead({
    title,
    description,
    canonical,
    robots,
    ogType = 'website',
    image,
    imageAlt,
    imageWidth,
    imageHeight,
    imageType,
    jsonLd,
    preview = true,
    children,
}: Props) {
    const page = usePage();
    const { site } = page.props;
    const resolvedRobots = filled(robots) ?? site.robots.default;

    if (!preview) {
        return (
            <Head>
                {resolvedRobots && (
                    <meta
                        head-key="robots"
                        name="robots"
                        content={resolvedRobots}
                    />
                )}
                {children}
            </Head>
        );
    }

    const imageDefaults = site.social.image;
    const imageOverride =
        typeof image === 'object' && image !== null ? image : undefined;
    const imageSource =
        typeof image === 'string'
            ? image
            : image === null
              ? null
              : (imageOverride?.url ?? imageDefaults.url);
    const imageVersion = imageOverride?.version ?? imageDefaults.version;
    const resolvedImage = resolveVersionedUrl(
        imageSource,
        site.url,
        imageVersion,
    );
    const titleOverride = filled(title);
    const resolvedTitle = titleOverride
        ? applyTitleTemplate(titleOverride, site.titleTemplate, site.name)
        : site.name;
    const resolvedDescription = filled(description) ?? site.description;
    const resolvedCanonical = resolveAbsoluteUrl(
        canonical ?? page.url,
        site.url,
    );
    const resolvedImageAlt =
        filled(imageAlt) ?? imageOverride?.alt ?? imageDefaults.alt;
    const resolvedImageWidth =
        imageWidth ?? imageOverride?.width ?? imageDefaults.width;
    const resolvedImageHeight =
        imageHeight ?? imageOverride?.height ?? imageDefaults.height;
    const resolvedImageType =
        filled(imageType) ?? imageOverride?.type ?? imageDefaults.type;
    const jsonLdPayload =
        jsonLd === false
            ? undefined
            : (jsonLd ?? buildDefaultJsonLd(site, resolvedDescription));

    return (
        <Head>
            <title>{resolvedTitle}</title>
            {resolvedDescription && (
                <meta
                    head-key="description"
                    name="description"
                    content={resolvedDescription}
                />
            )}
            {resolvedCanonical && (
                <link
                    head-key="canonical"
                    rel="canonical"
                    href={resolvedCanonical}
                />
            )}
            {resolvedRobots && (
                <meta
                    head-key="robots"
                    name="robots"
                    content={resolvedRobots}
                />
            )}
            {site.themeColor && (
                <meta
                    head-key="theme-color"
                    name="theme-color"
                    content={site.themeColor}
                />
            )}
            <meta
                head-key="og:title"
                property="og:title"
                content={resolvedTitle}
            />
            <meta head-key="og:type" property="og:type" content={ogType} />
            {resolvedDescription && (
                <meta
                    head-key="og:description"
                    property="og:description"
                    content={resolvedDescription}
                />
            )}
            {resolvedCanonical && (
                <meta
                    head-key="og:url"
                    property="og:url"
                    content={resolvedCanonical}
                />
            )}
            <meta
                head-key="og:site_name"
                property="og:site_name"
                content={site.name}
            />
            <meta
                head-key="og:locale"
                property="og:locale"
                content={site.ogLocale}
            />
            {resolvedImage && (
                <>
                    <meta
                        head-key="og:image"
                        property="og:image"
                        content={resolvedImage}
                    />
                    <meta
                        head-key="og:image:secure_url"
                        property="og:image:secure_url"
                        content={resolvedImage}
                    />
                    {resolvedImageType && (
                        <meta
                            head-key="og:image:type"
                            property="og:image:type"
                            content={resolvedImageType}
                        />
                    )}
                    {resolvedImageWidth && (
                        <meta
                            head-key="og:image:width"
                            property="og:image:width"
                            content={String(resolvedImageWidth)}
                        />
                    )}
                    {resolvedImageHeight && (
                        <meta
                            head-key="og:image:height"
                            property="og:image:height"
                            content={String(resolvedImageHeight)}
                        />
                    )}
                    {resolvedImageAlt && (
                        <meta
                            head-key="og:image:alt"
                            property="og:image:alt"
                            content={resolvedImageAlt}
                        />
                    )}
                </>
            )}
            <meta
                head-key="twitter:card"
                name="twitter:card"
                content={resolvedImage ? 'summary_large_image' : 'summary'}
            />
            <meta
                head-key="twitter:title"
                name="twitter:title"
                content={resolvedTitle}
            />
            {resolvedDescription && (
                <meta
                    head-key="twitter:description"
                    name="twitter:description"
                    content={resolvedDescription}
                />
            )}
            {resolvedImage && (
                <>
                    <meta
                        head-key="twitter:image"
                        name="twitter:image"
                        content={resolvedImage}
                    />
                    {resolvedImageAlt && (
                        <meta
                            head-key="twitter:image:alt"
                            name="twitter:image:alt"
                            content={resolvedImageAlt}
                        />
                    )}
                </>
            )}
            {site.social.twitter.site && (
                <meta
                    head-key="twitter:site"
                    name="twitter:site"
                    content={site.social.twitter.site}
                />
            )}
            {site.social.twitter.creator && (
                <meta
                    head-key="twitter:creator"
                    name="twitter:creator"
                    content={site.social.twitter.creator}
                />
            )}
            {site.structuredData.enabled && jsonLdPayload && (
                <script
                    head-key="json-ld"
                    type="application/ld+json"
                    dangerouslySetInnerHTML={{
                        __html: safeJsonLd(jsonLdPayload),
                    }}
                />
            )}
            {children}
        </Head>
    );
}

function buildDefaultJsonLd(
    site: SiteSharedProps,
    description: string,
): JsonLdPayload | undefined {
    if (!site.structuredData.enabled) {
        return undefined;
    }

    const graph: Record<string, unknown>[] = [
        {
            '@type': 'WebSite',
            name: site.name,
            url: site.url,
            description,
        },
    ];
    const logo = resolveAbsoluteUrl(site.structuredData.logo, site.url);

    if (logo) {
        graph.push({
            '@type': 'Organization',
            name: site.name,
            url: site.url,
            logo,
        });
    }

    return {
        '@context': 'https://schema.org',
        '@graph': graph,
    };
}
