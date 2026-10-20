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

    public function createAdminWebLink(string $adminToken): string
    {
        try {
            $response = Http::acceptJson()
                ->withHeader('X-Admin-Token', $adminToken)
                ->timeout(8)
                ->post(config('galy.api_url').'/create_admin_web_link.php');
        } catch (ConnectionException $exception) {
            throw new RuntimeException('No se pudo conectar con el panel GALY.', 0, $exception);
        }

        if (! $response->successful() || ! $response->json('success')) {
            throw new RuntimeException('GALY rechazó la solicitud del enlace administrativo.');
        }

        $path = $response->json('url');
        if (! is_string($path) || ! str_starts_with($path, 'admin_web.php?')) {
            throw new RuntimeException('GALY devolvió un enlace administrativo no válido.');
        }

        return config('galy.public_url').'/'.$path;
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
