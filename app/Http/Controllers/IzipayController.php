<?php

namespace App\Http\Controllers;

use App\Models\IntentoPagoIzipay;
use App\Models\Orden;
use App\Services\IzipayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Pago con tarjeta a través de Izipay.
 *
 * El pedido se crea en `OrdenController::crearPedidoPagoManual`; aquí solo se
 * muestra el formulario embebido, se valida la firma de la respuesta y se
 * confirma el pedido. La tienda nunca ve el número ni el CVV de la tarjeta.
 */
class IzipayController extends Controller
{
    public function __construct(private IzipayService $izipay)
    {
    }

    public function formulario(IntentoPagoIzipay $intento)
    {
        $usuarioId = auth()->user()?->resolveGalyUserId();
        abort_if($usuarioId === null, 403);
        abort_if((int) $intento->usuario_id !== (int) $usuarioId, 404);

        $orden = $intento->orden;
        abort_if($orden === null, 404);

        if ($orden->estado_pago === 'Pagado') {
            return redirect()->route('web.pedidos.confirmacion', $orden->id)
                ->with('mensaje', 'Este pedido ya está pagado. No necesitas pagar otra vez.')
                ->with('icono', 'success');
        }

        if (! $this->izipay->sePuedeOfrecer() || ! filled($intento->form_token)) {
            return redirect()->route('web.pedidos.confirmacion', $orden->id)
                ->with('mensaje', 'El pago con tarjeta no está disponible por ahora. Tu pedido sigue guardado: elige otro método o inténtalo más tarde.')
                ->with('icono', 'warning');
        }

        return view('web.izipay_form', [
            'intento' => $intento,
            'orden' => $orden,
            'publicKey' => $this->izipay->publicKey(),
            'formToken' => $intento->form_token,
            'scriptUrl' => $this->izipay->scriptUrl(),
            'urlRetorno' => $this->izipay->urlRetorno(),
        ]);
    }

    /**
     * Izipay devuelve al cliente con un POST firmado. Si la firma no cuadra no
     * se toca el pedido: ese mensaje pudo fabricarlo cualquiera.
     */
    public function retorno(Request $request)
    {
        $respuesta = $this->izipay->respuestaVerificada($request->all());

        if ($respuesta === null) {
            Log::warning('Izipay devolvió un pago con firma inválida o ausente.', [
                'ip' => $request->ip(),
            ]);

            abort(403, 'La firma del pago no es válida.');
        }

        $referencia = (string) ($respuesta['orderDetails']['orderId'] ?? '');
        $intento = $referencia !== ''
            ? IntentoPagoIzipay::where('referencia', $referencia)->first()
            : null;

        abort_if($intento === null, 404);

        $this->izipay->aplicarRespuesta($intento, $respuesta, 'retorno');
        $intento->refresh();

        $destino = $intento->pedido_id
            ? route('web.pedidos.confirmacion', $intento->pedido_id)
            : route('web.dashboard');

        return match ($intento->estado) {
            IntentoPagoIzipay::ESTADO_CONFIRMADO => redirect($destino)
                ->with('mensaje', 'Pago confirmado con Izipay. Tu pedido ya está en proceso.')
                ->with('icono', 'success'),
            IntentoPagoIzipay::ESTADO_FALLIDO, IntentoPagoIzipay::ESTADO_EXPIRADO => redirect($destino)
                ->with('mensaje', 'El pago no se completó y no se te cobró nada. Puedes intentarlo otra vez desde tu pedido.')
                ->with('icono', 'warning'),
            default => redirect($destino)
                ->with('mensaje', 'Estamos confirmando tu pago con Izipay. No repitas el cobro: el pedido se actualiza solo.')
                ->with('icono', 'info'),
        };
    }

    /**
     * Notificación servidor a servidor. La URL se configura en el Back Office
     * de Izipay (Configuración → Reglas de notificación).
     */
    public function ipn(Request $request)
    {
        $respuesta = $this->izipay->respuestaVerificada($request->all());

        if ($respuesta === null) {
            Log::warning('Izipay envió una IPN con firma inválida o ausente.', [
                'ip' => $request->ip(),
            ]);

            return response('Invalid signature', 403);
        }

        $referencia = (string) ($respuesta['orderDetails']['orderId'] ?? '');
        $intento = $referencia !== ''
            ? IntentoPagoIzipay::where('referencia', $referencia)->first()
            : null;

        if ($intento === null) {
            Log::warning('Izipay notificó un pago de una referencia desconocida.', [
                'referencia' => $referencia,
            ]);

            return response('Order not found', 404);
        }

        $this->izipay->aplicarRespuesta($intento, $respuesta, 'ipn');

        return response('OK', 200);
    }

    /**
     * Reabre el pago de un pedido pendiente sin crear otro pedido ni cobrar dos
     * veces. Solo el dueño del pedido puede hacerlo.
     */
    public function reintentar(Request $request, $id)
    {
        $usuarioId = auth()->user()?->resolveGalyUserId();
        abort_if($usuarioId === null, 403);

        $orden = Orden::where('usuario_id', $usuarioId)->findOrFail($id);

        if ($orden->estado_pago === 'Pagado') {
            return redirect()->route('web.pedidos.confirmacion', $orden->id)
                ->with('mensaje', 'Este pedido ya está pagado.')
                ->with('icono', 'success');
        }

        if (! $this->izipay->sePuedeOfrecer()) {
            return redirect()->route('web.pedidos.confirmacion', $orden->id)
                ->with('mensaje', 'El pago con tarjeta no está disponible por ahora.')
                ->with('icono', 'warning');
        }

        try {
            $intento = $this->izipay->reabrirParaOrden($orden, [
                'correo' => auth()->user()?->email,
                'nombre' => auth()->user()?->name,
                'dni' => auth()->user()?->dni,
                'telefono' => auth()->user()?->telefono,
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->route('web.pedidos.confirmacion', $orden->id)
                ->with('mensaje', 'No pudimos abrir el pago con tarjeta. Tu pedido sigue guardado: inténtalo otra vez en unos minutos.')
                ->with('icono', 'error');
        }

        return redirect()->route('web.pagos.izipay.form', $intento->id);
    }
}
