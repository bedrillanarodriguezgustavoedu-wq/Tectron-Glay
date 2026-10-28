<?php

namespace App\Services\Galy;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Spatie\Permission\Models\Role;

class GalyAccountBridge
{
    public function __construct(private readonly GalyApiClient $api) {}

    public function authenticate(string $email, string $password, bool $remember = false): User
    {
        $response = $this->api->login($email, $password);
        $userData = $response['user'];
        $galyUserId = (int) $userData['id'];
        $canonicalEmail = strtolower(trim((string) ($userData['email'] ?? $email)));
        $isAdmin = (bool) ($userData['es_admin'] ?? false);

        $user = DB::transaction(function () use ($canonicalEmail, $galyUserId, $isAdmin, $password, $userData) {
            $user = User::where('galy_user_id', $galyUserId)->first();
            $wasLinkedToThisGalyAccount = $user !== null;
            if (! $user) {
                $user = User::whereRaw('LOWER(email) = ?', [$canonicalEmail])->first();
            }

            if ($user && $user->galy_user_id && (int) $user->galy_user_id !== $galyUserId) {
                throw new RuntimeException('Ese correo está vinculado a otra cuenta GALY. Contacta al administrador antes de continuar.');
            }

            if (! $user) {
                $user = new User;
                $user->email = $canonicalEmail;
            }

            $user->name = trim((string) ($userData['nombre'] ?? '')) ?: $canonicalEmail;
            $user->password = Hash::make($password);
            $user->galy_user_id = $galyUserId;
            $user->galy_es_admin = $isAdmin;
            $user->estado = true;
            $user->save();

            if ($isAdmin) {
                $adminRole = Role::firstOrCreate(['name' => 'SUPER ADMIN']);
                if (! $user->hasRole('SUPER ADMIN')) {
                    $user->assignRole($adminRole);
                }
            } else {
                if ($wasLinkedToThisGalyAccount) {
                    foreach (['SUPER ADMIN', 'ADMINISTRADOR'] as $roleName) {
                        if ($user->hasRole($roleName)) {
                            $user->removeRole($roleName);
                        }
                    }
                }

                if (! $user->hasAnyRole(['SUPER ADMIN', 'ADMINISTRADOR', 'CLIENTE'])) {
                    $clientRole = Role::firstOrCreate(['name' => 'CLIENTE']);
                    $user->assignRole($clientRole);
                }
            }

            return $user;
        });

        Auth::login($user, $remember);
        request()->session()->regenerate();
        request()->session()->put('galy_user_id', $galyUserId);
        request()->session()->put('galy_access_token', $response['access_token']);
        request()->session()->put('galy_admin_token', $response['admin_token'] ?? null);
        request()->session()->put('galy_admin_email', $isAdmin ? $canonicalEmail : null);

        return $user;
    }

    public function register(string $name, string $email, string $password): User
    {
        try {
            $this->api->register($name, $email, $password);
        } catch (RuntimeException $exception) {
            if (! $this->puedeCrearCuentaLocal($exception)) {
                throw $exception;
            }

            return $this->registrarCuentaLocal($name, $email, $password);
        }

        return $this->authenticate($email, $password, true);
    }

    /**
     * Solo cuando el panel GALY está inalcanzable. Si GALY respondió y rechazó el
     * registro (un correo repetido, por ejemplo), crear la cuenta aquí duplicaría una
     * identidad, así que ese caso sigue fallando igual que antes.
     */
    private function puedeCrearCuentaLocal(RuntimeException $exception): bool
    {
        return config('galy.registro_local') === true
            && $exception->getPrevious() instanceof ConnectionException;
    }

    private function registrarCuentaLocal(string $name, string $email, string $password): User
    {
        $email = strtolower(trim($email));
        $nombre = trim($name);

        if (User::whereRaw('LOWER(email) = ?', [$email])->exists()) {
            throw new RuntimeException('Ese correo ya tiene una cuenta en la tienda.');
        }

        // `usuarios` es la tabla que llena register.php y la que consulta
        // resolveGalyUserId(): sin su fila la cuenta existiría pero el carrito no
        // funcionaría. `Hash::make` cifra bcrypt, igual que password_hash de la API.
        $hash = Hash::make($password);

        return DB::connection('galy')->transaction(function () use ($nombre, $email, $hash) {
            if (DB::connection('galy')->table('usuarios')
                ->whereRaw('LOWER(email) = ?', [$email])
                ->exists()
            ) {
                throw new RuntimeException('Ese correo ya está asociado a una cuenta GALY.');
            }

            $galyUserId = (int) DB::connection('galy')->table('usuarios')->insertGetId([
                'nombre' => $nombre,
                'email' => $email,
                'password' => $hash,
            ]);

            $cuenta = new User;
            $cuenta->name = $nombre;
            $cuenta->email = $email;
            $cuenta->password = $hash;
            $cuenta->galy_user_id = $galyUserId;
            $cuenta->estado = true;
            $cuenta->save();

            if (! $cuenta->hasAnyRole(['SUPER ADMIN', 'ADMINISTRADOR', 'CLIENTE'])) {
                $cuenta->assignRole(Role::firstOrCreate(['name' => 'CLIENTE']));
            }

            Auth::login($cuenta, true);
            request()->session()->regenerate();
            request()->session()->put('galy_user_id', $galyUserId);

            return $cuenta;
        });
    }

    public function refreshCanonicalProfile(User $user, string $name, string $email, ?string $telefono = null): void
    {
        $galyUserId = (int) $user->galy_user_id;
        if ($galyUserId <= 0) {
            throw new RuntimeException('La cuenta Tectron no está vinculada a un usuario GALY.');
        }

        $connection = DB::connection('galy');
        $email = strtolower(trim($email));
        if ($connection->table('usuarios')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('id', '!=', $galyUserId)
            ->exists()
        ) {
            throw new RuntimeException('Ese correo ya está asociado a otra cuenta GALY.');
        }

        $datos = ['nombre' => $name, 'email' => $email];

        if ($telefono !== null) {
            // `usuarios.telefono` es la columna que lee la app Android: si solo se
            // guardara en `users`, el repartidor vería un número y la app otro.
            $datos['telefono'] = $telefono;
            $user->telefono = $telefono;
        }

        $updated = $connection->table('usuarios')
            ->where('id', $galyUserId)
            ->update($datos);

        if ($updated === 0 && ! $connection->table('usuarios')->where('id', $galyUserId)->exists()) {
            throw new RuntimeException('No se encontró la cuenta GALY vinculada.');
        }

        $user->name = $name;
        $user->email = strtolower(trim($email));
        $user->save();
        request()->session()->forget(['galy_access_token', 'galy_admin_token']);
    }

    public function changePassword(User $user, string $password): void
    {
        $galyUserId = (int) $user->galy_user_id;
        if ($galyUserId <= 0) {
            throw new RuntimeException('La cuenta Tectron no está vinculada a un usuario GALY.');
        }

        $updated = DB::connection('galy')->table('usuarios')
            ->where('id', $galyUserId)
            ->update([
                'password' => Hash::make($password),
            ]);

        if ($updated !== 1) {
            throw new RuntimeException('No se pudo actualizar la contraseña central GALY.');
        }

        $user->password = $password;
        $user->save();
        request()->session()->forget(['galy_access_token', 'galy_admin_token']);
    }
}
