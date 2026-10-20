<?php

namespace App\Console\Commands;

use App\Models\Ajuste;
use App\Models\IntentoPagoMercadoPago;
use App\Services\MercadoPagoService;
use Illuminate\Console\Command;

class VerifyMercadoPagoSettings extends Command
{
    protected $signature = 'mercadopago:verificar {--probar : Consulta a Mercado Pago si el token es válido}';

    protected $description = 'Revisa qué falta para que el pago con tarjeta esté disponible en la tienda.';

    public function handle(MercadoPagoService $mercadoPago): int
    {
        $ajuste = Ajuste::first();
        $token = (string) $mercadoPago->accessToken();
        $urlBase = $mercadoPago->urlBase();

        $filas = [
            ['MERCADOPAGO_ENABLED', config('services.mercadopago.enabled') === true ? 'sí' : 'NO',
                'MERCADOPAGO_ENABLED=true en .env'],
            ['MERCADOPAGO_ACCESS_TOKEN', $token === '' ? 'NO' : ($mercadoPago->tokenVerosimil() ? $this->describirToken($token, $mercadoPago) : 'NO (no tiene forma de token)'),
                'Pega el Access Token completo de tu cuenta (empieza con TEST- o APP_USR-)'],
            ['MERCADOPAGO_WEBHOOK_SECRET', filled(config('services.mercadopago.webhook_secret')) ? 'sí' : 'no',
                'Copia el secreto firmante del webhook registrado en Mercado Pago'],
            ['URL pública de pagos', $urlBase, $this->problemaDeUrl($urlBase)],
            ['Interruptor en Admin → Ajustes', $ajuste?->web_mercadopago_activa ? 'activado' : 'desactivado',
                'Marca "Ofrecer pago con tarjeta" en Admin → Ajustes'],
            ['Credencial ya verificada', $mercadoPago->credencialVerificada()
                ? 'sí, '.($ajuste?->web_mercadopago_verificado_at ?? '')
                : 'NO (nadie probó este token contra Mercado Pago)',
                'Corre php artisan mercadopago:verificar --probar; sin esa prueba la tarjeta no se ofrece'],
        ];

        $this->table(['Qué se revisa', 'Estado', 'Cómo se arregla'], $filas);

        $ofrecida = $mercadoPago->sePuedeOfrecer() && (bool) $ajuste?->web_mercadopago_activa;

        if ($ofrecida) {
            $this->info('El pago con tarjeta SÍ se muestra en el checkout ('
                .($mercadoPago->esTokenDePrueba() ? 'modo pruebas' : 'modo producción').').');
        } elseif ($mercadoPago->credencialDePrueba()) {
            // Ninguna fila de la tabla está en NO: la credencial vale, es de
            // prueba. Decirle "arregla las filas marcadas con NO" lo deja
            // buscando una fila que no existe.
            $this->warn('El pago con tarjeta NO se muestra: la credencial puesta es de prueba y un cliente real no puede pagar con ella.');
        } else {
            $this->info('El pago con tarjeta NO se muestra todavía: arregla las filas marcadas con NO.');
        }

        if ($mercadoPago->habilitado() && $mercadoPago->rechazoReciente()) {
            $this->warn('La opción está retirada porque Mercado Pago rechazó la credencial en un intento reciente.');
            $this->line($mercadoPago->bloqueoDefinitivo()
                ? 'Ya fallaron varios tokens distintos, así que no va a volver sola.'
                : 'Vuelve sola media hora después de dejar de fallar.');
            $this->line('Correr esto otra vez con el mismo token no la reabre: primero cambia el Access Token en el .env.');
        }

        if ($ofrecida && ! filled(config('services.mercadopago.webhook_secret')) && ! app()->environment('local', 'testing')) {
            $this->warn('Sin MERCADOPAGO_WEBHOOK_SECRET la tienda acepta webhooks sin verificar firma. Ponlo antes de cobrar de verdad.');
        }

        $this->avisarDePagosAbiertos();

        if ($this->option('probar')) {
            if ($token === '') {
                $this->error('No hay token que probar.');

                return self::FAILURE;
            }

            $prueba = $mercadoPago->titularCuenta();

            if (! $prueba['ok']) {
                if (in_array($prueba['codigo'], [401, 403], true)) {
                    $mercadoPago->bloquearPorRechazo();
                }

                $this->error('Mercado Pago no aceptó el token: '.$prueba['detalle']);
                $this->line('Repetir la prueba con este mismo token da exactamente este resultado. Lo que falta es el Access Token de tu cuenta.');

                return self::FAILURE;
            }

            $mercadoPago->registrarVerificacion($prueba['es_prueba']);
            $mercadoPago->levantarBloqueo();
            $this->info('El token es válido y pertenece a '.$prueba['detalle']
                .($prueba['es_prueba'] ? ' — es una cuenta de PRUEBA' : ''));

            // La tabla de arriba se imprimió ANTES de esta prueba, así que describe
            // el estado de entrada. Sin este resumen el dueño se queda con un «NO»
            // en pantalla justo después de haber conseguido un sí, y corre el mismo
            // comando otra vez.
            if ($prueba['es_prueba']) {
                $this->warn('Es un token de la pestaña Prueba: sirve para que tú lo veas funcionar, pero un cliente real no puede pagar con él.');
                $this->line('La tarjeta sigue sin ofrecerse. Para cobrar de verdad, copia el Access Token de la pestaña Productivas y corre este script otra vez.');
            } elseif (! $mercadoPago->sePuedeOfrecer()) {
                $this->warn('La credencial vale, pero la opción sigue apagada por otra razón: mira la tabla de arriba.');
            } elseif (! (bool) $ajuste?->web_mercadopago_activa) {
                $this->line('Falta un clic: marca «Ofrecer pago con tarjeta» en Admin → Ajustes y guarda.');
            } else {
                $this->info('Ya está: la tarjeta Visa se ofrece en el carrito.');
            }

            return self::SUCCESS;
        }

        return self::SUCCESS;
    }

    /**
     * Lo que quedó del lado de Mercado Pago: cobros aprobados que no se pudieron
     * aplicar, cobros revertidos e intentos sin pago identificado.
     */
    private function avisarDePagosAbiertos(): void
    {
        $sinAplicar = IntentoPagoMercadoPago::query()
            ->where('estado_mercadopago', 'approved')
            ->where('estado', '!=', IntentoPagoMercadoPago::ESTADO_CONFIRMADO)
            ->count();

        $revertidos = IntentoPagoMercadoPago::query()
            ->whereIn('estado_mercadopago', IntentoPagoMercadoPago::ESTADOS_DE_REVERSION)
            ->count();

        $sinCobro = IntentoPagoMercadoPago::query()
            ->whereIn('estado', [IntentoPagoMercadoPago::ESTADO_ESPERANDO, IntentoPagoMercadoPago::ESTADO_EXPIRADO])
            ->whereNull('mercadopago_payment_id')
            ->count();

        if ($sinAplicar > 0 || $revertidos > 0) {
            $this->warn('Admin → Pagos tiene '.$sinAplicar.' cobro(s) aprobado(s) sin aplicar y '
                .$revertidos.' cobro(s) revertido(s). Revísalos antes de enviar el pedido o devolver el dinero.');
        }

        $this->line('Intentos de tarjeta sin cobro identificado: '.$sinCobro
            .'. El scheduler ejecuta mercadopago:reconciliar cada 10 minutos; si el scheduler no está activo, corre el comando a mano.');
    }

    private function describirToken(string $token, MercadoPagoService $mercadoPago): string
    {
        if ($mercadoPago->credencialDePrueba()) {
            return 'de pruebas (Mercado Pago respondió con una cuenta de prueba), '.strlen($token).' caracteres';
        }

        if (str_starts_with($token, 'TEST-')) {
            return 'de pruebas (TEST-), '.strlen($token).' caracteres';
        }

        if (str_starts_with($token, 'APP_USR-')) {
            return 'de producción (APP_USR-), '.strlen($token).' caracteres';
        }

        return 'no reconocido ('.substr($token, 0, 8).'…), '.strlen($token).' caracteres';
    }

    private function problemaDeUrl(string $url): string
    {
        if (! str_starts_with($url, 'https://')) {
            return 'Debe ser https pública; Mercado Pago no devuelve al cliente ni avisa al servidor.';
        }

        if (preg_match('#^https://(localhost|127\.|\[::1\])#', $url)) {
            return 'Es una URL local; Mercado Pago no podrá devolver al cliente ni notificar al servidor.';
        }

        if (str_contains($url, 'trycloudflare.com') || str_contains($url, 'ngrok')) {
            return 'Es un túnel temporal; si cambia o se apaga, el cliente queda fuera de Mercado Pago. Deja una URL fija en MERCADOPAGO_URL_BASE.';
        }

        return $url === rtrim((string) config('app.url'), '/')
            ? 'Se usa APP_URL; puedes poner otra en MERCADOPAGO_URL_BASE (por ejemplo un túnel).'
            : 'Se usa MERCADOPAGO_URL_BASE.';
    }
}
