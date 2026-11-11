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

    /*
    | Izipay (Mi Cuenta Web / PayZen). El cobro lo hace el formulario embebido de
    | Izipay con un `formToken`: la tienda nunca recibe el número ni el CVV.
    | Las cuatro credenciales salen del Back Office Vendedor de Izipay.
    */
    'izipay' => [
        'enabled' => env('IZIPAY_ENABLED', false),
        // Código de comercio (usuario) y clave API del botón de pagos.
        'shop_id' => env('IZIPAY_SHOP_ID'),
        'password' => env('IZIPAY_PASSWORD'),
        // Clave pública que usa el navegador para dibujar el formulario.
        'public_key' => env('IZIPAY_PUBLIC_KEY'),
        // Clave HMAC-SHA-256 con la que se valida `kr-answer`.
        'hmac_key' => env('IZIPAY_HMAC_KEY'),
        'environment' => env('IZIPAY_ENVIRONMENT', 'production'),
        'checkout_ttl_minutes' => env('IZIPAY_CHECKOUT_TTL_MINUTES', 30),
        // Se pueden sobrescribir para sandbox o para un proxy propio.
        'api_url' => env('IZIPAY_API_URL'),
        'script_url' => env('IZIPAY_SCRIPT_URL'),
        // URL pública a la que Izipay devuelve al cliente. Si va vacía se usa APP_URL.
        'url_base' => env('IZIPAY_URL_BASE'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
