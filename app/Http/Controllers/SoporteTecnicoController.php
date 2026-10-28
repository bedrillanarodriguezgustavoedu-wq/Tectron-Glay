<?php

namespace App\Http\Controllers;

use App\Models\ProductoImagen;
use App\Services\Galy\GalyApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use RuntimeException;

class SoporteTecnicoController extends Controller
{
    private const STATUSES = [
        'Recibido',
        'En Diagnostico',
        'Reparando',
        'Listo',
        'Entregado',
    ];

    public function index()
    {
        $this->ensureTicketsTableExists();

        $tickets = DB::connection('galy')
            ->table('servicios_tecnicos as ticket')
            ->leftJoin('usuarios as user', 'ticket.usuario_id', '=', 'user.id')
            ->select([
                'ticket.id',
                'ticket.usuario_id',
                'ticket.equipo_modelo',
                'ticket.descripcion_falla',
                'ticket.estado',
                'ticket.costo_estimado',
                'ticket.imagen_ingreso',
                'ticket.imagen_listo',
                'ticket.fecha_ingreso',
                'user.nombre as cliente_nombre',
            ])
            ->orderByDesc('ticket.id')
            ->paginate(25);

        return view('admin.soporte.index', [
            'tickets' => $tickets,
            'statuses' => self::STATUSES,
            'imagesUrl' => ProductoImagen::sharedImagesBaseUrl(),
        ]);
    }

    public function update(Request $request, int $id, GalyApiClient $api): RedirectResponse
    {
        $this->ensureTicketsTableExists();
        abort_unless(
            DB::connection('galy')->table('servicios_tecnicos')->where('id', $id)->exists(),
            404
        );

        $validated = $request->validate([
            'estado' => ['required', 'string', Rule::in(self::STATUSES)],
            'costo' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ]);

        try {
            $api->updateTechnicalTicket($id, $validated['estado'], (float) $validated['costo']);
        } catch (RuntimeException $exception) {
            report($exception);

            return redirect()->route('admin.soporte.index')
                ->withInput()
                ->withErrors(['soporte' => $exception->getMessage()]);
        }

        return redirect()->route('admin.soporte.index')
            ->with('mensaje', "El ticket #{$id} se actualizó. GALY notificará al cliente si tiene notificaciones activas.")
            ->with('icono', 'success');
    }

    private function ensureTicketsTableExists(): void
    {
        if (! Schema::connection('galy')->hasTable('servicios_tecnicos')) {
            throw new RuntimeException('La tabla compartida de tickets servicios_tecnicos no existe en la base GALY.');
        }
    }
}
