export type PageSurface =
    'public' | 'authentication' | 'application' | 'settings' | 'administration';

const applicationPages = new Set(['dashboard', 'home']);

export function classifyPageSurface(name: string): PageSurface {
    if (name.startsWith('auth/')) {
        return 'authentication';
    }

    if (name.startsWith('settings/')) {
        return 'settings';
    }

    if (name.startsWith('evolayer/admin/')) {
        return 'administration';
    }

    if (applicationPages.has(name) || name.startsWith('evolayer/ai/')) {
        return 'application';
    }

    // Public is deliberately the safe fallback. An unknown page must not gain
    // the authenticated sidebar merely because its visitor is signed in.
    return 'public';
}
