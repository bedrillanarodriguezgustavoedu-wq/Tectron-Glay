# Tienda en línea

Aplicación de comercio electrónico desarrollada con Laravel. Incluye catálogo de productos, carrito, pedidos, administración y pagos manuales con Yape y Plin.

## Requisitos

- PHP 8.3 o superior y Composer.
- Node.js y npm.
- SQLite para el entorno local (u otro motor configurado en `.env`).

En Windows, comprueba que la terminal esté usando PHP 8.3 con:

```powershell
# Si XAMPP aparece primero en el PATH, prioriza PHP 8.3 en esta terminal:
$php83Dir = Join-Path $env:LOCALAPPDATA 'Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe'
$env:Path = "$php83Dir;$env:Path"
php -v
where.exe php
```

El cambio anterior dura mientras esa terminal esté abierta. Si tienes permisos de administrador, puedes agregar PHP 8.3 al inicio del `PATH` del sistema para que sea la versión predeterminada en terminales nuevas. Agregarlo solo al `PATH` de usuario no tiene prioridad sobre PHP instalado en el `PATH` del sistema.

## Instalación en Windows

Desde la carpeta del proyecto, ejecuta:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
if (-not (Test-Path database/database.sqlite)) { New-Item -ItemType File database/database.sqlite }
php artisan migrate
npm install
npm run build
```

Si ya existe un archivo `.env`, consérvalo y no ejecutes `Copy-Item` encima. Configura en ese archivo la conexión de base de datos y los datos de correo que correspondan a tu entorno. Configura los números, titulares y códigos QR receptores de Yape y Plin desde los ajustes del administrador. No compartas ni subas el archivo `.env`.

Los pedidos pagados con Yape o Plin quedan pendientes hasta que el cliente envíe el comprobante y un administrador lo revise y confirme en el registro de pagos.

Para iniciar el servidor local:

```powershell
composer run dev
```

## Conexión compartida con GALY

La web Tectron, la API de GALY y la app Android comparten ahora la base MySQL `ecommerce`. En el `.env` de Tectron, configura esa base como conexión principal y como conexión GALY (no publiques ese archivo):

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecommerce
DB_USERNAME=...
DB_PASSWORD=...

GALY_DB_CONNECTION=mysql
GALY_DB_DATABASE=ecommerce
GALY_API_URL=http://localhost/galy_api
GALY_PUBLIC_URL=http://localhost/galy-admin
GALY_IMAGES_PATH=C:/xampp/htdocs/galy_api/img
```

Las cuentas web (`users`) y las cuentas de la app (`usuarios`) conservan sus tablas y propósitos diferentes dentro de `ecommerce`; la autenticación GALY usa `usuarios` y vincula la sesión web por `galy_user_id`. El modo «Conectar mi cuenta GALY» valida el correo y la contraseña en la API GALY. Las cuentas nuevas creadas desde la web también se registran en la misma base de datos para la app. El panel solo ofrece funciones administrativas compartidas cuando GALY confirma esos permisos.

`DB_DATABASE` y `GALY_DB_DATABASE` deben ser `ecommerce`. La base antigua `galy` se conserva como respaldo y no es la activa. El panel de Tectron inicia sesión contra la API GALY. El enlace «Administrador App GALY» aparece para una cuenta que GALY reconoce como administradora y usa un enlace temporal. Los pedidos hechos desde la web o la app, y sus cambios de inventario, se guardan en `ecommerce`.

Las tablas GALY activas ahora usan sus nombres normales en `ecommerce`; puedes consultar `ecommerce.pedidos` y `ecommerce.pedido_productos` en phpMyAdmin. Para preservar los datos anteriores de la tienda, las tablas con nombres coincidentes se archivaron con prefijo `tectron_legacy_`, y las copias GALY previas se conservaron con prefijo `galy_previous_20261013_`. La base original `galy` no se borró ni se modificó y queda como respaldo; los cambios posteriores se guardan en `ecommerce`.

Los banners de la web y de la pantalla de inicio de GALY se administran en una sola tabla: `ecommerce.banners`. La migración copia los banners existentes de `banners_inicio` y conserva la tabla anterior como `banners_inicio_legacy`; no elimina esos datos. Las imágenes nuevas se guardan en `C:/xampp/htdocs/galy_api/img` para que la app y la web utilicen la misma imagen.

Cuando una cuenta ya vinculada a GALY inicia sesión en la web, Tectron valida las credenciales compartidas con GALY y conserva el token administrativo para el acceso compartido. Si GALY no está disponible, se mantiene el acceso local existente para cuentas web, pero no se puede conectar una cuenta nueva ni registrar otra compartida. Los cambios de contraseña y los restablecimientos de cuentas vinculadas se guardan también en GALY para que la misma contraseña siga funcionando en la app.

El panel de administración web identifica la conexión con la App GALY y ofrece el acceso al panel compartido. Con un token administrativo GALY vigente, el acceso genera un enlace temporal de un solo uso; sin token, abre el inicio de sesión del panel GALY para autenticarse allí. El panel resalta también sus funciones para la app y ajusta el contraste de la tarjeta al tema oscuro.

La pantalla `/admin/productos` usa el catálogo compartido en `ecommerce`, por lo que los cambios de la web y la app Android afectan los mismos productos. El enlace «Administrador App GALY» abre el panel GALY con el inicio de sesión administrativo temporal. No restaures sobre `ecommerce` un volcado antiguo de `galy`: las tablas anteriores de la tienda siguen preservadas con prefijo `tectron_legacy_`.

La app Android y la web leen y administran el mismo catálogo activo en `ecommerce.productos`. Las copias históricas con prefijo `galy_previous_20261013_` son archivos de respaldo, no catálogos en uso; una migración de restauración idempotente incorpora a la tabla compartida los productos que falten en ella sin sobrescribir los existentes.

El carrito también es compartido: tanto la web como GALY guardan las cantidades por usuario y producto en `ecommerce.carrito`. La antigua tabla Laravel `carritos` se conserva como `carritos_legacy_20261013`; estaba vacía al archivarse y no es el carrito activo. La API Android usa `get_cart.php`, `add_to_cart.php`, `update_cart_quantity.php`, `delete_from_cart.php` y `clear_cart.php`; las operaciones validan sesión y stock. Una restricción única por usuario/producto evita líneas duplicadas y combina de forma aditiva las cantidades que ya estuvieran repetidas.

Las campañas de descuentos por producto también se administran desde un solo catálogo compartido: `ecommerce.ofertas` y `ecommerce.oferta_producto`. El panel web `/admin/ofertas` y la administración de ofertas de GALY crean, editan y eliminan las mismas campañas; los precios vigentes se recalculan para la web y la app. Los cupones por código siguen siendo promociones separadas.

Las marcas también usan un catálogo compartido en `ecommerce.marcas`. La migración `2026_10_13_130000_link_shared_product_brands.php` agrega las marcas que faltaban a partir del texto existente en `productos.marca` y completa `productos.marca_id` sin cambiar los nombres guardados ni las relaciones ya existentes. La web y GALY pueden crear, editar y activar/desactivar marcas; las marcas desactivadas o relacionadas con productos se conservan y no se borran físicamente. Al registrar o editar un producto desde GALY, su marca debe existir y estar activa en ese catálogo para quedar vinculada.

Las categorías usan también el catálogo compartido `ecommerce.categorias`. La web y la pantalla «Categorías compartidas» de GALY pueden crear y renombrar categorías; el nombre visible en los productos vinculados se actualiza junto con el nombre de la categoría. La administración muestra cuántos productos están asociados y bloquea la eliminación mientras haya productos. GALY valida la categoría al crear o editar un producto y rechaza categorías inexistentes, evitando productos sin vínculo.

La información pública de contacto se administra desde el único registro de `ecommerce.contactos`: teléfono, WhatsApp, correo, dirección, horario, mapa, redes y mensaje de bienvenida se pueden consultar y actualizar desde la web y GALY. La app y el pie de página web leen esos mismos datos; la configuración del pie de página conserva solo descripción, logotipo, enlaces legales y de aplicaciones, además de redes adicionales que no existen en el perfil compartido. El banner de contacto se sigue subiendo desde la web y no se modifica desde GALY. La página pública de contacto puede seguir desactivándose desde su configuración web.

La sección «Sobre Nosotros» usa el registro compartido de `ecommerce.nosotros`. El panel web y el administrador de GALY pueden editar textos, cifras, video y estado público; la imagen principal se conserva y se administra desde la web. La app muestra la información pública habilitada en «Sobre GALY».

Los tickets de soporte técnico usan `ecommerce.servicios_tecnicos`, que ya comparte GALY y su administrador web. El panel Laravel permite consultar la bandeja, cambiar estado y costo, y usa el endpoint de GALY para que el cambio se refleje en la app y dispare la notificación existente al cliente. Si Laravel y la API no están en la misma máquina, configura `GALY_API_KEY` con la misma llave `X-GALY-KEY` de la API.

Antes de desplegar, haz respaldos verificados de `ecommerce` y conserva el respaldo antiguo de `galy`. Configura `GALY_API_URL`, `GALY_PUBLIC_URL`, `GALY_IMAGES_PATH` y `GALY_DB_NAME=ecommerce` en el servidor PHP. El cambio de tablas de esta instalación ya fue aplicado y verificado; no vuelvas a ejecutar el importador `galy:import-tectron --apply` sobre la base activa porque podría duplicar datos.

```powershell
php artisan migrate
```
No ejecutes migraciones destructivas ni importes otra vez el volcado de GALY sobre la base activa. Para pruebas usa `APP_ENV=testing`, que mantiene conexiones SQLite aisladas.

## Pruebas

Las pruebas usan SQLite en memoria y no requieren una base de datos local persistente:

```powershell
php artisan test
```

## Compilación de recursos

```powershell
npm run build
```
