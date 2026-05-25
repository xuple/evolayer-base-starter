export const docsBaseUrl = 'https://docs.evodevops.com/base';

export const starterDocsCommands = [
    {
        title: 'Open: EvoLayer Base Docs',
        description: 'Open the starter base documentation and setup guidance.',
        value: 'open evodevops base docs documentation help support qa',
        url: docsBaseUrl,
        icon: 'base',
    },
    {
        title: 'Open: Laravel 13 Docs',
        description: 'Browse the official Laravel 13 documentation.',
        value: 'open laravel 13 docs documentation framework php',
        url: 'https://laravel.com/docs/13.x',
        icon: 'laravel',
    },
    {
        title: 'Open: Inertia 3 Docs',
        description: 'Browse the official Inertia v3 documentation.',
        value: 'open inertia 3 docs documentation inertiajs react v3',
        url: 'https://inertiajs.com/docs/v3/getting-started',
        icon: 'inertia',
    },
] as const;
