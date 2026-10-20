<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Galy\GalyAccountBridge;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\ResetsPasswords;
use RuntimeException;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords {
        reset as protected resetUsingTrait;
        setUserPassword as protected setPasswordUsingTrait;
    }

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    public function reset(Request $request)
    {
        try {
            return $this->resetUsingTrait($request);
        } catch (RuntimeException $exception) {
            report($exception);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'No se pudo actualizar la contraseña compartida con GALY. Inténtalo de nuevo.']);
        }
    }

    protected function setUserPassword($user, $password)
    {
        if ($user instanceof User && $user->resolveGalyUserId() !== null) {
            app(GalyAccountBridge::class)->changePassword($user, $password);

            return;
        }

        $this->setPasswordUsingTrait($user, $password);
    }
}
