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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'sso' => [
        'base_url' => rtrim((string) env('SSO_BASE_URL', ''), '/'),
        'frontend_url' => rtrim((string) env('SSO_FRONTEND_URL', ''), '/'),
        'client_id' => env('SSO_CLIENT_ID'),
        'client_secret' => env('SSO_CLIENT_SECRET'),
        'callback_url' => env('SSO_CALLBACK_URL'),
        'dev_bypass_enabled' => (bool) env('SSO_DEV_BYPASS_ENABLED', false),
        'dev_bypass_username' => env('SSO_DEV_BYPASS_USERNAME', 'local.dev'),
        'dev_bypass_name' => env('SSO_DEV_BYPASS_NAME', 'Local Developer'),
        'dev_bypass_role' => env('SSO_DEV_BYPASS_ROLE', 'developer'),
    ],

];
