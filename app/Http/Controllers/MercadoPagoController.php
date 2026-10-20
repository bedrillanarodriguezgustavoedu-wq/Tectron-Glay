<?php

namespace App\Http\Controllers;

use App\Models\Ajuste;
use App\Models\IntentoPagoMercadoPago;
use App\Models\Orden;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MercadoPagoController extends Controller
{
    public function __construct(private MercadoPagoService $mercadoPago)
    {
    }

    /**
     * Página a la que Mercado Pago devuelve al cliente tras pagar. El estado se
     * reconsulta en la API: los parámetros de la URL no son una prueba de pago.
     */
    public function retorno(Request $request)
    {
        $intento = IntentoPagoMercadoPago::where('referencia_externa', (string) $request->query('referencia', ''))->first();

        if (! $intento) {
            return redirect()->route('web.carrito.index')
                ->with('mensaje', 'No encontramos el intento de pago con tarjeta. Vuelve a intentarlo desde el carrito.')
                ->with('icono', 'warning');
        }

        $usuarioId = auth()->user()->resolveGalyUserId();

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

        $this->mercadoPago->aplicarPagoAlIntento($intento, $this->buscarPagoDelIntento($intento, $request->query('payment_id')));

        if ($intento->pedido_id) {
            return redirect()->route('web.pedidos.confirmacion', $intento->pedido_id);
        }

        return redirect()->route('web.carrito.index');
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
        $usuarioId = auth()->user()->resolveGalyUserId();
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

        $intento = $this->mercadoPago->reabrirCheckout($orden, auth()->user()->email);

        if ($intento->estado === IntentoPagoMercadoPago::ESTADO_FALLIDO) {
            return redirect()->route('web.pedidos.confirmacion', $orden->id)
                ->with('mensaje', 'Mercado Pago no respondió. Espera unos minutos e inténtalo de nuevo.')
                ->with('icono', 'error');
        }

        return redirect()->away($intento->checkout_url);
    }
}
