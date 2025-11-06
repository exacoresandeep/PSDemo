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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'greythr' => [
        'api_user' => env('GREYTHR_API_USER', 'demo1'),
        'api_key'  => env('GREYTHR_API_KEY', 'b223954f-bdc9-406f-b87f-62f158d9734e'),
        'host'     => env('GREYTHR_HOST', 'tousifapisso.greythr.com'),
        'api_url'  => env('GREYTHR_API_URL', 'https://api.greythr.com'),
    ],
    'sap' => [
        'api_url' => env('SAP_API_URL', 'http://192.168.0.3:8081')
    ],

];
