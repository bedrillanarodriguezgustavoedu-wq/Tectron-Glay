<?php

namespace App\Http\Controllers;

use App\Models\ProductoImagen;
use App\Services\ReservaProductoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ReservaProductoController extends Controller
{
    public function index(ReservaProductoService $reservations)
    {
        $this->ensureReservationsTableExists();
        $reservations->expireDueReservations();

        $items = DB::connection('galy')
            ->table('reservas_productos as reservation')
            ->leftJoin('usuarios as user', 'user.id', '=', 'reservation.usuario_id')
            ->join('productos as product', 'product.id', '=', 'reservation.producto_id')
            ->select([
                'reservation.*',
                'user.nombre as cliente_nombre',
                'user.email as cliente_email',
                'product.nombre as producto_nombre',
                'product.imagen_url',
            ])
            ->orderByDesc('reservation.id')
            ->paginate(25);

        return view('admin.reservas.index', [
            'reservations' => $items,
            'imagesUrl' => ProductoImagen::sharedImagesBaseUrl(),
        ]);
    }

    public function reserve(Request $request, ReservaProductoService $reservations): RedirectResponse
    {
        $validated = $request->validate([
            'producto_id' => ['required', 'integer', 'min:1'],
            'cantidad' => ['required', 'integer', 'min:1', 'max:10'],
        ]);
        $userId = Auth::user()->resolveGalyUserId();
        if (! $userId) {
            return redirect()->route('web.login')
                ->withErrors(['login_error' => 'Inicia sesión con una cuenta GALY vinculada para reservar productos.']);
        }

        try {
            $reservations->reserve($userId, (int) $validated['producto_id'], (int) $validated['cantidad']);
        } catch (ValidationException $exception) {
            return redirect()->back()->withErrors($exception->errors())->withInput();
        }

        return redirect()->route('web.reservas.index')
            ->with('mensaje', 'Producto apartado durante 24 horas.')
            ->with('icono', 'success');
    }

    public function mine(ReservaProductoService $reservations)
    {
        $userId = Auth::user()->resolveGalyUserId();
        if (! $userId) {
            return redirect()->route('web.login')
                ->withErrors(['login_error' => 'Inicia sesión con una cuenta GALY vinculada para consultar tus reservas.']);
        }

        return view('web.reservas', [
            'reservations' => $reservations->listForUser($userId),
            'imagesUrl' => ProductoImagen::sharedImagesBaseUrl(),
        ]);
    }

    public function cancelMine(int $id, ReservaProductoService $reservations): RedirectResponse
    {
        $userId = Auth::user()->resolveGalyUserId();
        abort_unless($userId, 403);
        if (! $reservations->cancel($id, $userId)) {
            return redirect()->route('web.reservas.index')
                ->with('mensaje', 'La reserva ya no está activa o venció.')
                ->with('icono', 'warning');
        }

        return redirect()->route('web.reservas.index')
            ->with('mensaje', 'Reserva cancelada; las unidades volvieron al inventario.')
            ->with('icono', 'success');
    }

    public function cancelAdmin(int $id, ReservaProductoService $reservations): RedirectResponse
    {
        abort_unless($reservations->cancel($id), 404);

        return redirect()->route('admin.reservas.index')
            ->with('mensaje', "La reserva #{$id} se canceló y el inventario fue liberado.")
            ->with('icono', 'success');
    }

    private function ensureReservationsTableExists(): void
    {
        if (! Schema::connection('galy')->hasTable('reservas_productos')) {
            abort(503, 'El módulo de reservas todavía no está instalado en la base compartida.');
        }
    }
}
