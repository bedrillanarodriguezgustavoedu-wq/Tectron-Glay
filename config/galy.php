<?php

return [
    'api_url' => rtrim((string) env('GALY_API_URL', 'http://localhost/galy_api'), '/'),
    'public_url' => rtrim((string) env('GALY_PUBLIC_URL', 'http://localhost/galy-admin'), '/'),
    'api_key' => (string) env('GALY_API_KEY', ''),
    'images_path' => env('GALY_IMAGES_PATH', 'C:/xampp/htdocs/galy_api/img'),
];
