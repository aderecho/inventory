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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
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

    'saml' => [
        'sp_entity_id' => env('SAML_SP_ENTITY_ID', rtrim((string) env('APP_URL'), '/').'/saml2/metadata'),
        'idp_name' => env('SAML_IDP_NAME', 'UP Cebu AMS'),
        'idp_entity_id' => env('SAML_IDP_ENTITY_ID'),
        'idp_sso_url' => env('SAML_IDP_SSO_URL'),
        'idp_slo_url' => env('SAML_IDP_SLO_URL'),
        'idp_public_cert' => env('SAML_IDP_PUBLIC_CERT'),
        'assertion_ttl_seconds' => (int) env('SAML_ASSERTION_TTL_SECONDS', 300),
        'clock_skew_seconds' => (int) env('SAML_CLOCK_SKEW_SECONDS', 60),
        'require_signed_requests' => filter_var(env('SAML_REQUIRE_SIGNED_REQUESTS', false), FILTER_VALIDATE_BOOL),
        'sign_responses' => filter_var(env('SAML_SIGN_RESPONSES', true), FILTER_VALIDATE_BOOL),
        'sign_assertions' => filter_var(env('SAML_SIGN_ASSERTIONS', true), FILTER_VALIDATE_BOOL),
    ],

];
