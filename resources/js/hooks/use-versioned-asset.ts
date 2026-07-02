import { usePage } from '@inertiajs/react';
import { appendPathVersion } from '@/lib/site-meta';

/**
 * Returns a function that appends the configured global asset version
 * (`SITE_ASSET_VERSION`, shared as `site.assetVersion`) as a `?v=` cache-buster
 * to a public asset path — so replacing an image in place is picked up past a
 * CDN cache. No-ops when the version is blank, so it is safe to wrap every
 * asset unconditionally:
 *
 *     const asset = useVersionedAsset();
 *     <img src={asset('/images/hero.jpg')} />
 */
export function useVersionedAsset(): (path: string) => string {
    const { site } = usePage().props;

    return (path: string) => appendPathVersion(path, site.assetVersion);
}
