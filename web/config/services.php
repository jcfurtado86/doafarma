<?php

declare(strict_types = 1);

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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'consultacrm' => [
        'key'                => env('CONSULTACRM_API_KEY'),
        'url'                => env('CONSULTACRM_API_URL', 'https://www.consultacrm.com.br/api/index.php'),
        'cache_days_valid'   => env('CONSULTACRM_CACHE_DAYS_VALID', 30),
        'cache_days_invalid' => env('CONSULTACRM_CACHE_DAYS_INVALID', 7),
        'timeout'            => env('CONSULTACRM_TIMEOUT', 3),
    ],

];
