<?php

namespace App\Services;

use App\Models\IntentoPagoIzipay;
use App\Models\Orden;
use App\Models\PedidoHistorial;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Pasarela Izipay (Mi Cuenta Web / PayZen).
 *
 * El navegador del cliente dibuja el formulario de Izipay con un `formToken`
 * que se pide aquí. La tienda nunca recibe el número de tarjeta ni el CVV.
 * Cuando el cliente paga, Izipay devuelve `kr-answer` firmado con HMAC-SHA-256:
 * si la firma cuadra y `orderStatus` es PAID, el pedido se marca como pagado.
 */
class IzipayService
{
    /** Estados en los que Izipay da el intento por cerrado sin cobrar. */
    private const ESTADOS_SIN_PAGO = ['UNPAID', 'EXPIRED', 'ABANDONED'];

    public function habilitado(): bool
    {
        return config('services.izipay.enabled') === true
            && filled($this->shopId())
            && filled($this->password())
            && filled($this->publicKey())
            && filled($this->hmacKey());
    }

    /**
     * Se puede ofrecer en el carrito solo si la tienda lo encendió y las cuatro
     * credenciales están puestas. Sin la clave HMAC no se podría validar la
     * respuesta, así que tampoco se ofrece.
     */
    public function sePuedeOfrecer(): bool
    {
        return $this->habilitado();
    }

    public function shopId(): ?string
    {
        return $this->limpiar(config('services.izipay.shop_id'));
    }

    public function password(): ?string
    {
        return $this->limpiar(config('services.izipay.password'));
    }

    public function publicKey(): ?string
    {
        return $this->limpiar(config('services.izipay.public_key'));
    }

    public function hmacKey(): ?string
    {
        return $this->limpiar(config('services.izipay.hmac_key'));
    }

    public function esSandbox(): bool
    {
        return strtolower((string) config('services.izipay.environment', 'production')) === 'sandbox';
    }

    public function apiUrl(): string
    {
        $configurada = $this->limpiar(config('services.izipay.api_url'));

        if ($configurada !== null) {
            return $configurada;
        }

        return $this->esSandbox()
            ? 'https://sandbox-api.micuentaweb.pe/api-payment/V4/Charge/CreatePayment'
            : 'https://api.micuentaweb.pe/api-payment/V4/Charge/CreatePayment';
    }

    public function scriptUrl(): string
    {
        $configurada = $this->limpiar(config('services.izipay.script_url'));

        if ($configurada !== null) {
            return $configurada;
        }

        return $this->esSandbox()
            ? 'https://sandbox-static.micuentaweb.pe/static/js/krypton-client/V4.0/stable/kr-payment-form.min.js'
            : 'https://static.micuentaweb.pe/static/js/krypton-client/V4.0/stable/kr-payment-form.min.js';
    }

    public function urlBase(): string
    {
        $base = $this->limpiar(config('services.izipay.url_base'))
            ?: (string) config('app.url');

        return rtrim($base, '/');
    }

    public function urlRetorno(): string
    {
        return $this->urlBase().'/pago/izipay/retorno';
    }

    public function urlIpn(): string
    {
        return $this->urlBase().'/webhooks/izipay';
    }

    /**
     * Pide a Izipay un formulario de pago. No cobra nada: el `formToken` solo
     * habilita el formulario embebido del navegador.
     *
     * @param  array<string, mixed>  $datos
     * @return array{referencia: string, form_token: string, public_key: string, monto: float, divisa: string}
     */
    public function prepararPago(array $datos): array
    {
        if (! $this->habilitado()) {
            throw new RuntimeException('Izipay no está configurado.');
        }

        $monto = round((float) ($datos['monto'] ?? 0), 2);

        if ($monto <= 0) {
            throw new RuntimeException('El monto del pago debe ser mayor a cero.');
        }

        $divisa = strtoupper((string) ($datos['divisa'] ?? 'PEN'));
        $referencia = (string) Str::uuid();

        $respuesta = $this->crearFormToken([
            'referencia' => $referencia,
            'monto' => $monto,
            'divisa' => $divisa,
            'correo' => $datos['correo'] ?? null,
            'nombre' => $datos['nombre'] ?? null,
            'dni' => $datos['dni'] ?? null,
            'telefono' => $datos['telefono'] ?? null,
            'direccion' => $datos['direccion'] ?? null,
        ]);

        return [
            'referencia' => $referencia,
            'form_token' => $respuesta['form_token'],
            'public_key' => $respuesta['public_key'],
            'monto' => $monto,
            'divisa' => $divisa,
        ];
    }

    /**
     * Guarda el intento ya ligado al pedido. El token se obtuvo antes de crear
     * el pedido a propósito: si Izipay no responde, no queda una compra a medias.
     *
     * @param  array{referencia: string, form_token: string, monto: float|int|string, divisa: string}  $preparado
     */
    public function registrarIntento(Orden $orden, array $preparado): IntentoPagoIzipay
    {
        return IntentoPagoIzipay::create([
            'referencia' => $preparado['referencia'],
            'usuario_id' => $orden->usuario_id,
            'pedido_id' => $orden->id,
            'monto' => $preparado['monto'],
            'divisa' => $preparado['divisa'],
            'estado' => IntentoPagoIzipay::ESTADO_ESPERANDO,
            'form_token' => $preparado['form_token'],
            'expira_at' => now()->addMinutes((int) config('services.izipay.checkout_ttl_minutes', 30)),
        ]);
    }

    /**
     * Reabre el pago de un pedido que quedó pendiente (el cliente cerró la
     * pestaña, la tarjeta fue rechazada, etc.). Crea una referencia nueva para
     * no reutilizar un `orderId` que Izipay ya vio.
     *
     * @param  array<string, mixed>  $datos
     */
    public function reabrirParaOrden(Orden $orden, array $datos = []): IntentoPagoIzipay
    {
        $preparado = $this->prepararPago([
            'monto' => (float) $orden->total,
            'divisa' => $orden->divisa ?: 'PEN',
            'correo' => $datos['correo'] ?? null,
            'nombre' => $datos['nombre'] ?? null,
            'dni' => $datos['dni'] ?? null,
            'telefono' => $datos['telefono'] ?? null,
            'direccion' => $orden->direccion_envio,
        ]);

        return $this->registrarIntento($orden, $preparado);
    }

    /**
     * Comprueba las credenciales pidiendo un formulario de S/ 1.00. No cobra:
     * solo confirma que el comercio y la clave API son válidos.
     *
     * @return array{ok: bool, mensaje: string, sandbox: bool}
     */
    public function probarCredenciales(): array
    {
        if (! $this->habilitado()) {
            return [
                'ok' => false,
                'mensaje' => 'Faltan IZIPAY_SHOP_ID, IZIPAY_PASSWORD, IZIPAY_PUBLIC_KEY o IZIPAY_HMAC_KEY en el .env.',
                'sandbox' => $this->esSandbox(),
            ];
        }

        try {
            $this->crearFormToken([
                'referencia' => 'PRUEBA-'.Str::upper(Str::random(12)),
                'monto' => 1.00,
                'divisa' => 'PEN',
                'correo' => 'prueba@example.com',
            ]);
        } catch (\Throwable $exception) {
            return [
                'ok' => false,
                'mensaje' => $exception->getMessage(),
                'sandbox' => $this->esSandbox(),
            ];
        }

        return [
            'ok' => true,
            'mensaje' => 'Izipay aceptó las credenciales y devolvió un formulario de pago de prueba.',
            'sandbox' => $this->esSandbox(),
        ];
    }

    /**
     * Valida la firma HMAC de la respuesta (`kr-answer`) y devuelve el arreglo
     * decodificado, o `null` si la firma no cuadra o falta.
     *
     * En el retorno del navegador la clave es la HMAC; en la IPN, Izipay manda
     * `kr-hash-key=password` y firma con la clave API. Se respeta lo que diga
     * el propio mensaje.
     *
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>|null
     */
    public function respuestaVerificada(array $datos): ?array
    {
        $respuesta = $datos['kr-answer'] ?? null;
        $firma = $datos['kr-hash'] ?? null;

        if (! is_string($respuesta) || ! is_string($firma) || $respuesta === '' || $firma === '') {
            return null;
        }

        $algoritmo = strtolower((string) ($datos['kr-hash-algorithm'] ?? 'sha256_hmac'));
        $algoritmo = explode('_', $algoritmo)[0] ?: 'sha256';

        if (! in_array($algoritmo, hash_hmac_algos(), true)) {
            $algoritmo = 'sha256';
        }

        $clave = strtolower((string) ($datos['kr-hash-key'] ?? 'sha256_hmac')) === 'password'
            ? (string) $this->password()
            : (string) $this->hmacKey();

        if ($clave === '') {
            return null;
        }

        $calculada = hash_hmac($algoritmo, $respuesta, $clave);

        if (! hash_equals($calculada, strtolower($firma))) {
            return null;
        }

        $decodificada = json_decode($respuesta, true);

        return is_array($decodificada) ? $decodificada : null;
    }

    /**
     * Aplica al pedido lo que Izipay acaba de informar.
     *
     * @param  array<string, mixed>  $respuesta
     */
    public function aplicarRespuesta(IntentoPagoIzipay $intento, array $respuesta, string $origen = 'retorno'): void
    {
        $detalles = (array) ($respuesta['orderDetails'] ?? []);
        $transaccion = (array) (($respuesta['transactions'] ?? [])[0] ?? []);
        $estado = strtoupper((string) ($respuesta['orderStatus'] ?? ''));
        $referencia = (string) ($detalles['orderId'] ?? '');
        $uuid = (string) ($transaccion['uuid'] ?? '');

        $intento->estado_izipay = $estado ?: null;
        $intento->metodo_izipay = mb_substr((string) ($transaccion['paymentMethodType'] ?? ''), 0, 40) ?: null;
        $intento->detalle_estado_izipay = mb_substr(
            trim((string) ($transaccion['status'] ?? '')),
            0,
            120
        ) ?: null;

        if ($uuid !== '' && ! IntentoPagoIzipay::query()
            ->where('referencia', '!=', $intento->referencia)
            ->where('izipay_uuid', $uuid)
            ->exists()) {
            $intento->izipay_uuid = $uuid;
        }

        if ($estado !== 'PAID') {
            if (in_array($estado, self::ESTADOS_SIN_PAGO, true)) {
                $intento->estado = $estado === 'EXPIRED'
                    ? IntentoPagoIzipay::ESTADO_EXPIRADO
                    : IntentoPagoIzipay::ESTADO_FALLIDO;

                $this->registrarNota(
                    $intento->pedido_id,
                    'Izipay devolvió el pago como '.$estado.'. El pedido sigue pendiente: no se le cobró nada al cliente.'
                );
            }

            $intento->save();

            return;
        }

        $problema = $this->inconsistencia($intento, $detalles, $transaccion, $referencia);

        if ($problema !== null) {
            Log::warning('Izipay aprobó un pago que no se puede aplicar al pedido.', [
                'referencia' => $intento->referencia,
                'pedido_id' => $intento->pedido_id,
                'motivo' => $problema,
                'origen' => $origen,
            ]);

            $intento->detalle_estado_izipay = mb_substr('Pagado sin verificar: '.$problema, 0, 120);
            $this->registrarNota(
                $intento->pedido_id,
                'Izipay reportó un pago aprobado que no se pudo aplicar automáticamente ('.$problema
                .'). El pedido sigue pendiente: verifica el cobro en tu cuenta de Izipay.'
            );
            $intento->save();

            return;
        }

        $this->confirmarPedido($intento, $uuid);
        $intento->estado = IntentoPagoIzipay::ESTADO_CONFIRMADO;
        $intento->pagado_at = $intento->pagado_at ?? now();
        $intento->save();
    }

    /**
     * @param  array<string, mixed>  $detalles
     * @param  array<string, mixed>  $transaccion
     */
    private function inconsistencia(
        IntentoPagoIzipay $intento,
        array $detalles,
        array $transaccion,
        string $referencia
    ): ?string {
        if ($referencia !== '' && $referencia !== (string) $intento->referencia) {
            return 'la referencia del pago no es la del pedido';
        }

        $divisa = strtoupper((string) ($detalles['orderCurrency'] ?? $detalles['currency'] ?? ''));

        if ($divisa !== '' && $divisa !== strtoupper((string) $intento->divisa)) {
            return "la divisa cobrada ({$divisa}) no es la del pedido (".strtoupper((string) $intento->divisa).')';
        }

        // Izipay trabaja en céntimos: se comparan como enteros para no pelear
        // con los decimales de un float. Se acepta el monto del detalle o el de
        // la transacción, porque según el medio uno de los dos puede faltar.
        $esperado = (int) round(((float) $intento->monto) * 100);
        $montos = array_filter([
            $detalles['orderTotalAmount'] ?? null,
            $transaccion['amount'] ?? null,
        ], fn ($valor) => $valor !== null && $valor !== '');

        if ($montos === []) {
            return 'el pago no informa el monto cobrado';
        }

        $coincide = collect($montos)->contains(
            fn ($valor) => (int) round((float) $valor) === $esperado
        );

        if (! $coincide) {
            return 'el monto cobrado ('.implode(' / ', array_map('strval', $montos))
                .' céntimos) no coincide con el del pedido ('.$esperado.' céntimos)';
        }

        return null;
    }

    private function confirmarPedido(IntentoPagoIzipay $intento, string $uuid): void
    {
        if (! $intento->pedido_id) {
            return;
        }

        DB::connection('galy')->transaction(function () use ($intento, $uuid): void {
            $orden = Orden::query()->lockForUpdate()->find($intento->pedido_id);

            if (! $orden) {
                return;
            }

            if (in_array($orden->estado_pago, ['Pagado', 'COMPLETED', 'approved'], true)) {
                if ($intento->estado !== IntentoPagoIzipay::ESTADO_CONFIRMADO) {
                    $this->registrarNota(
                        $orden->id,
                        'Izipay aprobó un cobro adicional'
                        .($uuid !== '' ? ' (operación '.$uuid.')' : '')
                        .' en un pedido que ya estaba pagado. Revisa si el cliente pagó dos veces.'
                    );
                }

                return;
            }

            $orden->estado_pago = 'Pagado';

            if ($uuid !== '') {
                $orden->transaccion_id = $uuid;
            }

            $orden->save();

            $monto = number_format((float) $intento->monto, 2, '.', '').' '.strtoupper((string) $intento->divisa);

            $this->registrarNota(
                $orden->id,
                'Izipay confirmó el pago con tarjeta'
                    .($uuid !== '' ? ' (operación '.$uuid.')' : '')
                    .' por '.$monto.'.'
            );
        });
    }

    private function registrarNota(?int $pedidoId, string $nota): void
    {
        if (! $pedidoId) {
            return;
        }

        $historial = new PedidoHistorial;
        $historial->orden_id = $pedidoId;
        $historial->estado_anterior = null;
        $historial->estado_nuevo = 'En proceso';
        $historial->fecha = now()->toDateString();
        $historial->hora = now()->toTimeString();
        $historial->nota = $nota;
        $historial->usuario_id = null;
        $historial->save();
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array{form_token: string, public_key: string}
     */
    private function crearFormToken(array $datos): array
    {
        $montoCentimos = (int) round(((float) $datos['monto']) * 100);

        $facturacion = array_filter([
            'firstName' => $datos['nombre'] ?? null,
            'lastName' => null,
            'phoneNumber' => $datos['telefono'] ?? null,
            'identityType' => filled($datos['dni'] ?? null) ? 'DNI' : null,
            'identityCode' => $datos['dni'] ?? null,
            'address' => $datos['direccion'] ?? null,
            'country' => 'PE',
        ], fn ($valor) => filled($valor));

        $payload = [
            'amount' => $montoCentimos,
            'currency' => strtoupper((string) $datos['divisa']),
            'orderId' => (string) $datos['referencia'],
            'customer' => array_filter([
                'email' => $datos['correo'] ?? null,
                'billingDetails' => $facturacion ?: null,
            ], fn ($valor) => filled($valor)),
        ];

        $respuesta = Http::withBasicAuth((string) $this->shopId(), (string) $this->password())
            ->acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout(15)
            ->post($this->apiUrl(), $payload);

        $cuerpo = $respuesta->json();

        if (! $respuesta->successful() || strtoupper((string) ($cuerpo['status'] ?? '')) !== 'SUCCESS') {
            Log::error('Izipay rechazó la creación del formulario de pago.', [
                'http' => $respuesta->status(),
                'estado' => $cuerpo['status'] ?? null,
                'error' => $cuerpo['answer']['errorCode'] ?? null,
                'mensaje' => $cuerpo['answer']['errorMessage'] ?? null,
                'referencia' => $datos['referencia'] ?? null,
            ]);

            throw new RuntimeException(
                'Izipay no pudo abrir el formulario de pago (HTTP '.$respuesta->status().').'
            );
        }

        $formToken = $cuerpo['answer']['formToken'] ?? null;

        if (! filled($formToken)) {
            throw new RuntimeException('Izipay no devolvió un formToken utilizable.');
        }

        return [
            'form_token' => (string) $formToken,
            'public_key' => (string) $this->publicKey(),
        ];
    }

    private function limpiar(mixed $valor): ?string
    {
        $texto = trim((string) $valor);

        return $texto === '' ? null : $texto;
    }
}
