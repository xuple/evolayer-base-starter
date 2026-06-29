<?php

/*
|--------------------------------------------------------------------------
| EvoLayer Base configuration
|--------------------------------------------------------------------------
|
| Merged into the top-level `evolayer` config key by Xuple\EvoLayer\Base\BaseServiceProvider.
| All Base settings live under `evolayer.base.*` so sibling packages (Commerce,
| SaaS, RLS, etc.) can claim `evolayer.commerce.*`, `evolayer.saas.*`, etc. without
| collision.
|
*/

return [
    'base' => [
        'examples' => [
            'thread_studio' => env('EVOLAYER_BASE_EXAMPLE_THREAD_STUDIO', false),
            'prd_studio' => env('EVOLAYER_BASE_EXAMPLE_PRD_STUDIO', false),
            'admin_inbox' => env('EVOLAYER_BASE_EXAMPLE_ADMIN_INBOX', false),
            'contact_ai' => env('EVOLAYER_BASE_EXAMPLE_CONTACT_AI', false),
            'voice_input' => env('EVOLAYER_BASE_EXAMPLE_VOICE_INPUT', false),
            'ai_text_field' => env('EVOLAYER_BASE_EXAMPLE_AI_TEXT_FIELD', false),
            'marketing_pages' => env('EVOLAYER_BASE_EXAMPLE_MARKETING_PAGES', false),
        ],

        'features' => [
            'contact_attachments' => env('EVOLAYER_BASE_FEATURE_CONTACT_ATTACHMENTS', false),
        ],

        // Brand surface for the published landing pages (home/about) and head
        // metadata. Hosts rebrand by overriding these via env or by sharing
        // evolayer.base.brand on the Inertia props (see Support\EvoLayerProps) —
        // so resync never needs to overwrite the page files to rebrand them.
        'brand' => [
            'name' => env('EVOLAYER_BASE_BRAND_NAME', 'EvoLayer Base'),
            'tagline' => env('EVOLAYER_BASE_BRAND_TAGLINE', 'A fully working AI application layer for the official Laravel AI SDK.'),
            'description' => env('EVOLAYER_BASE_BRAND_DESCRIPTION', 'Start from a working application: Laravel auth, typed routes, structured AI workflows, admin screens, ontology tooling, and local verification commands already wired together.'),
        ],

        'route' => [
            'middleware' => ['web'],
        ],
    ],
];
