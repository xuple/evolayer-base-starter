<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default AI Provider Names
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the AI providers below should be the
    | default for AI operations when no explicit provider is provided
    | for the operation. This should be any provider defined below.
    |
    */

    'default' => env('AI_DEFAULT_PROVIDER', 'gemini'),
    'default_for_images' => 'gemini',
    'default_for_audio' => 'openai',
    'default_for_transcription' => 'openai',
    'default_for_embeddings' => 'openai',
    'default_for_reranking' => 'cohere',

    /*
    |--------------------------------------------------------------------------
    | Showcase Features
    |--------------------------------------------------------------------------
    |
    | The starter's ThreadStudio surface uses Laravel AI SDK agents with structured
    | output. Runtime-approved ThreadStudio providers are the directly-verified ones —
    | Gemini and OpenAI today (ADR-020). Gemini is the lowest-friction first-run
    | path (native SDK provider, no custom base URL); OpenAI is the second
    | matrix-verified provider.
    |
    | NVIDIA, OpenCode, and OpenRouter remain configured here as OpenAI-compatible
    | router-backed probe candidates — exercisable via the broad diagnostic commands
    | (evolayer:ai:probe / smoke-test / stream-check) — but they are NOT runtime-approved
    | for ThreadStudio runtime selection until directly verified. Anthropic is
    | likewise diagnostic-known but blocked/pending for ThreadStudio while its
    | structured streaming emits no usable TextDelta events.
    |
    */

    'thread_studio' => [
        'provider' => env('AI_THREAD_STUDIO_PROVIDER', 'gemini'),
        'timeout' => (int) env('AI_THREAD_STUDIO_TIMEOUT', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Below you may configure caching strategies for AI related operations
    | such as embedding generation. You are free to adjust these values
    | based on your application's available caching stores and needs.
    |
    */

    'caching' => [
        'embeddings' => [
            'cache' => false,
            'store' => env('CACHE_STORE', 'database'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Providers
    |--------------------------------------------------------------------------
    |
    | Below are each of your AI providers defined for this application. Each
    | represents an AI provider and API key combination which can be used
    | to perform tasks like text, image, and audio creation via agents.
    |
    */

    'providers' => [
        'anthropic' => [
            'driver' => 'anthropic',
            'key' => env('ANTHROPIC_API_KEY'),
            'url' => env('ANTHROPIC_URL', 'https://api.anthropic.com/v1'),
            'models' => [
                'text' => [
                    'default' => env('ANTHROPIC_CHAT_MODEL', 'claude-haiku-4-5-20251001'),
                ],
            ],
        ],

        'azure' => [
            'driver' => 'azure',
            'key' => env('AZURE_OPENAI_API_KEY'),
            'url' => env('AZURE_OPENAI_URL'),
            'api_version' => env('AZURE_OPENAI_API_VERSION', '2025-04-01-preview'),
            'deployment' => env('AZURE_OPENAI_DEPLOYMENT', 'gpt-4o'),
            'embedding_deployment' => env('AZURE_OPENAI_EMBEDDING_DEPLOYMENT', 'text-embedding-3-small'),
        ],

        'bedrock' => [
            'driver' => 'bedrock',
            'region' => env('AWS_BEDROCK_REGION', 'us-east-1'),
            'key' => env('AWS_BEARER_TOKEN_BEDROCK'),
            'access_key_id' => env('AWS_ACCESS_KEY_ID'),
            'secret_access_key' => env('AWS_SECRET_ACCESS_KEY'),
            'session_token' => env('AWS_SESSION_TOKEN'),
            'use_default_credential_provider' => env('AWS_USE_DEFAULT_CREDENTIALS', true),
        ],

        'cohere' => [
            'driver' => 'cohere',
            'key' => env('COHERE_API_KEY'),
            'url' => env('COHERE_URL', 'https://api.cohere.com/v2'),
        ],

        'deepseek' => [
            'driver' => 'deepseek',
            'key' => env('DEEPSEEK_API_KEY'),
            'url' => env('DEEPSEEK_URL', 'https://api.deepseek.com'),
        ],

        'eleven' => [
            'driver' => 'eleven',
            'key' => env('ELEVENLABS_API_KEY'),
        ],

        'gemini' => [
            'driver' => 'gemini',
            'key' => env('GEMINI_API_KEY'),
            'url' => env('GEMINI_URL', 'https://generativelanguage.googleapis.com/v1beta'),
            'models' => [
                'text' => [
                    'default' => env('GEMINI_CHAT_MODEL', 'gemini-3-flash-preview'),
                ],
            ],
        ],

        'groq' => [
            'driver' => 'groq',
            'key' => env('GROQ_API_KEY'),
            'url' => env('GROQ_URL', 'https://api.groq.com/openai/v1'),
        ],

        'jina' => [
            'driver' => 'jina',
            'key' => env('JINA_API_KEY'),
            'url' => env('JINA_URL', 'https://api.jina.ai/v1'),
        ],

        'mistral' => [
            'driver' => 'mistral',
            'key' => env('MISTRAL_API_KEY'),
            'url' => env('MISTRAL_URL', 'https://api.mistral.ai/v1'),
        ],

        'ollama' => [
            'driver' => 'ollama',
            'key' => env('OLLAMA_API_KEY', ''),
            'url' => env('OLLAMA_URL', 'http://localhost:11434'),
        ],

        'openai' => [
            'driver' => 'openai',
            'key' => env('OPENAI_API_KEY'),
            'url' => env('OPENAI_URL', 'https://api.openai.com/v1'),
            'models' => [
                'text' => [
                    // OpenAI is a runtime-approved ThreadStudio provider (ADR-020). It
                    // needs a default model so ThreadStudio can resolve one when
                    // none is selected; without this the feature falls back to
                    // the defaultModel() sentinel and fails at request time.
                    'default' => env('OPENAI_CHAT_MODEL', 'gpt-4o-mini'),
                ],
            ],
        ],

        'openrouter' => [
            'driver' => 'openrouter',
            'key' => env('OPENROUTER_API_KEY'),
            'url' => env('OPENROUTER_URL', 'https://openrouter.ai/api/v1'),
            'http_referer' => env('OPENROUTER_HTTP_REFERER'),
            'x_title' => env('OPENROUTER_TITLE', env('APP_NAME', 'EvoLayer Base')),
            'models' => [
                'text' => [
                    'default' => env('OPENROUTER_CHAT_MODEL', 'openrouter/auto'),
                ],
            ],
        ],

        'voyageai' => [
            'driver' => 'voyageai',
            'key' => env('VOYAGEAI_API_KEY'),
            'url' => env('VOYAGEAI_URL', 'https://api.voyageai.com/v1'),
        ],

        'xai' => [
            'driver' => 'xai',
            'key' => env('XAI_API_KEY'),
            'url' => env('XAI_URL', 'https://api.x.ai/v1'),
        ],

        'nvidia' => [
            // NVIDIA hosted Integrate exposes an OpenAI-compatible Chat Completions surface.
            // The SDK's OpenRouter gateway speaks that Chat Completions request shape.
            'driver' => 'openrouter',
            'key' => env('NVIDIA_API_KEY'),
            'url' => env('NVIDIA_URL', 'https://integrate.api.nvidia.com/v1'),
            'models' => [
                'text' => [
                    'default' => env('NVIDIA_CHAT_MODEL', 'deepseek-ai/deepseek-v4-flash'),
                ],
            ],
        ],

        /*
         * OpenCode Go: OpenAI-compatible Chat Completions endpoint.
         *
         * Model capability for ThreadStudio is tracked in
         * ThreadStudioAiConfig::opencodeModelCompatibility().
         * Keep that method aligned with the Go model catalogue at https://opencode.ai/go.
         */
        'opencode' => [
            'driver' => 'openrouter',
            'key' => env('OPENCODE_API_KEY'),
            'url' => env('OPENCODE_URL', 'https://opencode.ai/zen/go/v1'),
            'models' => [
                'text' => [
                    'default' => env('OPENCODE_CHAT_MODEL', 'kimi-k2.6'),
                ],
            ],
        ],
    ],

];
