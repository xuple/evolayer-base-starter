/**
 * Client-side utility to detect the correct command palette modifier key.
 *
 * This purely presentational helper prevents hardcoded "⌘" hints from showing
 * to Windows/Linux users who actually need to press "Ctrl".
 */
type NavigatorWithUserAgentData = Navigator & {
    userAgentData?: {
        platform?: string;
    };
};

function getPlatformName(): string {
    if (typeof navigator === 'undefined') {
        return '';
    }

    const userAgentDataPlatform = (navigator as NavigatorWithUserAgentData)
        .userAgentData?.platform;

    if (userAgentDataPlatform) {
        return userAgentDataPlatform;
    }

    if (navigator.platform) {
        return navigator.platform;
    }

    return navigator.userAgent;
}

export function getCommandModifier(): string {
    if (typeof window === 'undefined' || typeof navigator === 'undefined') {
        return 'Ctrl'; // SSR fallback
    }

    // This is only used to label the shortcut hint, not to gate behavior.
    const isMac = /mac|iphone|ipad|ipod/i.test(getPlatformName());

    return isMac ? '⌘' : 'Ctrl';
}
