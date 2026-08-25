<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Model Configuration
    |--------------------------------------------------------------------------
    |
    | This file configures all available AI models with their API keys,
    | endpoints, and settings. Keys are loaded from environment variables.
    |
    */

    'default' => env('AI_DEFAULT_MODEL', 'gemini'),

    'models' => [

        'claude' => [
            'name' => 'Claude',
            'provider' => 'anthropic',
            'api_key' => env('ANTHROPIC_API_KEY'),
            'endpoint' => 'https://api.anthropic.com/v1/messages',
            'model' => 'claude-sonnet-4-20250514',
            'max_tokens' => 4096,
            'icon' => 'ti-message-2',
            'color' => '#d97757',
            'supports' => ['text', 'image'],
        ],

        'openai' => [
            'name' => 'OpenAI GPT',
            'provider' => 'openai',
            'api_key' => env('OPENAI_API_KEY'),
            'endpoint' => 'https://api.openai.com/v1/chat/completions',
            'model' => 'gpt-4o',
            'max_tokens' => 4096,
            'icon' => 'ti-cpu',
            'color' => '#10a37f',
            'supports' => ['text', 'image'],
        ],

        'gemini' => [
            'name' => 'Gemini',
            'provider' => 'google',
            'api_key' => env('GEMINI_API_KEY'),
            'endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent',
            'model' => 'gemini-3.5-flash',
            'max_tokens' => 4096,
            'icon' => 'ti-planet',
            'color' => '#4285f4',
            'supports' => ['text', 'image'],
        ],

        'grok' => [
            'name' => 'Grok',
            'provider' => 'xai',
            'api_key' => env('GROK_API_KEY'),
            'endpoint' => 'https://api.x.ai/v1/chat/completions',
            'model' => 'grok-beta',
            'max_tokens' => 4096,
            'icon' => 'ti-rocket',
            'color' => '#1da1f2',
            'supports' => ['text'],
        ],

        'cohere' => [
            'name' => 'Cohere',
            'provider' => 'cohere',
            'api_key' => env('COHERE_API_KEY'),
            'endpoint' => 'https://api.cohere.ai/v2/chat',
            'model' => 'command-a-03-2025',
            'max_tokens' => 4096,
            'icon' => 'ti-palette',
            'color' => '#d4a017',
            'supports' => ['text'],
        ],

        'mistral' => [
            'name' => 'Mistral',
            'provider' => 'mistral',
            'api_key' => env('MISTRAL_API_KEY'),
            'endpoint' => 'https://api.mistral.ai/v1/chat/completions',
            'model' => 'mistral-large-latest',
            'max_tokens' => 4096,
            'icon' => 'ti-cloud',
            'color' => '#ff7000',
            'supports' => ['text'],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Models
    |--------------------------------------------------------------------------
    |
    | If the primary model fails, try these in order.
    |
    */
    'fallback_models' => ['openai', 'claude', 'gemini'],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limits for AI API calls per minute.
    |
    */
    'rate_limits' => [
        'default' => 30,
        'openai' => 60,
        'claude' => 40,
        'gemini' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Timeout Settings
    |--------------------------------------------------------------------------
    |
    | API request timeout in seconds.
    |
    */
    'timeout' => 90,

    /*
    |--------------------------------------------------------------------------
    | Retry Settings
    |--------------------------------------------------------------------------
    |
    | Number of retry attempts and delay between retries.
    |
    */
    'retries' => [
        'attempts' => 2,
        'delay' => 200, // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Extraction Prompt Templates
    |--------------------------------------------------------------------------
    |
    | Templates for different AI tasks.
    |
    */
    'templates' => [
        'extract_job' => 'extract_job_prompt',
        'enhance_field' => 'enhance_field_prompt',
        'generate_from_title' => 'generate_from_title_prompt',
        'image_extract' => 'image_extract_prompt',
    ],

    /*
    |--------------------------------------------------------------------------
    | Country-Specific Settings
    |--------------------------------------------------------------------------
    |
    | Default currency, language, and location settings by country.
    |
    */
    'country_settings' => [
        'AU' => ['currency' => 'AUD', 'locale' => 'en_AU', 'timezone' => 'Australia/Sydney'],
        'UG' => ['currency' => 'UGX', 'locale' => 'en_UG', 'timezone' => 'Africa/Kampala'],
        'KE' => ['currency' => 'KES', 'locale' => 'en_KE', 'timezone' => 'Africa/Nairobi'],
        'TZ' => ['currency' => 'TZS', 'locale' => 'en_TZ', 'timezone' => 'Africa/Dar_es_Salaam'],
        'RW' => ['currency' => 'RWF', 'locale' => 'en_RW', 'timezone' => 'Africa/Kigali'],
        'MW' => ['currency' => 'MWK', 'locale' => 'en_MW', 'timezone' => 'Africa/Blantyre'],
        'ZM' => ['currency' => 'ZMW', 'locale' => 'en_ZM', 'timezone' => 'Africa/Lusaka'],
        'SG' => ['currency' => 'SGD', 'locale' => 'en_SG', 'timezone' => 'Asia/Singapore'],
    ],
];