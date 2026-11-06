<?php

namespace App\Http\Controllers;

use App\Services\Galy\GalyApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class GalyAdminLinkController extends Controller
{
    public function __invoke(Request $request, GalyApiClient $api): RedirectResponse
    {
        $user = auth()->user();
        if (! $user?->galy_es_admin || ! $user->galy_user_id) {
            abort(403);
        }

        // El enlace se firma siempre con la cuenta GALY vinculada a la sesión web
        // actual: esta pantalla no abre el panel con otras cuentas administradoras.
        $destino = (int) $user->galy_user_id;
        if ($destino <= 0) {
            abort(404);
        }

        $email = (string) $user->email;

        try {
            if (Schema::connection('galy')->hasTable('usuarios')) {
                $cuenta = DB::connection('galy')
                    ->table('usuarios')
                    ->where('id', $destino)
                    ->first(['email', 'es_admin', 'estado']);

                if (
                    $cuenta !== null
                    && (int) $cuenta->es_admin === 1
                    && (int) $cuenta->estado === 1
                    && is_string($cuenta->email)
                    && $cuenta->email !== ''
                ) {
                    // El panel Angular valida que el id y el correo pertenezcan al
                    // mismo administrador, así que se usa el correo de la app.
                    $email = $cuenta->email;
                }
            }
        } catch (RuntimeException $exception) {
            return $this->falla($exception->getMessage());
        } catch (\Throwable $exception) {
            report($exception);

            return $this->falla('No se pudo leer tu cuenta de GALY en la base compartida.');
        }

        try {
            return redirect()->away($api->createAdminWebLink($destino, $email));
        } catch (RuntimeException $exception) {
            report($exception);

            return $this->falla($exception->getMessage());
        }
    }

    private function falla(string $mensaje): RedirectResponse
    {
        return redirect()->route('admin.app-galy.administradores')
            ->withErrors(['galy' => $mensaje]);
    }
}
