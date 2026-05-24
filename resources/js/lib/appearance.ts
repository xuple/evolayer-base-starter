export const APPEARANCE_INTENTS = ['light', 'dark', 'system'] as const;
export const RESOLVED_APPEARANCES = ['light', 'dark'] as const;

export type AppearanceIntent = (typeof APPEARANCE_INTENTS)[number];
export type ResolvedAppearance = (typeof RESOLVED_APPEARANCES)[number];

export type AppearanceThemeColors = {
    light: string;
    dark: string;
};

export type AppearanceConfig = {
    cookieName: string;
    defaultIntent: AppearanceIntent;
    themeColors: AppearanceThemeColors;
};

export type AppearanceState = {
    intent: AppearanceIntent;
    resolved: ResolvedAppearance;
    config: AppearanceConfig;
};

export type AppearanceSnapshot = {
    intent: AppearanceIntent;
    resolved: ResolvedAppearance;
};

export function isAppearanceIntent(value: unknown): value is AppearanceIntent {
    return (
        typeof value === 'string' &&
        APPEARANCE_INTENTS.includes(value as AppearanceIntent)
    );
}

export function normalizeAppearanceIntent(
    value: unknown,
    fallback: AppearanceIntent = 'system',
): AppearanceIntent {
    return isAppearanceIntent(value) ? value : fallback;
}

export function resolveAppearance(
    intent: AppearanceIntent,
    prefersDark: boolean,
): ResolvedAppearance {
    if (intent === 'light' || intent === 'dark') {
        return intent;
    }

    return prefersDark ? 'dark' : 'light';
}
