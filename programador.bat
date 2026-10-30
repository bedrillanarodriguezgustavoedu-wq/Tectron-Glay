@echo off
rem Una pasada del programador de la tienda. La llama el Programador de tareas de
rem Windows cada minuto. Sin esto hace falta dejar «php artisan schedule:work» con
rem la ventana abierta: al cerrarla, mercadopago:reconciliar deja de confirmar
rem pagos con tarjeta y reservas:expirar deja de liberar stock reservado.
rem
rem Se prueba a mano con:  programador.bat
rem
rem La tarea «Galy Tienda - programador» no llama a este archivo directamente: llama
rem a programador-min.lnk, un acceso directo con «Ejecutar: Minimizada» que evita que
rem la consola parpadee cada minuto. Si se pierde el acceso directo se recrea con:
rem   powershell -Command "$s=(New-Object -ComObject WScript.Shell).CreateShortcut('E:\ecommerce (4)\ecommerce\programador-min.lnk'); $s.TargetPath='E:\ecommerce (4)\ecommerce\programador.bat'; $s.WorkingDirectory='E:\ecommerce (4)\ecommerce'; $s.WindowStyle=7; $s.Save()"
setlocal
cd /d "E:\ecommerce (4)\ecommerce"
set "PHP=C:\Users\USUARIO\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe"

rem El registro de Windows se reescribe entero cada minuto: se corta solo para no
rem llenar el disco. Lo que importa queda en storage\logs\laravel-*.log.
for %%F in ("storage\logs\programador.log") do if %%~zF GTR 1048576 del /q "storage\logs\programador.log"

"%PHP%" artisan schedule:run >> "storage\logs\programador.log" 2>&1
endlocal
