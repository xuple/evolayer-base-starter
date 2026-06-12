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

export function safeJsonLd(payload: unknown): string {
    return JSON.stringify(payload).replace(/</g, '\\u003c');
}

function withTrailingSlash(url: string): string {
    return url.endsWith('/') ? url : `${url}/`;
}
