@echo off
rem Ejecuta las tareas programadas de la tienda (reservas, conciliacion y caducidad
rem de pagos con tarjeta). Lo llama el Programador de tareas de Windows cada minuto;
rem si ademas tienes abierta una consola con `php artisan schedule:work`, cierrala:
rem las dos cosas a la vez ejecutan cada tarea dos veces por minuto.
cd /d "E:\ecommerce (4)\ecommerce"
php artisan schedule:run >> "storage\logs\planificador.log" 2>&1
