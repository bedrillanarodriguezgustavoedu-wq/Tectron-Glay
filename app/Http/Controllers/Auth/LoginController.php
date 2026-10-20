<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Galy\GalyAccountBridge;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    public function login(Request $request, GalyAccountBridge $accounts, ?string $redirectTo = null)
    {
        $request->validate([
            $this->username() => 'required|string',
            'password' => 'required|string',
        ]);

        $email = strtolower(trim((string) $request->input($this->username())));
        $password = (string) $request->input('password');
        $galyOnly = $request->input('galy') === 'connect';
        $localUser = User::whereRaw('LOWER(email) = ?', [$email])->first();
        $finalRedirect = $redirectTo ?? $this->redirectPath();
        $matchesLocalPassword = false;
        $storedPassword = '';

        if ($localUser) {
            $storedPassword = (string) $localUser->password;

            if ($storedPassword !== '' && password_get_info($storedPassword)['algo'] !== 0) {
                try {
                    $matchesLocalPassword = Hash::check($password, $storedPassword);
                } catch (RuntimeException) {
                    $matchesLocalPassword = false;
                }
            } elseif ($storedPassword === $password) {
                $matchesLocalPassword = true;
            }

        }

        $isLocalAdmin = $localUser && (
            $localUser->galy_es_admin
            || $localUser->galy_user_id !== null
            || $localUser->hasRole('SUPER ADMIN')
            || $localUser->hasRole('ADMINISTRADOR')
        );

        try {
            $authenticatedUser = $accounts->authenticate(
                $email,
                $password,
                $request->boolean('remember')
            );
        } catch (ValidationException $exception) {
            if ($galyOnly || ! $matchesLocalPassword || $localUser?->galy_user_id !== null) {
                throw $exception;
            }
        } catch (RuntimeException $exception) {
            if ($galyOnly || ! $matchesLocalPassword) {
                return back()
                    ->withInput($request->only($this->username()))
                    ->withErrors([$this->username() => $exception->getMessage()]);
            }
        }

        if ($matchesLocalPassword && $localUser) {
            return $this->loginLocally(
                $request,
                $localUser,
                $storedPassword,
                $password,
                $this->redirectPathForUser($localUser, $finalRedirect)
            );
        }

        return redirect()->intended($this->redirectPathForUser($authenticatedUser, $finalRedirect));
    }

    private function redirectPathForUser(User $user, string $redirectTo): string
    {
        return $user->hasAnyRole(['SUPER ADMIN', 'ADMINISTRADOR']) ? '/home' : $redirectTo;
    }

    private function loginLocally(
        Request $request,
        User $user,
        string $storedPassword,
        string $password,
        string $redirectTo
    ) {
        if ($storedPassword === $password) {
            $user->password = Hash::make($password);
            $user->save();
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended($this->redirectPathForUser($user, $redirectTo));
    }

    public function logout(Request $request)
    {
        $request->session()->forget([
            'galy_user_id',
            'galy_access_token',
            'galy_admin_token',
            'galy_admin_email',
        ]);
        $this->guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }
}
