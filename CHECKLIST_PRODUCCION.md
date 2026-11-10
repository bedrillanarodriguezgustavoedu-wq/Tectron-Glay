# Checklist de producción — Tienda GALY

Última actualización: 2026-11-10

## 1. Código (hecho)
- [x] 500 del pago (timeout/reintento) corregido
- [x] Mensajes de error por causa real (red / tarjeta / pasarela)
- [x] Identificación, teléfono y dirección enviados a MP (aprobación)
- [x] Túnel activo y URL guardada en `.env`
- [x] Marcas de tarjeta dinámicas desde la API de MP
- [x] Productos agotados / bajo stock con reposición rápida
- [x] Página "Pago seguro" + enlaces
- [x] Suite: 361 pruebas / 1867 aserciones en verde
- [x] Commit `30d18fc` realizado (working tree limpio)

## 2. Mercado Pago (pendiente — requiere panel de MP)
- [ ] Registrar webhook `https://TUDOMINIO.pe/webhooks/mercadopago` (activar "Pago")
- [ ] Copiar el secreto firmante a `MERCADOPAGO_WEBHOOK_SECRET`
- [ ] Poner el dominio real en "Sitio web" de las credenciales de producción
- [ ] Pegar el Access Token de producción en `.env` del servidor
- [ ] Ejecutar `php artisan mercadopago:verificar --probar` (debe decir "de producción")
- [ ] (Opcional) Habilitar más marcas de tarjeta en MP → aparecen solas en la tienda

## 3. Servidor (pendiente — requiere acceso al hosting)
- [ ] `APP_URL=https://TUDOMINIO.pe`
- [ ] `MERCADOPAGO_URL_BASE=https://TUDOMINIO.pe`
- [ ] `.env.production` completo: `APP_KEY`, `DB_USERNAME`, `DB_PASSWORD`, `MAIL_*`, `MERCADOPAGO_ACCESS_TOKEN`, `MERCADOPAGO_WEBHOOK_SECRET`, `MERCADOPAGO_URL_BASE`
- [ ] Cron cada minuto: `* * * * * php artisan schedule:run`
- [ ] SMTP real (no Mailtrap) en `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`
- [ ] `QUEUE_CONNECTION=database` y worker de colas si se usa

## 4. Validación final (pendiente)
- [ ] Cobro real de prueba con tarjeta (monto mínimo)
- [ ] Verificar intento → `confirmado`, pedido → `Pagado`, webhook recibido
- [ ] Correo de confirmación recibido por el cliente
- [ ] Probar Yape/Plin con QR
- [ ] Probar flujo móvil (390 px) sin desbordes

## 5. Limpieza (hecho)
- [x] Eliminados: `public/__audit.html`, `mercadopago-brick.ps1`, `programador-*`, `respaldos/`, `hot.off`, `OfertaPriceRefreshTest.php.vacio-y-no-usar`
- [ ] (Opcional) Limpiar pedidos de prueba y restaurar stock si se usaron datos reales

## 6. Opcional
- [ ] Login con Google: autorizar `https://TUDOMINIO.pe/auth/google/callback`
- [ ] Enlazar página "Pago seguro" en el menú principal
- [ ] Configurar `color_primario` y datos de tienda (dirección, teléfono, Yape/Plin)
