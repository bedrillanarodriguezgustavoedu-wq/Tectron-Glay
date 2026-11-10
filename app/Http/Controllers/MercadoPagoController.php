<?php

namespace App\Http\Controllers;

use App\Models\Ajuste;
use App\Models\IntentoPagoMercadoPago;
use App\Models\Orden;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class MercadoPagoController extends Controller
{
    public function __construct(private MercadoPagoService $mercadoPago)
    {
    }

    /**
     * Página a la que Mercado Pago devuelve al cliente tras pagar. El estado se
     * reconsulta en la API: los parámetros de la URL no son una prueba de pago.
     * Solo sirven para lo contrario, para cerrar un intento que no dejó cobro.
     */
    public function retorno(Request $request)
    {
        $intento = IntentoPagoMercadoPago::where('referencia_externa', (string) $request->query('referencia', ''))->first();

        if (! $intento) {
            return redirect()->route('web.carrito.index')
                ->with('mensaje', 'No encontramos el intento de pago con tarjeta. Vuelve a intentarlo desde el carrito.')
                ->with('icono', 'warning');
        }

        $usuarioId = auth()->user()?->resolveGalyUserId();

        if ($usuarioId === null) {
            return redirect()->route('web.carrito.index')
                ->with('mensaje', 'Tu cuenta no está vinculada a GALY. Inicia sesión con tu cuenta para continuar el pago.')
                ->with('icono', 'warning');
        }

        if ((int) $intento->usuario_id !== (int) $usuarioId) {
            Log::warning('Retorno de Mercado Pago pedido con la referencia de otro cliente.', [
                'referencia' => $intento->referencia_externa,
                'solicitante' => $usuarioId,
            ]);

            return redirect()->route('web.carrito.index')
                ->with('mensaje', 'Ese pago no pertenece a tu cuenta.')
                ->with('icono', 'warning');
        }

        $this->mercadoPago->aplicarPagoAlIntento(
            $intento,
            $this->buscarPagoDelIntento($intento, $request->query('payment_id')),
            mb_strtolower(trim((string) $request->query('estado', '')))
        );

        $destino = $intento->pedido_id
            ? redirect()->route('web.pedidos.confirmacion', $intento->pedido_id)
            : redirect()->route('web.carrito.index');

        return match ($intento->estado) {
            IntentoPagoMercadoPago::ESTADO_CONFIRMADO => $destino
                ->with('mensaje', 'Mercado Pago confirmó tu pago. El pedido ya está en proceso.')
                ->with('icono', 'success'),
            IntentoPagoMercadoPago::ESTADO_FALLIDO => $destino
                ->with('mensaje', 'Tu tarjeta fue rechazada y no se te cobró nada. Puedes reintentar el pago o elegir otro método.')
                ->with('icono', 'warning'),
            default => $destino
                ->with('mensaje', 'Todavía no encontramos el pago en Mercado Pago. Si ya pagaste, espera unos segundos y recarga; no repitas el cobro.')
                ->with('icono', 'info'),
        };
    }

    /**
     * El `payment_id` llega por URL y cualquiera puede escribirlo: solo se acepta
     * cuando la API confirma que ese pago es de este intento. Si no, se busca el
     * último pago de la referencia, que es el único que Mercado Pago puede asignar.
     */
    private function buscarPagoDelIntento(IntentoPagoMercadoPago $intento, mixed $paymentId): ?array
    {
        $pago = blank($paymentId) ? null : $this->mercadoPago->obtenerPago((string) $paymentId);

        if ($pago !== null && (string) ($pago['external_reference'] ?? '') === $intento->referencia_externa) {
            return $pago;
        }

        if ($pago !== null) {
            Log::warning('El payment_id del retorno pertenece a otra referencia y se descartó.', [
                'payment_id' => (string) $paymentId,
                'referencia_solicitada' => $intento->referencia_externa,
                'referencia_real' => (string) ($pago['external_reference'] ?? ''),
            ]);
        }

        return $this->mercadoPago->buscarUltimoPago($intento->referencia_externa);
    }

    /**
     * Notificación asíncrona de Mercado Pago. Siempre responde 204 para que el
     * gateway no reintente, y nunca asume que el cuerpo sea veraz.
     */
    public function webhook(Request $request)
    {
        $dataId = $request->input('data.id') ?? $request->query('id');
        $tipo = $request->input('type') ?? $request->query('topic');

        if (! $this->mercadoPago->webhookAutenticado($request, $dataId ? (string) $dataId : null)) {
            Log::warning('Webhook de Mercado Pago rechazado por firma inválida o ausente.', [
                'ip' => $request->ip(),
                'data_id' => $dataId,
            ]);

            return response()->noContent();
        }

        if ($tipo !== 'payment' || blank($dataId)) {
            return response()->noContent();
        }

        $pago = $this->mercadoPago->obtenerPago((string) $dataId);
        $referencia = $pago['external_reference'] ?? null;

        if (blank($referencia)) {
            return response()->noContent();
        }

        $intento = IntentoPagoMercadoPago::where('referencia_externa', (string) $referencia)->first();
        if ($intento) {
            $this->mercadoPago->aplicarPagoAlIntento($intento, $pago);
        }

        return response()->noContent();
    }

    /**
     * Reabre el checkout para un pedido con tarjeta que quedó pendiente.
     */
    public function reintentar($id)
    {
        $usuarioId = auth()->user()?->resolveGalyUserId();
        abort_if($usuarioId === null, 403);

        $orden = Orden::where('usuario_id', $usuarioId)->where('metodo_pago', 'tarjeta')->findOrFail($id);

        if (in_array($orden->estado_pago, ['Pagado', 'COMPLETED', 'approved'], true)) {
            return redirect()->route('web.pedidos.confirmacion', $orden->id)
                ->with('mensaje', 'Este pedido ya quedó pagado con tarjeta.')
                ->with('icono', 'info');
        }

        // Un cobro aprobado que aún no se pudo aplicar es trabajo de la tienda, no del
        // cliente: reabrir la pasarela lo expondría a que le cobren dos veces.
        if (IntentoPagoMercadoPago::where('pedido_id', $orden->id)->where('estado_mercadopago', 'approved')->exists()) {
            return redirect()->route('web.pedidos.confirmacion', $orden->id)
                ->with('mensaje', 'Mercado Pago ya registró un cobro para este pedido y la tienda lo está verificando. No repitas el pago.')
                ->with('icono', 'warning');
        }

        if (! $this->mercadoPago->sePuedeOfrecer() || ! Ajuste::first()?->web_mercadopago_activa) {
            return redirect()->route('web.pedidos.confirmacion', $orden->id)
                ->with('mensaje', 'El pago con tarjeta no está disponible por ahora. Contacta a la tienda para pagar por otro método.')
                ->with('icono', 'warning');
        }

        $intento = $this->mercadoPago->reabrirCheckout($orden, auth()->user()?->email);

        if ($intento->estado === IntentoPagoMercadoPago::ESTADO_FALLIDO) {
            return redirect()->route('web.pedidos.confirmacion', $orden->id)
                ->with('mensaje', 'Mercado Pago no respondió. Espera unos minutos e inténtalo de nuevo.')
                ->with('icono', 'error');
        }

        return redirect()->away($intento->checkout_url);
    }

    /**
     * Cobra la tarjeta dentro de la tienda con el token que generó el Brick de
     * Mercado Pago en el navegador del cliente. Responde JSON porque lo llama ese
     * formulario, no una recarga de página.
     *
     * Lo que se cobra es el monto guardado en el intento, nunca lo que envía el
     * cliente: el campo del formulario es editable y un S/ 1.00 bien enviado sería
     * un pedido entregado por la mitad de su precio.
     */
    public function pagoBrick(Request $request, $id)
    {
        try {
        $usuarioId = auth()->user()?->resolveGalyUserId();
        abort_if($usuarioId === null, 403);

        $orden = Orden::where('usuario_id', $usuarioId)->where('metodo_pago', 'tarjeta')->findOrFail($id);

        if (! $this->mercadoPago->brickListo()) {
            return response()->json([
                'resultado' => 'indisponible',
                'mensaje' => 'El pago con tarjeta dentro de la tienda no está disponible ahora mismo. Recarga la página para elegir otro método.',
            ], 409);
        }

        if (in_array($orden->estado_pago, ['Pagado', 'COMPLETED', 'approved'], true)) {
            return response()->json([
                'resultado' => 'confirmado',
                'mensaje' => 'Este pedido ya quedó pagado con tarjeta.',
                'redireccion' => route('web.pedidos.confirmacion', $orden->id),
            ]);
        }

        // Igual que en `reintentar`: un cobro aprobado que todavía no se pudo aplicar
        // es trabajo de la tienda. Dejar que el cliente vuelva a pasar la tarjeta lo
        // expondría a pagar dos veces por lo mismo.
        if (IntentoPagoMercadoPago::where('pedido_id', $orden->id)->where('estado_mercadopago', 'approved')->exists()) {
            return response()->json([
                'resultado' => 'pendiente',
                'mensaje' => 'Mercado Pago ya registró un cobro para este pedido y la tienda lo está verificando. No repitas el pago.',
                'redireccion' => route('web.pedidos.confirmacion', $orden->id),
            ], 409);
        }

        $datos = $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'payment_method_id' => ['required', 'string', 'max:60'],
            'issuer_id' => ['nullable', 'integer', 'min:1'],
            'installments' => ['nullable', 'integer', 'between:1,60'],
            'email' => ['nullable', 'email:rfc', 'max:190'],
            // Datos del titular que el Brick ya recogió: enviarlos a MP
            // mejora la tasa de aprobación (antifraude).
            'first_name' => ['nullable', 'string', 'max:80'],
            'last_name' => ['nullable', 'string', 'max:80'],
            'dni' => ['nullable', 'string', 'max:20'],
            'identification' => ['nullable', 'array'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable'],
        ]);

        $intento = $this->mercadoPago->intentoParaBrick($orden);

        // Si el Brick no envió dirección, usamos la del pedido para el
        // antifraude de Mercado Pago (mejora la tasa de aprobación).
        $direccionModelo = $orden->direccion()->first();
        if (empty($datos['address']) && $direccionModelo) {
            $d = $direccionModelo;
            $datos['address'] = array_filter([
                'street_name' => trim((string) $d->direccion_exacta) ?: null,
                'zip_code' => (string) ($d->codigo_postal ?? ''),
            ], fn ($v) => $v !== null && $v !== '');
        } elseif (empty($datos['address']) && filled($orden->direccion_envio)) {
            $datos['address'] = $orden->direccion_envio;
        }
        if (empty($datos['phone'])) {
            $datos['phone'] = auth()->user()?->telefono;
        }
        if (empty($datos['first_name'])) {
            $datos['first_name'] = auth()->user()?->name;
        }
        if (empty($datos['dni'])) {
            $datos['dni'] = auth()->user()?->dni;
        }

        try {
            $pago = $this->mercadoPago->crearPagoDirecto($intento, $datos);
        } catch (Throwable $exception) {
            // El cuerpo de Mercado Pago trae el detalle para el dueño, no para el
            // cliente: mostrarlo tal cual revelaría referencias internas y códigos
            // que nadie de la tienda sabe leer.
            report($exception);

            // Solo tiene sentido buscar un cobro «perdido» cuando la causa es de
            // red o de tiempo: un 400/401/403 de Mercado Pago no dejó ningún
            // cobro que recuperar, y buscarlo solo retrasa la respuesta.
            $falloDeRed = $exception instanceof \Illuminate\Http\Client\ConnectionException
                || str_contains($exception->getMessage(), 'timed out')
                || str_contains($exception->getMessage(), 'Could not resolve')
                || str_contains($exception->getMessage(), 'cURL error');

            $pagoTardio = $falloDeRed
                ? $this->mercadoPago->pagoTrasFalloDeRed($intento)
                : null;

            if ($pagoTardio !== null) {
                $this->mercadoPago->aplicarPagoAlIntento($intento, $pagoTardio, 'brick');

                $confirmado = $intento->estado === IntentoPagoMercadoPago::ESTADO_CONFIRMADO;

                return response()->json([
                    'resultado' => $confirmado ? 'confirmado' : 'pendiente',
                    'mensaje' => $confirmado
                        ? 'Mercado Pago confirmó tu pago. El pedido ya está en proceso.'
                        : 'Mercado Pago sí registró un cobro, pero el banco aún no lo confirma. No repitas el pago.',
                    'redireccion' => route('web.pedidos.confirmacion', $orden->id),
                ]);
            }

            $mensaje = $falloDeRed
                ? 'No pudimos comunicarnos con Mercado Pago y no se registró ningún cobro. Inténtalo de nuevo en unos minutos.'
                : (preg_match('/HTTP (401|403)/', $exception->getMessage())
                    ? 'La pasarela de tarjeta no está disponible ahora. Elige otro método o inténtalo más tarde.'
                    : 'Mercado Pago no pudo procesar el pago y no se te cobró nada. Inténtalo de nuevo.');

            return response()->json([
                'resultado' => 'error',
                'mensaje' => $mensaje,
            ], 502);
        }

        $this->mercadoPago->aplicarPagoAlIntento($intento, $pago, 'brick');

        $confirmado = $intento->estado === IntentoPagoMercadoPago::ESTADO_CONFIRMADO;
        $rechazado = $intento->estado === IntentoPagoMercadoPago::ESTADO_FALLIDO;

        return response()->json([
            'resultado' => $confirmado ? 'confirmado' : ($rechazado ? 'rechazado' : 'pendiente'),
            'mensaje' => match (true) {
                $confirmado => 'Mercado Pago confirmó tu pago. El pedido ya está en proceso.',
                $rechazado => 'Tu tarjeta fue rechazada y no se te cobró nada. Puedes probar con otra tarjeta.',
                default => 'El banco todavía no confirma el cobro. No repitas el pago: si pasa, el pedido se actualiza solo.',
            },
            'redireccion' => route('web.pedidos.confirmacion', $orden->id),
        ]);
        } catch (\Throwable $exception) {
            if ($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpException
                || $exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException
                || $exception instanceof \Illuminate\Auth\Access\AuthorizationException
                || $exception instanceof \Illuminate\Validation\ValidationException) {
                throw $exception;
            }

            report($exception);

            return response()->json([
                'resultado' => 'error',
                'mensaje' => 'No pudimos procesar el pago. Inténtalo de nuevo en unos minutos.',
            ], 500);
        }
    }
}
