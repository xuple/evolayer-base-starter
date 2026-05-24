<?php

/*
|--------------------------------------------------------------------------
| EvoDevOps Base configuration
|--------------------------------------------------------------------------
|
| Merged into the top-level `evo` config key by EvoDevOps\Base\BaseServiceProvider.
| All Base settings live under `evo.base.*` so sibling packages (Commerce,
| SaaS, RLS, etc.) can claim `evo.commerce.*`, `evo.saas.*`, etc. without
| collision.
|
*/

return [
    'base' => [
        'examples' => [
            'thread_studio' => env('EVO_BASE_EXAMPLE_THREAD_STUDIO', false),
            'prd_studio' => env('EVO_BASE_EXAMPLE_PRD_STUDIO', false),
            'admin_inbox' => env('EVO_BASE_EXAMPLE_ADMIN_INBOX', false),
            'contact_ai' => env('EVO_BASE_EXAMPLE_CONTACT_AI', false),
            'voice_input' => env('EVO_BASE_EXAMPLE_VOICE_INPUT', false),
            'ai_text_field' => env('EVO_BASE_EXAMPLE_AI_TEXT_FIELD', false),
            'marketing_pages' => env('EVO_BASE_EXAMPLE_MARKETING_PAGES', false),
        ],

        'features' => [
            'contact_attachments' => env('EVO_BASE_FEATURE_CONTACT_ATTACHMENTS', false),
        ],

        'route' => [
            'middleware' => ['web'],
        ],
    ],
];
