<?php

return [

    // Catalogo carte (vedi App\Services\TcgdexService)
    'tcgdex' => [
        'url'             => env('TCGDEX_URL', 'https://api.tcgdex.net/v2'),
        'timeout'         => (int) env('TCGDEX_TIMEOUT', 30),
        'connect_timeout' => (int) env('TCGDEX_CONNECT_TIMEOUT', 10),
        'retries'         => (int) env('TCGDEX_RETRIES', 3),
        // Sincronizzazione settimanale automatica (set e carte con i prezzi): off di default
        'scheduled_sync'  => (bool) env('TCGDEX_SCHEDULED_SYNC', false),
    ],

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
