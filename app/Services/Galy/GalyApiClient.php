<?php

namespace App\Services\Galy;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class GalyApiClient
{
    public function login(string $email, string $password): array
    {
        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout(8)
                ->post(config('galy.api_url').'/login.php', [
                    'email' => $email,
                    'password' => $password,
                ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('No se pudo conectar con el servicio GALY. Verifica GALY_API_URL y el servidor PHP.', 0, $exception);
        }

        if ($response->status() === 401) {
            throw ValidationException::withMessages([
                'email' => 'El correo o la contraseña no son correctos.',
            ]);
        }

        if (! $response->successful() || ! $response->json('success')) {
            throw new RuntimeException('GALY no pudo validar la cuenta. Inténtalo de nuevo más tarde.');
        }

        $data = $response->json();
        if (
            ! is_array($data)
            || ! is_array($data['user'] ?? null)
            || (int) ($data['user']['id'] ?? 0) <= 0
            || ! is_string($data['access_token'] ?? null)
        ) {
            throw new RuntimeException('GALY devolvió una respuesta de inicio de sesión incompleta.');
        }

        return $data;
    }

    public function register(string $name, string $email, string $password): array
    {
        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout(8)
                ->post(config('galy.api_url').'/register.php', [
                    'nombre' => $name,
                    'email' => $email,
                    'password' => $password,
                ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('No se pudo conectar con GALY para crear la cuenta.', 0, $exception);
        }

        if (! $response->successful() || ! $response->json('success')) {
            $message = mb_strtolower((string) $response->json('message'));
            if ($response->status() === 409 || str_contains($message, 'ya está registrado')) {
                throw ValidationException::withMessages([
                    'email' => 'Ese correo ya está registrado en GALY.',
                ]);
            }

            throw new RuntimeException('GALY no pudo crear la cuenta. Inténtalo de nuevo más tarde.');
        }

        return $response->json();
    }

    /**
     * Crea el enlace de acceso al panel Angular de GALY usando el inicio de
     * sesión SSO firmado con el secreto compartido. No depende del token
     * legacy de `galy_api`, por lo que también funciona cuando la API PHP no
     * emite `admin_token`.
     */
    public function createAdminWebLink(int $userId, string $email): string
    {
        $secret = (string) config('galy.sso_secret', '');
        if ($secret === '') {
            throw new RuntimeException('Falta configurar GALY_SSO_SECRET para abrir el administrador GALY.');
        }

        $email = strtolower(trim($email));
        $expira = now()->addMinutes(2)->timestamp;
        $payload = $userId.'|'.$email.'|'.$expira;
        $firma = hash_hmac('sha256', $payload, $secret);

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout(8)
                ->post(config('galy.admin_api_url').'/auth/web-sso', [
                    'user_id' => $userId,
                    'email' => $email,
                    'exp' => $expira,
                    'signature' => $firma,
                ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('No se pudo conectar con el panel GALY.', 0, $exception);
        }

        if (! $response->successful() || ! $response->json('success')) {
            throw new RuntimeException(
                (string) ($response->json('message') ?: 'GALY rechazó la solicitud del enlace administrativo.')
            );
        }

        $token = $response->json('token') ?: $response->json('admin_token');
        if (! is_string($token) || $token === '') {
            throw new RuntimeException('GALY devolvió un enlace administrativo no válido.');
        }

        return config('galy.public_url').'/login?sso_token='.rawurlencode($token);
    }

    /**
     * Envía una notificación push a todos los dispositivos de la app GALY.
     * Devuelve el mensaje que muestra el endpoint (incluye el número de
     * dispositivos alcanzados).
     */
    public function sendPush(string $titulo, string $mensaje): string
    {
        try {
            $request = Http::acceptJson()
                ->asJson()
                ->timeout(15);
            $apiKey = (string) config('galy.api_key', '');
            if ($apiKey !== '') {
                $request = $request->withHeader('X-Galy-Key', $apiKey);
            }
            $response = $request->post(config('galy.api_url').'/send_push.php', [
                'titulo' => $titulo,
                'mensaje' => $mensaje,
            ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('No se pudo conectar con GALY para enviar la notificación.', 0, $exception);
        }

        if (! $response->successful() || ! $response->json('success')) {
            throw new RuntimeException(
                (string) ($response->json('message') ?: 'GALY no pudo enviar la notificación.')
            );
        }

        return (string) ($response->json('message') ?: 'Notificación enviada a los dispositivos de la app GALY.');
    }

    public function updateTechnicalTicket(int $id, string $status, float $cost): void
    {
        try {
            $request = Http::acceptJson()
                ->asJson()
                ->timeout(8);
            $apiKey = (string) config('galy.api_key', '');
            if ($apiKey !== '') {
                $request = $request->withHeader('X-Galy-Key', $apiKey);
            }
            $response = $request->post(config('galy.api_url').'/update_ticket_status.php', [
                'id' => $id,
                'estado' => $status,
                'costo' => $cost,
            ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('No se pudo conectar con GALY para actualizar el ticket.', 0, $exception);
        }

        if (! $response->successful() || ! $response->json('success')) {
            throw new RuntimeException(
                (string) ($response->json('message') ?: 'GALY no pudo actualizar el ticket. Inténtalo de nuevo.')
            );
        }
    }
}
