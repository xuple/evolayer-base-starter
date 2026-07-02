export function filled(value: string | null | undefined): string | undefined {
    const trimmed = value?.trim();

    return trimmed ? trimmed : undefined;
}

export function applyTitleTemplate(
    title: string,
    template: string,
    fallbackName: string,
): string {
    if (title.trim() === fallbackName.trim()) {
        return fallbackName;
    }

    return template.includes('%s')
        ? template.replace('%s', title)
        : `${title} | ${fallbackName}`;
}

export function resolveAbsoluteUrl(
    value: string | null | undefined,
    baseUrl: string,
): string | undefined {
    const pathOrUrl = filled(value);

    if (!pathOrUrl) {
        return undefined;
    }

    try {
        return new URL(pathOrUrl).toString();
    } catch {
        return new URL(pathOrUrl, withTrailingSlash(baseUrl)).toString();
    }
}

export function appendVersion(
    url: string | undefined,
    version: string | null | undefined,
): string | undefined {
    const cleanVersion = filled(version);

    if (!url || !cleanVersion) {
        return url;
    }

    const parsed = new URL(url);

    if (parsed.searchParams.has('v')) {
        return parsed.toString();
    }

    parsed.searchParams.set('v', cleanVersion);

    return parsed.toString();
}

export function resolveVersionedUrl(
    value: string | null | undefined,
    baseUrl: string,
    version?: string | null,
): string | undefined {
    return appendVersion(resolveAbsoluteUrl(value, baseUrl), version);
}

/**
 * Append a `?v=` cache-buster to a **relative** asset path, preserving the
 * path, any existing query string, and any hash. No-ops when the version is
 * blank or a `v` param is already present. Unlike {@link resolveVersionedUrl}
 * this does not absolutise against the site URL, so asset `src`s stay
 * same-origin even when `SITE_URL` points at a different host.
 */
export function appendPathVersion(
    path: string,
    version: string | null | undefined,
): string {
    const cleanVersion = filled(version);

    if (!cleanVersion) {
        return path;
    }

    const hashIndex = path.indexOf('#');
    const hash = hashIndex === -1 ? '' : path.slice(hashIndex);
    const base = hashIndex === -1 ? path : path.slice(0, hashIndex);

    if (/[?&]v=/.test(base)) {
        return path;
    }

    const separator = base.includes('?') ? '&' : '?';

    return `${base}${separator}v=${encodeURIComponent(cleanVersion)}${hash}`;
}

export function safeJsonLd(payload: unknown): string {
    return JSON.stringify(payload).replace(/</g, '\\u003c');
}

function withTrailingSlash(url: string): string {
    return url.endsWith('/') ? url : `${url}/`;
}
