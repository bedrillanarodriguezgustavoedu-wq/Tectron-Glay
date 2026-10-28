<?php
/**
 * Genera .env.production a partir del .env de esta PC.
 *
 * Copia los valores que sí tienen sentido en un servidor (nombre de la base,
 * moneda, TTL del checkout) y deja VACÍAS todas las claves secretas para que
 * nazcan o se peguen en el servidor. Nunca escribe sobre .env.
 *
 * Uso:  php deploy/generar-env-produccion.php
 */

$raiz = dirname(__DIR__);
$origen = $raiz.'/.env';
$destino = $raiz.'/.env.production';

if (! is_readable($origen)) {
    fwrite(STDERR, "No existe el archivo .env en {$raiz}\n");
    exit(1);
}

// Lo que en un servidor tiene que valer distinto, pase lo que diga el .env local.
$forzar = [
    'APP_NAME' => 'GALY',
    'APP_ENV' => 'production',
    'APP_DEBUG' => 'false',
    'APP_URL' => 'https://CAMBIA-ESTA-DIRECCION.pe',
    'LOG_CHANNEL' => 'daily',
    'LOG_LEVEL' => 'error',
    'CACHE_STORE' => 'file',
    'SESSION_DRIVER' => 'file',
    'SESSION_SECURE_COOKIE' => 'true',
    'QUEUE_CONNECTION' => 'sync',
    'BROADCAST_CONNECTION' => 'log',
    // Vacíos a propósito: la carpeta de fotos la resuelve config/galy.php según
    // el servidor, y sin MERCADOPAGO_URL_BASE el retorno usa APP_URL.
    'GALY_IMAGES_PATH' => '',
    'MERCADOPAGO_URL_BASE' => '',
    'GOOGLE_REDIRECT_URI' => '',
    // El correo de pruebas (Mailtrap) no sirve arriba: si queda, los avisos del
    // cliente se van a una bandeja de desarrollo y nadie se entera.
    'MAIL_MAILER' => 'smtp',
    'MAIL_HOST' => '',
    'MAIL_PORT' => '',
    'MAIL_USERNAME' => '',
    'MAIL_FROM_ADDRESS' => '',
    'GOOGLE_CLIENT_ID' => '',
    // Sin `galy_api` arriba, el registro de clientes es imposible: esta línea es la
    // que permite que la tienda cree la cuenta (y su fila en `usuarios`) ella misma.
    'GALY_REGISTRO_LOCAL' => 'true',
    // root solo funciona en tu XAMPP; dejarlo aquí invitaría a subirlo al servidor.
    'DB_USERNAME' => '',
];

// Cualquier clave con forma de credencial sale vacía del archivo.
$esSecreto = static fn (string $clave): bool => (bool) preg_match(
    '/(APP_KEY|API_KEY|ACCESS_KEY|SECRET|TOKEN|PASSWORD)/', $clave
);

// Lo que el dueño tiene que rellenar o generar arriba del servidor.
$obligatorio = [
    'APP_KEY' => 'php artisan key:generate',
    'DB_USERNAME' => 'el usuario que crees en el hosting (no root)',
    'DB_PASSWORD' => 'la contraseña de ese usuario',
    'MAIL_HOST' => 'servidor SMTP real (no Mailtrap)',
    'MAIL_PORT' => '587 suele ser el puerto',
    'MAIL_USERNAME' => 'usuario del SMTP',
    'MAIL_PASSWORD' => 'contraseña del SMTP',
    'MAIL_FROM_ADDRESS' => 'un correo tuyo verificando el dominio',
    'GOOGLE_CLIENT_ID' => 'credencial OAuth de Google con el dominio autorizado',
    'GOOGLE_CLIENT_SECRET' => 'el Secret que va junto a ese Client ID',
    'MERCADOPAGO_ACCESS_TOKEN' => 'Access Token de producción del panel de Mercado Pago',
    'MERCADOPAGO_WEBHOOK_SECRET' => 'Secreto de firma que crea el propio panel de Mercado Pago',
    'APP_URL' => 'tu dominio con https:// (de esto depende el retorno de la tarjeta)',
];

$lineas = file($origen, FILE_IGNORE_NEW_LINES) ?: [];
$salida = [];
$vacias = [];
$reescritas = [];

foreach ($lineas as $linea) {
    if (preg_match('/^\s*([A-Z0-9_]+)=(.*)$/', $linea, $m)) {
        $clave = $m[1];

        if ($esSecreto($clave)) {
            $salida[] = $clave.'=';
            if (trim($m[2], "\" \t") !== '') {
                $vacias[] = $clave;
            }
            continue;
        }

        if (array_key_exists($clave, $forzar)) {
            $salida[] = $clave.'='.$forzar[$clave];
            $reescritas[] = $clave;
            continue;
        }

        $salida[] = $linea;
        continue;
    }

    $salida[] = $linea;
}

// Claves de producción que el .env local no trae.
$existentes = [];
foreach ($salida as $linea) {
    if (preg_match('/^([A-Z0-9_]+)=/', $linea, $m)) {
        $existentes[$m[1]] = true;
    }
}

foreach (array_merge(array_keys($forzar), array_keys($obligatorio)) as $clave) {
    if (isset($existentes[$clave])) {
        continue;
    }
    $salida[] = $clave.'='.(isset($forzar[$clave]) ? $forzar[$clave] : '');
    $existentes[$clave] = true;
}

$avisos = [
    '',
    '# ─── Antes de usar este archivo, completa en el servidor ───',
];
foreach ($obligatorio as $clave => $como) {
    $avisos[] = "# {$clave} → {$como}";
}
$avisos[] = '# Las credenciales se pegan en el servidor, nunca se suben desde esta PC.';
$avisos[] = '# GALY_REGISTRO_LOCAL=true → la tienda crea la cuenta cuando galy_api no está';
$avisos[] = '#   arriba. Si además subes galy_api al mismo servidor, ponla en false.';

$archivo = implode("\n", $salida)."\n".implode("\n", $avisos)."\n";

// Red de seguridad: ninguna clave con forma de credencial puede salir con valor.
foreach (explode("\n", $archivo) as $linea) {
    if (preg_match('/^([A-Z0-9_]*(KEY|SECRET|TOKEN|PASSWORD)[A-Z0-9_]*)=(.+)$/', $linea, $m)) {
        fwrite(STDERR, "La clave {$m[1]} salió con valor. El archivo no se escribió.\n");
        exit(1);
    }
}

file_put_contents($destino, $archivo);

echo "Escrito {$destino}\n";
echo 'Líneas: '.count($salida).' · secretos vaciados: '.count($vacias).' · forzado producción: '.count($reescritas)."\n";
if ($vacias !== []) {
    echo 'Claves dejadas en blanco: '.implode(', ', $vacias)."\n";
}
echo "Faltan por completar: ".count($obligatorio)." (están listados al final del archivo).\n";
