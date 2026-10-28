@echo off
rem Levanta UN solo tunel Cloudflare hacia la tienda y escribe su direccion
rem nueva en el .env. Sin esto, el retorno de Mercado Pago se queda apuntando
rem a un hostname que ya murio y el cliente ve "No se puede acceder".
setlocal
set "PROYECTO=E:\ecommerce (4)\ecommerce"
set "LOG=%TEMP%\tunel-galy.log"

echo [1/4] Cerrando los cloudflared que quedaron abiertos...
taskkill /F /IM cloudflared.exe >nul 2>&1
del "%LOG%" >nul 2>&1

where cloudflared >nul 2>&1
if errorlevel 1 (
    echo No encuentro cloudflared en el PATH. Instalalo o ajusta esta linea.
    exit /b 1
)

echo [2/4] Abriendo el tunel hacia http://127.0.0.1:8000 ...
start "Tunel GALY" /MIN cmd /c "cloudflared tunnel --url http://127.0.0.1:8000 1>"%LOG%" 2>&1"

echo [3/4] Esperando la direccion publica, tarda unos segundos...
cd /d "%PROYECTO%"
php artisan tunel:fijar --log="%LOG%"
if errorlevel 1 (
    echo El tunel no llego a escribir su direccion. Mira la ventana "Tunel GALY".
    exit /b 1
)

echo [4/4] Listo.
echo.
echo La tienda esta visible en internet mientras siga abierta la ventana "Tunel GALY".
echo Si «php artisan serve» estaba abierto antes, cierralo y vuelvelo a lanzar:
echo ese proceso sigue usando la direccion que cargo al arrancar.
endlocal
