<?php

namespace App\Http\Controllers;

use App\Models\Ajuste;
use App\Models\Orden;
use App\Models\PagoManual;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class MobileCheckoutController extends Controller
{
    /**
     * Solo ofrece destinos de pago plausibles: un número de prueba o incompleto
     * se oculta igual que en la web, porque el cliente transfería y perdía su
     * pago. La app no usa QR, así que el QR no se exige aquí.
     */
    public function paymentMethods()
    {
        $settings = Ajuste::query()->first();
        $methods = [];

        foreach ([
            'YAPE' => ['method' => 'yape', 'number' => 'yape_numero', 'holder' => 'yape_titular', 'qr' => 'yape_qr'],
            'PLIN' => ['method' => 'plin', 'number' => 'plin_numero', 'holder' => 'plin_titular', 'qr' => 'plin_qr'],
        ] as $label => $fields) {
            $number = trim((string) ($settings?->{$fields['number']} ?? ''));
            $holder = trim((string) ($settings?->{$fields['holder']} ?? ''));

            if (Ajuste::motivoDeDestinoPago($fields['method'], $number, $holder, requiereQr: false) !== null) {
                continue;
            }

            $methods[] = [
                'method' => $label,
                'number' => $number,
                'holder' => $holder,
                'qr' => $this->publicStoragePath($settings?->{$fields['qr']}),
            ];
        }

        $bcpCci = trim((string) ($settings?->app_bcp_cci ?? ''));
        $bcpHolder = trim((string) ($settings?->app_bcp_titular ?? ''));
        if (Ajuste::motivoDeDestinoPago('bcp', $bcpCci, $bcpHolder, requiereQr: false) === null) {
            $methods[] = [
                'method' => 'BCP',
                'number' => $bcpCci,
                'account' => trim((string) ($settings?->app_bcp_cuenta ?? '')),
                'holder' => $bcpHolder,
            ];
        }

        return response()->json(['success' => true, 'methods' => $methods]);
    }

    /**
     * Ruta corta del QR, o null si el archivo no está. La app la une a su propia
     * BASE_URL, así que el QR se ve igual aunque el servidor cambie de host.
     */
    private function publicStoragePath(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '' || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return 'storage/'.$path;
    }

    public function sendPaymentReceipt(Request $request)
    {
        $validated = $request->validate([
            'id_token' => 'required|string|max:10000',
            'pedido_id' => 'required|integer|min:1',
            'metodo_pago' => 'required|string|in:YAPE,PLIN,BCP',
            'numero_operacion' => 'nullable|string|max:80',
            'comprobante' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        $userId = $this->verifiedGoogleCustomerId($validated['id_token']);
        $method = strtolower($validated['metodo_pago']);
        $order = Orden::query()->where('usuario_id', $userId)->findOrFail($validated['pedido_id']);

        abort_unless(
            strtolower((string) $order->metodo_pago) === $method,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'El método de pago no coincide con el pedido.'
        );

        abort_if(
            in_array(strtolower((string) $order->estado_pago), ['pagado', 'completed', 'approved'], true),
            Response::HTTP_CONFLICT,
            'El pago de este pedido ya fue confirmado.'
        );

        $previous = PagoManual::query()->where('pedido_id', $order->id)->first();
        abort_if(
            $previous && ((int) $previous->usuario_id !== $userId || $previous->metodo_pago !== $method),
            Response::HTTP_CONFLICT,
            'El comprobante no corresponde a este pedido.'
        );
        abort_if(
            $previous?->estado === 'confirmado',
            Response::HTTP_CONFLICT,
            'El comprobante de este pedido ya fue confirmado.'
        );

        $settings = Ajuste::query()->first();
        [$methodKey, $numberField, $holderField] = match ($method) {
            'yape' => ['yape', 'yape_numero', 'yape_titular'],
            'plin' => ['plin', 'plin_numero', 'plin_titular'],
            'bcp' => ['bcp', 'app_bcp_cci', 'app_bcp_titular'],
        };
        // El mismo filtro que ofrece los métodos: un destino de prueba no puede
        // recibir un comprobante porque nadie pudo transferirle de verdad.
        abort_if(
            Ajuste::motivoDeDestinoPago(
                $methodKey,
                $settings?->{$numberField},
                $settings?->{$holderField},
                requiereQr: false
            ) !== null,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'Este método de pago no está configurado por la tienda.'
        );

        $previousPath = $previous?->comprobante_path;
        $path = $request->file('comprobante')->store('comprobantes-pago', 'local');
        if (! $path) {
            throw new \RuntimeException('No se pudo guardar el comprobante de pago.');
        }

        $payment = $previous ?? new PagoManual;
        $payment->pedido_id = $order->id;
        $payment->usuario_id = $userId;
        $payment->metodo_pago = $method;
        $payment->numero_operacion = filled($validated['numero_operacion'] ?? null)
            ? trim($validated['numero_operacion'])
            : null;
        $payment->comprobante_path = $path;
        $payment->estado = 'comprobante_enviado';
        $payment->comprobante_enviado_at = now();
        $payment->save();

        if ($previousPath && Storage::disk('local')->exists($previousPath)) {
            Storage::disk('local')->delete($previousPath);
        }

        return response()->json([
            'success' => true,
            'message' => 'Comprobante enviado; el pago queda pendiente de revisión.',
        ]);
    }

    private function verifiedGoogleCustomerId(string $idToken): int
    {
        $clientId = trim((string) config('services.google.client_id'));
        abort_if($clientId === '', Response::HTTP_SERVICE_UNAVAILABLE, 'El inicio con Google no está configurado.');

        try {
            $response = Http::connectTimeout(5)
                ->timeout(10)
                ->get('https://oauth2.googleapis.com/tokeninfo', ['id_token' => $idToken]);
        } catch (ConnectionException) {
            abort(Response::HTTP_SERVICE_UNAVAILABLE, 'No se pudo validar la sesión con Google. Inténtalo nuevamente.');
        }

        $tokenInfo = $response->successful() ? $response->json() : null;
        $email = strtolower(trim((string) ($tokenInfo['email'] ?? '')));
        $issuer = (string) ($tokenInfo['iss'] ?? '');

        abort_unless(
            is_array($tokenInfo)
                && hash_equals($clientId, (string) ($tokenInfo['aud'] ?? ''))
                && in_array($issuer, ['accounts.google.com', 'https://accounts.google.com'], true)
                && (int) ($tokenInfo['exp'] ?? 0) > time()
                && filter_var($tokenInfo['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN)
                && filter_var($email, FILTER_VALIDATE_EMAIL),
            Response::HTTP_UNAUTHORIZED,
            'La sesión de Google no es válida o ha vencido.'
        );

        $columns = Schema::connection('galy')->getColumnListing('usuarios');
        $fields = in_array('es_admin', $columns, true) ? ['id', 'es_admin'] : ['id'];
        $user = DB::connection('galy')
            ->table('usuarios')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first($fields);

        abort_unless($user, Response::HTTP_FORBIDDEN, 'El correo no pertenece a una cuenta GALY registrada.');
        abort_if((bool) ($user->es_admin ?? false), Response::HTTP_FORBIDDEN, 'El checkout móvil solo está disponible para clientes.');

        return (int) $user->id;
    }
}
