<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'model' => env('OPENAI_MODEL', 'gpt-5.6-sol'),
        'transcription_model' => env('OPENAI_TRANSCRIPTION_MODEL', 'gpt-4o-mini-transcribe'),
        'models' => [
            'gpt-5.6-sol' => [
                'label' => 'GPT-5.6 Sol',
                'description' => 'بالاترین کیفیت',
            ],
            'gpt-5.6-terra' => [
                'label' => 'GPT-5.6 Terra',
                'description' => 'متعادل و سریع',
            ],
            'gpt-5.6-luna' => [
                'label' => 'GPT-5.6 Luna',
                'description' => 'سریع و اقتصادی',
            ],
        ],
        'pricing' => [
            'gpt-5.6-sol' => ['input' => 1.25, 'output' => 10.00],
            'gpt-5.6-terra' => ['input' => 0.25, 'output' => 2.00],
            'gpt-5.6-luna' => ['input' => 0.05, 'output' => 0.40],
        ],
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
