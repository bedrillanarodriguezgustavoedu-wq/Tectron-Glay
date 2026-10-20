<?php

namespace App\Http\Controllers;

use App\Services\Galy\GalyApiClient;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class GalyAdminLinkController extends Controller
{
    public function __invoke(GalyApiClient $api): RedirectResponse
    {
        $user = auth()->user();
        if (! $user?->galy_es_admin || ! $user->galy_user_id) {
            abort(403);
        }

        $token = (string) session('galy_admin_token', '');
        if ($token === '') {
            return redirect()->route('web.login')
                ->withErrors(['login_error' => 'Vuelve a iniciar sesión con tu cuenta administradora GALY.']);
        }

        try {
            return redirect()->away($api->createAdminWebLink($token));
        } catch (RuntimeException $exception) {
            report($exception);

            return redirect()->route('admin.index')
                ->withErrors(['galy' => $exception->getMessage()]);
        }
    }
}
