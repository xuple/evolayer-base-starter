export const commandPalettePlaceholder = 'Search pages and commands...';

export const commandPaletteCopy = {
    emptyState: 'No matching destinations.',
    groups: {
        docs: 'Docs',
        navigation: 'Navigation',
    },
    footerHints: [
        {
            label: 'navigate',
            keys: 'arrows',
        },
        {
            label: 'open',
            keys: 'enter',
        },
        {
            label: 'toggle',
            keys: 'modifier',
        },
        {
            label: 'close',
            keys: 'escape',
        },
    ],
} as const;
