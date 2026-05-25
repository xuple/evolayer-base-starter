<?php

/*
|--------------------------------------------------------------------------
| EvoLayer Base configuration
|--------------------------------------------------------------------------
|
| Merged into the top-level `evo` config key by Xuple\EvoLayer\Base\BaseServiceProvider.
| All Base settings live under `evolayer.base.*` so sibling packages (Commerce,
| SaaS, RLS, etc.) can claim `evo.commerce.*`, `evo.saas.*`, etc. without
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

        'route' => [
            'middleware' => ['web'],
        ],
    ],
];
