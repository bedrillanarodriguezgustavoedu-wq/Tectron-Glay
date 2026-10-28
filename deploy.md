# Subir la tienda GALY a un servidor real

Todo esto asume que ya tienes **dominio con HTTPS** (Mercado Pago lo exige: sin candado no emite credenciales de producción ni registra el webhook).

## 0. La única línea que cambias

Abre una terminal en el servidor y define la ruta donde vivirá la tienda. Todos los comandos de abajo usan `$RUTA`; si no la cambias, fallan con "No such file or directory" y no pasa nada malo.

```bash
RUTA=/home/TU-USUARIO/tudominio            # <── cambia solo esta línea
```

## 0.5 El bloqueo que no se ve: la tienda no registra clientes sola

El carrito entero se arma sobre el id de usuario de GALY (`resolveGalyUserId()`), y
**crear** una cuenta era, hasta hoy, una orden que la web le pasaba a `galy_api`
(`register.php`). Si subes solo la Laravel y dejas `GALY_API_URL=http://localhost/galy_api`,
ningún cliente nuevo puede registrarse y por lo tanto nadie puede comprar.

Dos salidas, y ya están preparadas las dos:

- **Subir también `galy_api`.** Es PHP suelto sobre la misma base `ecommerce` y no usa
  rutas de Windows; así la web se comporta exactamente igual que en tu PC.
- **Dejar que la tienda cree las cuentas.** `deploy/generar-env-produccion.php` ya pone
  `GALY_REGISTRO_LOCAL=true` en el `.env` del servidor. Si `galy_api` no responde, la web
  inserta su fila en `usuarios` (bcrypt, el mismo hash que usa `register.php`), liga
  `galy_user_id`, asigna el rol `CLIENTE` y deja entrar al carrito. **Si `galy_api`
  responde y rechaza el correo, no se reintenta:** así no se duplica una identidad.

Entrar con una cuenta que ya existe funciona en los dos casos: la web primero intenta con
GALY y, si el panel no está arriba, usa su propia contraseña guardada.

## 1. Armar el paquete desde esta PC

```powershell
cd "E:\ecommerce (4)\ecommerce"
powershell -File deploy\paquetar.ps1 -ConVendor
```

`-ConVendor` mete `vendor/` en el ZIP para que el servidor **no** necesite Composer. Sin ese flag el paquete pesa ~180 MB; con vendor, ~260 MB. Si el hosting no deja subir un archivo tan grande, hazlo sin `-ConVendor` y corre Composer en el servidor (paso 4).

El script **nunca** incluye `.env` ni los `.env.copia-*`: salen de la PC vacíos de credenciales. Lo que sí viaja es `.env.production`.

Verifica antes de subir que el ZIP no tenga tu archivo de secretos:

```powershell
powershell -File deploy\paquetar.ps1 -SoloListar   # muestra tamaños, no copia nada
```

## 2. Subir y descomprimir

Sube `deploy/galy-web-*.zip` y descomprímelo dentro de `$RUTA`.

**Dónde tiene que apuntar el dominio:** a `$RUTA/public`. Es la carpeta con `index.php`. Si tu hosting no deja cambiar el document root (cPanel clásico), sube el proyecto a `$RUTA/tienda` y pon en `$RUTA/public_html` un `.htaccess` con:

```apache
RewriteEngine On
RewriteRule ^(.*)$ /tienda/public/$1 [L]
```

## 3. El `.env` del servidor

```bash
cd $RUTA
cp .env.production .env
```

Las otras claves edítalas con `nano $RUTA/.env` si tienes terminal SSH, o en cPanel: *File Manager* → clic derecho sobre `.env` → *Edit*.

`php artisan key:generate` rellena `APP_KEY` solo:

```bash
php artisan key:generate
```

Quedan 13 claves por completar (están listadas al final del propio `.env`). Las que más rompen si se olvidan:

| Clave | Si la dejas vacía |
|---|---|
| `APP_URL` | Nada arma URLs: correos sin link, y el retorno de Mercado Pago apunta a `CAMBIA-ESTA-DIRECCION.pe`. Se ve en `/admin/pagos` con el aviso rojo. |
| `DB_USERNAME` / `DB_PASSWORD` | La tienda abre con "SQLSTATE[…] Access denied". |
| `MERCADOPAGO_ACCESS_TOKEN` | El carrito muestra Tarjeta desactivada. Sin token no hay cobro. |
| `MERCADOPAGO_WEBHOOK_SECRET` | El cobro igual llega, pero cualquiera podría POSTEAR fingiendo un pago. No lo dejes vacío. |
| `MAIL_*` | El pedido se crea, el cliente no recibe el correo. |

`GALY_IMAGES_PATH` va **vacío a propósito**: `config/galy.php` usa entonces `public/galy_api/img`, que es la carpeta que el propio dominio sirve.

## 4. Dependencias

```bash
cd $RUTA
composer install --no-dev --optimize-autoloader   # solo si el ZIP salió sin vendor/
```

Extensiones de PHP que sí hacen falta (con `php -m` las ves): `ctype filter hash mbstring openssl session tokenizer curl fileinfo json pdo_mysql`. **`gd` e `intl` no se usan en este proyecto** — no las pidas al soporte del hosting.

PHP tiene que ser **8.3 o superior** (`php -v`). En cPanel se cambia en *MultiPHP Manager*.

## 5. Permisos y el enlace de storage

```bash
cd $RUTA
chgrp -R www-data storage bootstrap/cache 2>/dev/null
chmod -R ug+rwx storage bootstrap/cache
php artisan storage:link
mkdir -p public/galy_api/img
chmod -R 775 public/galy_api/img storage/app/public
```

Sin `storage:link`, el logo, los avatares y las fotos subidas desde el panel salen 404. Si el hosting no da terminal, crea el enlace desde el panel de archivos o copia `storage/app/public` dentro de `public/storage` (menos limpio, pero funciona).

## 6. Base de datos y caché

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

`--force` es obligatorio con `APP_ENV=production`, si no Laravel se queda esperando un "yes".

**La base:** decide si el servidor arranca vacío (y cargas catálogo a mano) o si le subes un volcado de tu `ecommerce` actual. Tu base de hoy es de desarrollo: 64 productos, 6 usuarios y 37 pedidos reales con el #36 por S/ 4200. Para volcarla desde esta PC:

```bash
mysqldump -u root -h 127.0.0.1 --default-character-set=utf8mb4 ecommerce > galy-ecommerce.sql
```

Y se importa desde phpMyAdmin del hosting. Si subes el volcado, **no corras los seeders** otra vez.

## 7. Mercado Pago

1. Panel de Mercado Pago → *Credenciales de producción*: pon el dominio real en *Sitio web* y copia el **Access Token** al `.env` del servidor. Nunca lo pegues en un chat ni lo subas por FTP.
2. *Webhooks*: registra `https://tudominio.pe/webhooks/mercadopago` y activa **Pago**. Copia el secreto de firma a `MERCADOPAGO_WEBHOOK_SECRET`.
3. Abre `/admin/ajustes` en el navegador: la tarjeta tiene que decir **"Pasarela configurada"** (si dice "credencial de prueba", el token pegado sigue siendo del entorno de pruebas y ningún cliente real puede pagar). Después abre `/admin/pagos`: ahí se ve en verde si el **enlace de retorno** ya es tu dominio y no el túnel.
4. En el servidor **ya no necesitas el túnel ni `tunel-galy.bat`**: eso era solo para cobrar desde esta PC.

## 8. El planificador (ahora sí es obligatorio)

Sin esto, los intentos de tarjeta se quedan en `esperando_pago`, las reservas no vencen y nada se reconcilia. En cPanel: *Cron Jobs*, cada 1 minuto:

```bash
* * * * * cd $RUTA && php artisan schedule:run >> /dev/null 2>&1
```

Corre tres cosas: `reservas:expirar` (cada minuto), `mercadopago:reconciliar` (cada 10 min) y `mercadopago:expirar` (cada 15 min).

Prueba que funcione:

```bash
cd $RUTA && php artisan schedule:list
```

## 9. Login con Google

`GOOGLE_REDIRECT_URI` va **vacío**: así se arma solo desde `APP_URL`. En Google Cloud Console → *Credenciales* agrega la URL autorizada `https://tudominio.pe/auth/google/callback`. Si no, el botón falla con `Error 400 (redirect_uri_mismatch)`.

Si prefieres no depender de esto el primer día, oculta el botón de Google en la vista de login: el resto del sitio no lo necesita.

## 10. Prueba final (7 clics)

1. `/` carga con estilos (si llega sin CSS, falta `public/build`).
2. Producto → carrito → cupón `GALY2026` → el total baja.
3. Checkout con un correo tuyo → llega el correo de confirmación.
4. Tarjeta → Mercado Pago → **paga con una tarjeta real de bajo monto o cancélatelo** para ver el rechazo.
5. Vuelve a la tienda: el retorno muestra el mensaje correcto y `/admin/pagos` muestra el intento (`fallido` si lo cancelaste, `confirmado` si pasó).
6. Entra a `/admin` con tu cuenta y sube una foto de producto: tiene que quedar en `public/galy_api/img` y verse desde el dominio.
7. Revisa `storage/logs/laravel.log` (o `laravel-*.log`, porque `LOG_CHANNEL=daily`): tiene que estar vacío de `ERROR`.

## 11. Lo que sigue quedando de tu lado

Nada de esto es código, son datos:

- `ajustes.direccion` dice "Av. Siempre Viva 123" y `telefono` "0998765432".
- `color_primario` está vacío y `APP_NAME` ahora es `GALY` (por el `.env.production`).
- Los "QR" de Yape y Plin **son la misma imagen** (900×1600, mismo hash): sube dos QR reales desde el panel de Ajustes.
- `puntos_recojo` tiene 1 fila y su `provincia_id` está vacía, así que el carrito no muestra agencias.
- La app Android sigue cobrando por su propio camino (`galy_api`), que no se movió en este despliegue.
- `GALY_API_URL` y `GALY_PUBLIC_URL` siguen apuntando a `localhost` porque `galy_api` y `galy-admin` son otras apps de esta PC, no parte de la tienda. Dos botones del panel dependen de ellos y van a fallar con "No se pudo conectar con el panel GALY": el enlace de acceso al panel antiguo y el cambio de estado de un ticket de soporte. Las fotos de la tienda **no** dependen de esas dos variables.
