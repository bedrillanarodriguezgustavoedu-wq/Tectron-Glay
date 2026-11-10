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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env(
            'GOOGLE_REDIRECT_URI',
            rtrim(env('APP_URL', 'http://localhost'), '/').'/auth/google/callback'
        ),
    ],

    'mercadopago' => [
        'enabled' => env('MERCADOPAGO_ENABLED', false),
        'access_token' => env('MERCADOPAGO_ACCESS_TOKEN'),
        'webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET'),
        'currency' => env('MERCADOPAGO_CURRENCY', 'PEN'),
        'url_base' => env('MERCADOPAGO_URL_BASE'),
        'checkout_ttl_minutes' => env('MERCADOPAGO_CHECKOUT_TTL_MINUTES', 30),
        'api_url' => env('MERCADOPAGO_API_URL', 'https://api.mercadopago.com'),
        'statement_descriptor' => env('MERCADOPAGO_STATEMENT_DESCRIPTOR', 'GALY'),
        // El formulario de tarjeta dentro de la tienda (Checkout Bricks) pide la
        // Public Key además del Access Token, y se enciende aparte: apagado, el
        // cobro sigue yendo a la pantalla hospedada de Mercado Pago.
        'public_key' => env('MERCADOPAGO_PUBLIC_KEY'),
        'bricks' => env('MERCADOPAGO_BRICKS', false),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
