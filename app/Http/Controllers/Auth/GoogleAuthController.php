<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use GuzzleHttp\Exception\TransferException;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Spatie\Permission\Models\Role;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        if (! $this->isConfigured()) {
            return $this->loginRedirect('El acceso con Google aún no está configurado.');
        }

        return Socialite::driver('google')
            ->scopes(['openid', 'email', 'profile'])
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    public function link(Request $request): RedirectResponse
    {
        if ($this->isAdministrator($request->user())) {
            return $this->checkoutRedirect('Los administradores deben iniciar sesión con su correo y contraseña.');
        }

        if (! $this->isConfigured()) {
            return $this->checkoutRedirect('El acceso con Google aún no está configurado.');
        }

        $user = $request->user();
        $request->session()->put('google_link_user_id', $user->getKey());

        return Socialite::driver('google')
            ->scopes(['openid', 'email', 'profile'])
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        if (Auth::check() && ! $request->session()->has('google_link_user_id')) {
            return redirect()->intended('/dashboard');
        }

        if ($request->filled('error')) {
            return $this->oauthErrorRedirect($request, 'Se canceló el acceso con Google. Puedes intentarlo nuevamente.');
        }

        if (! $this->isConfigured()) {
            return $this->oauthErrorRedirect($request, 'El acceso con Google aún no está configurado.');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException $exception) {
            return $this->oauthErrorRedirect($request, 'No se pudo validar la sesión de Google. Inténtalo nuevamente.');
        } catch (TransferException $exception) {
            Log::warning('Google OAuth provider request failed.', [
                'exception_class' => $exception::class,
            ]);

            return $this->oauthErrorRedirect($request, 'No se pudo conectar de forma segura con Google. Revisa los certificados SSL del servidor e inténtalo nuevamente.');
        }

        $email = strtolower(trim((string) $googleUser->getEmail()));
        $googleId = trim((string) $googleUser->getId());
        $isEmailVerified = filter_var(
            (string) ($googleUser->user['verified_email'] ?? false),
            FILTER_VALIDATE_BOOLEAN
        );

        if (
            ! filter_var($email, FILTER_VALIDATE_EMAIL)
            || strlen($email) > 100
            || $googleId === ''
            || ! $isEmailVerified
        ) {
            return $this->oauthErrorRedirect($request, 'Google debe confirmar un correo válido antes de iniciar sesión.');
        }

        if (! Schema::connection('galy')->hasTable('usuarios')) {
            return $this->oauthErrorRedirect($request, 'No se pudo conectar con el registro de cuentas GALY.');
        }

        $linkedGoogleUser = User::where('google_id', $googleId)->first();
        $linkingUserId = $request->session()->get('google_link_user_id');
        if ($linkingUserId !== null) {
            $user = $request->user();
            if (! $user || (int) $user->getKey() !== (int) $linkingUserId
                || strtolower(trim((string) $user->email)) !== $email) {
                return $this->oauthErrorRedirect($request, 'La cuenta de Google debe usar el mismo correo de tu cuenta actual.');
            }
            if ($linkedGoogleUser && (int) $linkedGoogleUser->getKey() !== (int) $user->getKey()) {
                return $this->oauthErrorRedirect($request, 'Esta cuenta de Google ya está vinculada a otro perfil.');
            }
        } else {
            $user = $linkedGoogleUser ?? User::whereRaw('LOWER(email) = ?', [$email])->first();
        }

        $galyColumns = Schema::connection('galy')->getColumnListing('usuarios');
        $galyUser = DB::connection('galy')
            ->table('usuarios')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first(in_array('es_admin', $galyColumns, true)
                ? ['id', 'nombre', 'email', 'es_admin']
                : ['id', 'nombre', 'email']);

        if (! $galyUser) {
            $name = trim((string) $googleUser->getName());
            if ($name === '') {
                $name = strstr($email, '@', true) ?: $email;
            }

            $newGalyUser = [
                'nombre' => Str::limit($name, 100, ''),
                'email' => $email,
                'password' => Hash::make(Str::random(64)),
            ];
            foreach ([
                'es_admin' => false,
                'estado' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ] as $column => $value) {
                if (in_array($column, $galyColumns, true)) {
                    $newGalyUser[$column] = $value;
                }
            }

            try {
                DB::connection('galy')->table('usuarios')->insert($newGalyUser);
            } catch (QueryException $exception) {
                $galyUser = DB::connection('galy')
                    ->table('usuarios')
                    ->whereRaw('LOWER(email) = ?', [$email])
                    ->first(in_array('es_admin', $galyColumns, true)
                        ? ['id', 'nombre', 'email', 'es_admin']
                        : ['id', 'nombre', 'email']);

                if (! $galyUser) {
                    report($exception);

                    return $this->oauthErrorRedirect($request, 'No se pudo crear tu cuenta cliente GALY. Inténtalo nuevamente.');
                }
            }

            $galyUser ??= DB::connection('galy')
                ->table('usuarios')
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first(in_array('es_admin', $galyColumns, true)
                    ? ['id', 'nombre', 'email', 'es_admin']
                    : ['id', 'nombre', 'email']);
        }

        if (! $galyUser) {
            return $this->oauthErrorRedirect($request, 'No se pudo crear tu cuenta cliente GALY. Inténtalo nuevamente.');
        }

        if ($user && $user->google_id !== null && $user->google_id !== $googleId) {
            return $this->oauthErrorRedirect($request, 'Esta cuenta ya está vinculada a otro perfil de Google.');
        }

        if ($user && $user->galy_user_id !== null && (int) $user->galy_user_id !== (int) $galyUser->id) {
            return $this->oauthErrorRedirect($request, 'Esta cuenta ya está vinculada a otro perfil GALY.');
        }

        $isAdministrator = (bool) ($galyUser->es_admin ?? false)
            || $this->isAdministrator($user);

        $user = DB::transaction(function () use ($email, $googleId, $galyUser, $isAdministrator, $user): User {
            $user ??= new User;
            $name = trim((string) $galyUser->nombre);
            if ($name === '') {
                $name = (string) ($user->name ?: $email);
            }
            $user->forceFill([
                'name' => $name,
                'email' => $email,
                'google_id' => $googleId,
                'email_verified_at' => $user->email_verified_at ?? now(),
                'galy_user_id' => (int) $galyUser->id,
                'galy_es_admin' => $isAdministrator,
                'estado' => true,
            ]);
            if (! $user->exists) {
                $user->password = Hash::make(Str::random(64));
            }
            $user->save();

            if (! $isAdministrator && ! $user->hasAnyRole(['CLIENTE'])) {
                $user->assignRole(Role::firstOrCreate(['name' => 'CLIENTE']));
            }

            return $user;
        });

        Auth::login($user, false);
        $request->session()->regenerate();
        $request->session()->put('galy_user_id', (int) $galyUser->id);
        $request->session()->put('google_customer_mode', true);
        $request->session()->forget([
            'google_link_user_id',
            'galy_admin_token',
            'galy_admin_email',
        ]);

        return redirect()->intended('/dashboard');
    }

    private function isAdministrator(?User $user): bool
    {
        return $user !== null && (
            (bool) $user->galy_es_admin
            || $user->hasAnyRole(['SUPER ADMIN', 'ADMINISTRADOR'])
        );
    }

    private function isConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }

    private function loginRedirect(string $message): RedirectResponse
    {
        return redirect()->route('web.login')->with('google_login_error', $message);
    }

    private function checkoutRedirect(string $message): RedirectResponse
    {
        return redirect()->route('web.carrito.index')
            ->with('mensaje', $message)
            ->with('icono', 'warning');
    }

    private function oauthErrorRedirect(Request $request, string $message): RedirectResponse
    {
        if ($request->session()->pull('google_link_user_id') !== null) {
            return $this->checkoutRedirect($message);
        }

        return $this->loginRedirect($message);
    }
}
