<?php

namespace App\Services;

use App\Models\Ajuste;
use App\Models\IntentoPagoMercadoPago;
use App\Models\Orden;
use App\Models\PedidoHistorial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class MercadoPagoService
{
    /**
     * Estados de pago que Mercado Pago considera cobros que no llegaron a buen
     * puerto. Solo `approved` puede marcar un pedido como pagado.
     */
    private const ESTADOS_FALLIDOS = ['rejected', 'cancelled', 'charged_back', 'refunded'];

    private const CLAVE_RECHAZO = 'mercadopago.rechazo-reciente';

    private const CLAVE_RECHAZOS = 'mercadopago.rechazos-seguidos';

    private const CLAVE_RECHAZO_HUELLA = 'mercadopago.rechazo-huella';

    private const RECHAZOS_PARA_OCULTAR = 3;

    public function habilitado(): bool
    {
        return config('services.mercadopago.enabled') === true && $this->tokenVerosimil();
    }

    /**
     * Si se le puede *ofrecer* la tarjeta a un cliente nuevo. Pide la credencial,
     * que alguien la haya probado contra Mercado Pago con este mismo token, y que
     * un rechazo reciente no la haya retirado.
     *
     * La verificación vive en la base, no en la caché, a propósito: `cache:clear`
     * borraba el candado y devolvía a la vitrina una tarjeta que da 403.
     *
     * A propósito NO se mete nada de esto dentro de `habilitado()`: ese también
     * manda sobre el reconciliador y el webhook, y un 403 al abrir un checkout
     * nuevo no puede cancelar el cobro aprobado de un cliente que ya pagó.
     *
     * Una credencial de prueba tampoco se ofrece: el cliente llegaría al checkout
     * de Mercado Pago y no podría pagarte, lo cual parece una tienda averiada.
     */
    public function sePuedeOfrecer(): bool
    {
        return $this->habilitado()
            && $this->credencialVerificada()
            && ! $this->credencialDePrueba()
            && ! $this->rechazoReciente();
    }

    /**
     * Que `mercadopago:verificar --probar` haya confirmado este token alguna vez.
     * Se guarda la huella del token, no el token: si mañana cambia el `.env`, la
     * verificación anterior deja de contar sola, sin tener que avisar a nadie.
     */
    public function credencialVerificada(): bool
    {
        $ajuste = Ajuste::first();

        if (! $ajuste?->web_mercadopago_token_hash) {
            return false;
        }

        return hash_equals((string) $ajuste->web_mercadopago_token_hash, $this->huellaDeTokenActual());
    }

    public function verificadaEn(): ?string
    {
        return Ajuste::first()?->web_mercadopago_verificado_at;
    }

    public function huellaDeTokenActual(): string
    {
        return hash('sha256', trim((string) $this->accessToken()));
    }

    public function registrarVerificacion(bool $esPrueba = false): void
    {
        $ajuste = Ajuste::first();

        if (! $ajuste) {
            return;
        }

        $ajuste->web_mercadopago_token_hash = $this->huellaDeTokenActual();
        $ajuste->web_mercadopago_verificado_at = now();
        $ajuste->web_mercadopago_es_prueba = $esPrueba;
        $ajuste->save();
    }

    /**
     * Un 401 o un 403 no es un fallo de red: es esta credencial. Hasta que
     * `--probar` no la vuelva a aceptar, la tarjeta no se ofrece.
     */
    public function invalidarVerificacion(): void
    {
        $ajuste = Ajuste::first();

        if (! $ajuste) {
            return;
        }

        $ajuste->web_mercadopago_token_hash = null;
        $ajuste->web_mercadopago_verificado_at = null;
        $ajuste->save();
    }

    /**
     * Un Access Token que Mercado Pago rechaza con 403 no se arregla solo: cada
     * cliente que lo intente paga y falla, y el pedido queda pendiente. La
     * opción se retira de la vitrina media hora, o hasta que
     * `mercadopago:verificar --probar` confirme la credencial.
     */
    public function bloquearPorRechazo(): void
    {
        // Si la credencial estaba verificada, la tarjeta estaba en la vitrina y
        // hay que retirarla. Si no, ya estaba apagada: ponerle «en pausa» sería
        // describir una avería que no existe, y el dueño lo lee cada vez que
        // repite el diagnóstico con el mismo token roto.
        $estabaOfrecida = $this->credencialVerificada();

        $this->invalidarVerificacion();

        // Repetir la prueba con el MISMO token no aporta información: es el
        // dueño dando dos veces al mismo comando, no una credencial que tropieza
        // en cada cliente. Solo se cuenta el rechazo de un token distinto, que
        // es la señal real de que alguien está probando credenciales a ciegas.
        $huella = $this->huellaDeTokenActual();
        $rechazos = (int) Cache::get(self::CLAVE_RECHAZOS, 0);
        $mismoToken = Cache::get(self::CLAVE_RECHAZO_HUELLA) === $huella;

        if (! $mismoToken) {
            $rechazos++;
            Cache::forever(self::CLAVE_RECHAZOS, $rechazos);
            Cache::forever(self::CLAVE_RECHAZO_HUELLA, $huella);
        }

        // Sin caducidad del contador: "seguidos" significa seguidos, no "dentro de
        // la misma tarde". Lo borra `levantarBloqueo()` cuando un token válido se impone.

        if (! $estabaOfrecida) {
            // No hay nada que retirar, y tampoco hay que borrar una pausa que
            // puso un rechazo anterior: esa sigue contando su media hora.
            return;
        }

        // Al tercer token distinto rechazado la credencial ya no tropezó: está mal.
        // Reaparecer cada media hora solo fabrica clientes que pagan y fallan, así
        // que a partir de ahí la opción no vuelve hasta que `--probar` la valide.
        Cache::put(
            self::CLAVE_RECHAZO,
            now()->toDateTimeString(),
            $rechazos >= self::RECHAZOS_PARA_OCULTAR ? null : now()->addMinutes(30)
        );
    }

    public function levantarBloqueo(): void
    {
        Cache::forget(self::CLAVE_RECHAZO);
        Cache::forget(self::CLAVE_RECHAZOS);
        Cache::forget(self::CLAVE_RECHAZO_HUELLA);
    }

    public function rechazoReciente(): bool
    {
        return Cache::has(self::CLAVE_RECHAZO);
    }

    /**
     * Si el ocultamiento es ya indefinido: avisa al panel y al médico para que no
     * prometan una espera de media hora que no va a ocurrir.
     */
    public function bloqueoDefinitivo(): bool
    {
        return (int) Cache::get(self::CLAVE_RECHAZOS, 0) >= self::RECHAZOS_PARA_OCULTAR;
    }

    /**
     * Un valor de relleno (`APP_USR-xxxxxxxx`) basta para que la tienda ofrezca
     * la tarjeta, redirija al cliente a Mercado Pago y falle allí a mitad de
     * pago. Se exige la forma real de un Access Token, no que la variable
     * simplemente esté escrita.
     */
    public function tokenVerosimil(): bool
    {
        $token = trim((string) $this->accessToken());

        // El cuarto tramo numérico es opcional: las credenciales nuevas de
        // Checkout Pro lo traen, y rechazarlas por forma dejaba fuera un token
        // auténtico. La Public Key sigue fuera porque su primer tramo lleva
        // letras (`APP_USR-8f4a…`) y aquí se exigen seis dígitos.
        return preg_match('/^(TEST|APP_USR)-\d{6,}-\d{4,}-[A-Za-z0-9]{12,}(-\d{4,})?$/', $token) === 1;
    }

    public function accessToken(): ?string
    {
        return config('services.mercadopago.access_token');
    }

    /**
     * Las credenciales que Mercado Pago genera hoy pueden empezar con `APP_USR-`
     * y seguir siendo de prueba. Si solo se mira el prefijo, una de esas manda al
     * cliente al checkout de producción con una credencial que no cobra.
     */
    public function esTokenDePrueba(): bool
    {
        $token = (string) $this->accessToken();

        return str_starts_with($token, 'TEST-')
            || str_contains($token, '-TEST-')
            || str_ends_with($token, '-TEST')
            || $this->credencialDePrueba();
    }

    /**
     * Si la credencial que está en el `.env` es de la pestaña «Prueba» del panel.
     * No lo dice el prefijo —una de prueba puede empezar con `APP_USR-`— sino la
     * cuenta con la que Mercado Pago respondió al probarla. Solo cuenta mientras
     * la huella guardada siga siendo la del token actual.
     */
    public function credencialDePrueba(): bool
    {
        return $this->credencialVerificada()
            && (bool) Ajuste::first()?->web_mercadopago_es_prueba;
    }

    /**
     * Crea una preferencia de Checkout Pro y devuelve su identificador con la URL
     * a la que hay que redirigir al cliente.
     *
     * @return array{preferencia_id: string, checkout_url: string}
     */
    public function crearCheckout(
        string $referencia,
        float $monto,
        string $divisa,
        string $titulo,
        string $descripcion,
        ?array $payer = null
    ): array {
        $retorno = $this->urlBase().'/pago/tarjeta/retorno?referencia='.rawurlencode($referencia).'&estado=';

        $respuesta = $this->request()->post($this->apiUrl().'/checkout/preferences', [
            'items' => [[
                'title' => $titulo,
                'description' => $descripcion,
                'quantity' => 1,
                'currency_id' => $divisa,
                'unit_price' => round($monto, 2),
            ]],
            'payer' => $payer ?: ['email' => 'cliente@galy.pe'],
            'external_reference' => $referencia,
            'back_urls' => [
                'success' => $retorno.'success',
                'pending' => $retorno.'pending',
                'failure' => $retorno.'failure',
            ],
            'auto_return' => 'approved',
            'notification_url' => $this->urlBase().'/webhooks/mercadopago',
            'statement_descriptor' => config('services.mercadopago.statement_descriptor'),
        ]);

        if ($respuesta->failed()) {
            // 401 y 403 son problemas de credencial, no fallos pasajeros de la
            // red: repetir el intento no los arregla, así que se retira la
            // opción. Un 400 (payload nuestro) o un 5xx de Mercado Pago no la
            // apagan, porque el próximo cliente sí podría pasar.
            if (in_array($respuesta->status(), [401, 403], true)) {
                $this->bloquearPorRechazo();
            }

            throw new RuntimeException(
                'Mercado Pago rechazó la creación del checkout (HTTP '.$respuesta->status().').'
            );
        }

        $preferencia = $respuesta->json() ?? [];
        $url = $this->esTokenDePrueba()
            ? ($preferencia['sandbox_init_point'] ?? $preferencia['init_point'] ?? null)
            : ($preferencia['init_point'] ?? null);

        if (blank($preferencia['id'] ?? null) || blank($url)) {
            throw new RuntimeException('Mercado Pago devolvió un checkout incompleto.');
        }

        return ['preferencia_id' => (string) $preferencia['id'], 'checkout_url' => (string) $url];
    }

    /**
     * Cuenta titular del Access Token, con el motivo cuando falla. Decir solo
     * «token rechazado» dejaba al dueño sin saber si el token estaba muerto o
     * si el servidor no llegaba a la API: dos arreglos distintos.
     *
     * @return array{ok: bool, detalle: string, codigo: int|null, es_prueba: bool}
     */
    public function titularCuenta(): array
    {
        try {
            $respuesta = $this->request()->get($this->apiUrl().'/users/me');
        } catch (Throwable $e) {
            return ['ok' => false, 'detalle' => 'no se pudo hablar con la API: '.$e->getMessage(), 'codigo' => null, 'es_prueba' => false];
        }

        if (! $respuesta->successful()) {
            $mensaje = $respuesta->json('message');

            return [
                'ok' => false,
                'codigo' => $respuesta->status(),
                'detalle' => 'HTTP '.$respuesta->status().' — '.(is_string($mensaje) && $mensaje !== '' ? $mensaje : 'sin mensaje'),
                'es_prueba' => false,
            ];
        }

        $cuenta = $respuesta->json() ?? [];
        $correo = (string) ($cuenta['email'] ?? '');

        return [
            'ok' => true,
            'codigo' => 200,
            'detalle' => $correo !== '' ? $correo : 'ID '.($cuenta['id'] ?? '?'),
            'es_prueba' => $this->esCuentaDePrueba($correo),
        ];
    }

    /**
     * Las cuentas que Mercado Pago crea para probar tienen su propio dominio de
     * correo. Es la única señal fiable que hay cuando el token empieza con
     * `APP_USR-` y salió de la pestaña Prueba.
     */
    private function esCuentaDePrueba(string $correo): bool
    {
        return str_contains(strtolower($correo), 'testuser.com')
            || str_starts_with(strtolower($correo), 'test_user_');
    }

    /**
     * Reconsulta un pago. Nunca se confía en el cuerpo del webhook: el estado
     * real siempre se lee de la API.
     */
    public function obtenerPago(string $paymentId): ?array
    {
        $respuesta = $this->request()->get($this->apiUrl().'/v1/payments/'.rawurlencode($paymentId));

        return $respuesta->successful() ? $respuesta->json() : null;
    }

    /**
     * Último pago asociado a una referencia externa. Se usa al volver de
     * Mercado Pago, cuando el webhook todavía no llegó (p. ej. en desarrollo).
     */
    public function buscarUltimoPago(string $referencia): ?array
    {
        $respuesta = $this->request()->get($this->apiUrl().'/v1/payments/search', [
            'external_reference' => $referencia,
            'sort' => 'date_created',
            'criteria' => 'desc',
            'limit' => 1,
        ]);

        if (! $respuesta->successful()) {
            return null;
        }

        $pagos = $respuesta->json('results') ?? [];

        return is_array($pagos) && isset($pagos[0]) && is_array($pagos[0]) ? $pagos[0] : null;
    }

    /**
     * Aplica al intento el estado leído de la API. Un `approved` solo paga el
     * pedido cuando referencia, divisa, monto y total del pedido cuadran al
     * céntimo; cualquier diferencia deja el pedido pendiente y lo muestra a un
     * humano, porque confirmar de más cuesta dinero o mercancía.
     *
     * @param  array<string, mixed>|null  $pago
     */
    public function aplicarPagoAlIntento(IntentoPagoMercadoPago $intento, ?array $pago): void
    {
        if (! $pago) {
            return;
        }

        $estado = strtolower((string) ($pago['status'] ?? ''));

        $intento->estado_mercadopago = $estado ?: null;
        $intento->detalle_estado_mercadopago = isset($pago['status_detail'])
            ? mb_substr((string) $pago['status_detail'], 0, 80)
            : null;

        $pagoId = isset($pago['id']) ? (string) $pago['id'] : null;
        $pagoDeOtroIntento = $pagoId !== null && IntentoPagoMercadoPago::query()
            ->where('referencia_externa', '!=', $intento->referencia_externa)
            ->where('mercadopago_payment_id', $pagoId)
            ->exists();

        if ($pagoId !== null && ! $pagoDeOtroIntento) {
            $intento->mercadopago_payment_id = $pagoId;
        }

        if ($estado === 'approved') {
            $rechazo = $pagoDeOtroIntento
                ? 'el pago ya confirmó otro intento'
                : $this->inconsistenciaDelPago($intento, $pago);

            if ($rechazo !== null) {
                Log::warning('Mercado Pago aprobó un pago que no se puede aplicar al pedido.', [
                    'referencia' => $intento->referencia_externa,
                    'pedido_id' => $intento->pedido_id,
                    'motivo' => $rechazo,
                    'payment_id' => $pagoId,
                ]);
                $intento->detalle_estado_mercadopago = mb_substr('Pagado pero sin verificar: '.$rechazo, 0, 80);
                $this->registrarNota(
                    $intento->pedido_id,
                    'Mercado Pago reportó un pago aprobado que no se pudo aplicar automáticamente ('.$rechazo
                    .'). El pedido sigue pendiente: verifica el cobro en tu cuenta de Mercado Pago.'
                );

                $intento->save();

                return;
            }

            $this->confirmarPedido($intento, $pago);
            $intento->estado = IntentoPagoMercadoPago::ESTADO_CONFIRMADO;
            $intento->confirmado_at = $intento->confirmado_at ?? now();
        } elseif (in_array($estado, self::ESTADOS_FALLIDOS, true)) {
            $intento->estado = IntentoPagoMercadoPago::ESTADO_FALLIDO;
            $this->registrarNota($intento->pedido_id, $this->notaDePagoFallido($intento, $estado));
        }

        $intento->save();
    }

    /**
     * Revisa en la API los intentos que siguen sin cobro identificado. Un webhook
     * perdido, un túnel caído o el cliente que cierra el navegador no pueden dejar
     * pagado de verdad y pendiente en el sistema.
     *
     * @return array{revisados: int, confirmados: int}
     */
    public function reconciliarIntentosAbiertos(int $limite = 50): array
    {
        if (! $this->habilitado()) {
            return ['revisados' => 0, 'confirmados' => 0];
        }

        $revisados = 0;
        $confirmados = 0;

        $intentos = IntentoPagoMercadoPago::query()
            ->whereIn('estado', [IntentoPagoMercadoPago::ESTADO_ESPERANDO, IntentoPagoMercadoPago::ESTADO_EXPIRADO])
            ->whereNotNull('pedido_id')
            ->whereNull('mercadopago_payment_id')
            ->orderBy('created_at')
            ->limit($limite)
            ->get();

        foreach ($intentos as $intento) {
            $revisados++;
            $estadoAnterior = $intento->estado;

            $this->aplicarPagoAlIntento($intento, $this->buscarUltimoPago($intento->referencia_externa));

            if ($estadoAnterior !== IntentoPagoMercadoPago::ESTADO_CONFIRMADO
                && $intento->estado === IntentoPagoMercadoPago::ESTADO_CONFIRMADO) {
                $confirmados++;
            }
        }

        return ['revisados' => $revisados, 'confirmados' => $confirmados];
    }

    /**
     * Motivo por el que este cobro no se puede aplicar al pedido, o `null` cuando
     * cuadra con lo que la tienda registró.
     *
     * @param  array<string, mixed>  $pago
     */
    private function inconsistenciaDelPago(IntentoPagoMercadoPago $intento, array $pago): ?string
    {
        $referencia = (string) ($pago['external_reference'] ?? '');

        if ($referencia !== '' && $referencia !== $intento->referencia_externa) {
            return 'la referencia del pago no es la del pedido';
        }

        $divisa = strtoupper((string) ($pago['currency_id'] ?? ''));

        if (filled($intento->divisa) && $divisa !== '' && $divisa !== strtoupper((string) $intento->divisa)) {
            return "la divisa cobrada ({$divisa}) no es la del pedido (".strtoupper((string) $intento->divisa).')';
        }

        if (! isset($pago['transaction_amount'])) {
            return 'el pago no informa el monto cobrado';
        }

        $montoCobrado = round((float) $pago['transaction_amount'], 2);
        $montoDelIntento = round((float) $intento->monto, 2);

        if ($montoCobrado !== $montoDelIntento) {
            return 'el monto cobrado ('.$montoCobrado.') no coincide con el del pedido ('.$montoDelIntento.')';
        }

        if ($intento->pedido_id) {
            $totalActual = Orden::where('id', $intento->pedido_id)->value('total');

            if ($totalActual !== null && round((float) $totalActual, 2) !== $montoCobrado) {
                return 'el monto cobrado ('.$montoCobrado.') no coincide con el total actual del pedido ('
                    .round((float) $totalActual, 2).')';
            }
        }

        return null;
    }

    private function confirmarPedido(IntentoPagoMercadoPago $intento, array $pago = []): void
    {
        if (! $intento->pedido_id) {
            return;
        }

        DB::connection('galy')->transaction(function () use ($intento, $pago): void {
            $orden = Orden::query()->lockForUpdate()->find($intento->pedido_id);

            if (! $orden) {
                return;
            }

            if (in_array($orden->estado_pago, ['Pagado', 'COMPLETED', 'approved'], true)) {
                if ($intento->estado !== IntentoPagoMercadoPago::ESTADO_CONFIRMADO) {
                    $this->registrarNota(
                        $orden->id,
                        'Mercado Pago aprobó un cobro adicional'
                        .($intento->mercadopago_payment_id ? ' (ID '.$intento->mercadopago_payment_id.')' : '')
                        .' en un pedido que ya estaba pagado. Revisa si el cliente pagó dos veces.'
                    );
                }

                return;
            }

            $estadoAnterior = $orden->estado_pago;
            $orden->estado_pago = 'Pagado';
            $orden->save();

            $montoCobrado = isset($pago['transaction_amount'])
                ? number_format((float) $pago['transaction_amount'], 2, '.', '').' '.strtoupper((string) ($pago['currency_id'] ?? $intento->divisa))
                : number_format((float) $intento->monto, 2, '.', '').' '.strtoupper((string) $intento->divisa);

            $this->registrarNota(
                $orden->id,
                'Mercado Pago aprobó el pago con tarjeta'
                    .($intento->mercadopago_payment_id ? ' (ID '.$intento->mercadopago_payment_id.')' : '')
                    .'. Se verificó el cobro de '.$montoCobrado.'. El pedido pasó de '.$estadoAnterior.' a Pagado.',
                $estadoAnterior,
                $orden->estado_orden
            );
        });
    }

    private function notaDePagoFallido(IntentoPagoMercadoPago $intento, string $estado): string
    {
        $yaPagado = $intento->pedido_id !== null
            && in_array((string) Orden::where('id', $intento->pedido_id)->value('estado_pago'), ['Pagado', 'COMPLETED', 'approved'], true);

        if ($yaPagado && in_array($estado, IntentoPagoMercadoPago::ESTADOS_DE_REVERSION, true)) {
            return 'Mercado Pago revirtió el cobro ('.$estado.') de un pedido que ya figuraba como pagado. '
                .'Confirma en tu cuenta antes de enviar el producto o de devolver el dinero.';
        }

        return 'Mercado Pago registró el pago como '.$estado.'. El pedido sigue pendiente de pago.';
    }

    private function registrarNota(?int $pedidoId, string $nota, ?string $estadoAnterior = null, ?string $estadoNuevo = null): void
    {
        if (! $pedidoId) {
            return;
        }

        $historial = new PedidoHistorial;
        $historial->orden_id = $pedidoId;
        $historial->estado_anterior = $estadoAnterior;
        $historial->estado_nuevo = $estadoNuevo;
        $historial->fecha = now()->toDateString();
        $historial->hora = now()->toTimeString();
        $historial->nota = $nota;
        $historial->usuario_id = null;
        $historial->save();
    }

    /**
     * Verifica la firma «x-signature» que Mercado Pago incluye en los webhooks.
     * Sin secreto configurado solo se aceptan peticiones en entorno local.
     */
    public function webhookAutenticado(Request $request, ?string $dataId): bool
    {
        $secreto = config('services.mercadopago.webhook_secret');

        if (blank($secreto)) {
            return app()->environment('local', 'testing');
        }

        if (blank($dataId)) {
            return false;
        }

        $partes = [];
        foreach (explode(',', (string) $request->headers->get('x-signature', '')) as $par) {
            [$clave, $valor] = array_pad(explode('=', trim($par), 2), 2, null);
            if (filled($clave)) {
                $partes[$clave] = (string) $valor;
            }
        }

        $marca = $partes['ts'] ?? null;
        $firma = $partes['v1'] ?? null;

        if (! $marca || ! $firma) {
            return false;
        }

        $manifiesto = sprintf(
            'id:%s;request-id:%s;ts:%s;',
            $dataId,
            (string) $request->headers->get('x-request-id', ''),
            $marca
        );

        return hash_equals(hash_hmac('sha256', $manifiesto, (string) $secreto), $firma);
    }

    /**
     * Registra el intento y abre la pasarela antes de crear el pedido: si Mercado
     * Pago no responde, el carrito y el stock quedan intactos.
     *
     * @param  array{usuario_id: int, items: array, direccion_id: int|null, direccion_envio: string,
     *               modalidad_entrega: string, monto: float, divisa: string, correo: string|null}  $datos
     */
    public function abrirCheckout(array $datos): IntentoPagoMercadoPago
    {
        $intento = IntentoPagoMercadoPago::create([
            'usuario_id' => $datos['usuario_id'],
            'firma_carrito' => hash('sha256', json_encode([$datos['usuario_id'], $datos['items'], $datos['monto']])),
            'monto' => $datos['monto'],
            'divisa' => $datos['divisa'],
            'estado' => IntentoPagoMercadoPago::ESTADO_INICIANDO,
            'direccion_id' => $datos['direccion_id'],
            'direccion_envio' => (string) $datos['direccion_envio'],
            'modalidad_entrega' => (string) $datos['modalidad_entrega'],
            'items' => $datos['items'],
            'expira_at' => now()->addMinutes($this->minutosVigencia()),
        ]);

        try {
            $checkout = $this->crearCheckout(
                referencia: $intento->referencia_externa,
                monto: (float) $datos['monto'],
                divisa: (string) $intento->divisa,
                titulo: 'Compra en la tienda GALY',
                descripcion: 'Referencia de pago '.$intento->referencia_externa,
                payer: ['email' => (string) $datos['correo']],
            );
        } catch (Throwable $exception) {
            report($exception);
            $intento->estado = IntentoPagoMercadoPago::ESTADO_FALLIDO;
            $intento->save();

            return $intento;
        }

        $intento->mercadopago_order_id = $checkout['preferencia_id'];
        $intento->checkout_url = $checkout['checkout_url'];
        $intento->estado = IntentoPagoMercadoPago::ESTADO_ESPERANDO;
        $intento->save();

        return $intento;
    }

    /**
     * Reabre la pasarela para un pedido con tarjeta que sigue pendiente de pago.
     */
    public function reabrirCheckout(Orden $orden, ?string $correo): IntentoPagoMercadoPago
    {
        IntentoPagoMercadoPago::where('pedido_id', $orden->id)
            ->where('estado', IntentoPagoMercadoPago::ESTADO_ESPERANDO)
            ->update(['estado' => IntentoPagoMercadoPago::ESTADO_EXPIRADO]);

        return $this->abrirCheckout([
            'usuario_id' => (int) $orden->usuario_id,
            'items' => $orden->detalles()->with('producto')->get()->map(fn ($detalle) => [
                'producto_id' => (int) $detalle->producto_id,
                'nombre' => $detalle->producto?->nombre,
                'cantidad' => (int) $detalle->cantidad,
                'precio' => (float) $detalle->precio,
            ])->all(),
            'direccion_id' => $orden->direccion_id,
            'direccion_envio' => (string) $orden->direccion_envio,
            'modalidad_entrega' => (string) $orden->modalidad_entrega,
            'monto' => (float) $orden->total,
            'divisa' => (string) ($orden->divisa ?: config('services.mercadopago.currency')),
            'correo' => $correo,
        ]);
    }

    public function minutosVigencia(): int
    {
        return max(5, (int) config('services.mercadopago.checkout_ttl_minutes', 30));
    }

    /**
     * Cierra los cheques que el cliente nunca pagó, para que el panel no muestre
     * pagos eternamente "en curso".
     */
    public function caducarVencidos(): int
    {
        return IntentoPagoMercadoPago::query()
            ->where('estado', IntentoPagoMercadoPago::ESTADO_ESPERANDO)
            ->where('expira_at', '<', now())
            ->update([
                'estado' => IntentoPagoMercadoPago::ESTADO_EXPIRADO,
                'updated_at' => now(),
            ]);
    }

    /**
     * URL pública desde la que Mercado Pago puede devolver al cliente y avisar al
     * servidor. Puede ser un túnel distinto al APP_URL del sitio.
     */
    public function urlBase(): string
    {
        return rtrim((string) (config('services.mercadopago.url_base') ?: config('app.url')), '/');
    }

    public function urlDeRetorno(): string
    {
        return $this->urlBase().'/pago/tarjeta/retorno';
    }

    public function urlDeWebhook(): string
    {
        return $this->urlBase().'/webhooks/mercadopago';
    }

    private function request(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withToken((string) $this->accessToken())
            ->acceptJson()
            ->timeout(20)
            ->retry(2, 250, throw: false);
    }

    private function apiUrl(): string
    {
        return rtrim((string) config('services.mercadopago.api_url', 'https://api.mercadopago.com'), '/');
    }
}
