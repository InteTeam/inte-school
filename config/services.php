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

    'govuk_notify' => [
        'api_key' => env('GOVUK_NOTIFY_API_KEY'),
        'sms_template_id' => env('GOVUK_NOTIFY_SMS_TEMPLATE_ID'),
        'free_allowance' => (int) env('GOVUK_NOTIFY_FREE_ALLOWANCE', 5000),
        'cost_pence' => (int) env('GOVUK_NOTIFY_COST_PENCE', 3), // 2.4p + VAT ≈ 3p
    ],

];
