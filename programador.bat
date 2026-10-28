@echo off
rem Una pasada del programador de la tienda. La llama el Programador de tareas de
rem Windows cada minuto. Sin esto hace falta dejar «php artisan schedule:work» con
rem la ventana abierta: al cerrarla, mercadopago:reconciliar deja de confirmar
rem pagos con tarjeta y reservas:expirar deja de liberar stock reservado.
rem
rem Se prueba a mano con:  programador.bat
setlocal
cd /d "E:\ecommerce (4)\ecommerce"
set "PHP=C:\Users\USUARIO\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe"

rem El registro de Windows se reescribe entero cada minuto: se corta solo para no
rem llenar el disco. Lo que importa queda en storage\logs\laravel-*.log.
for %%F in ("storage\logs\programador.log") do if %%~zF GTR 1048576 del /q "storage\logs\programador.log"

"%PHP%" artisan schedule:run >> "storage\logs\programador.log" 2>&1
endlocal
