<?php

// `GALY_IMAGES_PATH` apunta en esta PC a la carpeta img de XAMPP. Esa ruta no
// existe en un servidor, y dejarla tal cual hace que subir una foto desde el
// panel falle. Si la ruta configurada no está, se usa la copia del proyecto
// (public/galy_api/img), que es la misma carpeta que la tienda ya sirve.
$carpetaConfigurada = trim((string) env('GALY_IMAGES_PATH', ''));

return [
    'api_url' => rtrim((string) env('GALY_API_URL', 'http://localhost/galy_api'), '/'),
    'public_url' => rtrim((string) env('GALY_PUBLIC_URL', 'http://localhost/galy-admin'), '/'),
    'api_key' => (string) env('GALY_API_KEY', ''),
    // Panel Angular de GALY: API REST (`/auth/web-sso`) y secreto HMAC que
    // comparten la tienda y ese backend para firmar los enlaces de un solo uso.
    'admin_api_url' => rtrim((string) env('GALY_ADMIN_API_URL', 'http://localhost/galy-admin/api/v1'), '/'),
    'sso_secret' => (string) env('GALY_SSO_SECRET', ''),
    // Permite crear la cuenta en la tienda cuando `galy_api` no está arriba.
    // Apagado por defecto: en esta PC la API sí existe, y allí se quiere fallar
    // cerrado. En un servidor sin `galy_api` es lo único que deja registrar clientes.
    'registro_local' => (bool) env('GALY_REGISTRO_LOCAL', false),
    'images_path' => $carpetaConfigurada !== '' && is_dir($carpetaConfigurada)
        ? $carpetaConfigurada
        : public_path('galy_api') . DIRECTORY_SEPARATOR . 'img',
];
