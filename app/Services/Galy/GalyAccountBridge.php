<?php

namespace App\Services\Galy;

use App\Models\User;
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
        $this->api->register($name, $email, $password);

        return $this->authenticate($email, $password, true);
    }

    public function refreshCanonicalProfile(User $user, string $name, string $email): void
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

        $updated = $connection->table('usuarios')
            ->where('id', $galyUserId)
            ->update(['nombre' => $name, 'email' => $email]);

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
