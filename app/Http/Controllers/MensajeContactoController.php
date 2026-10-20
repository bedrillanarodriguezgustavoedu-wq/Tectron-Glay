<?php

namespace App\Http\Controllers;

use App\Models\MensajeContacto;
use Illuminate\Http\Request;

class MensajeContactoController extends Controller
{
    public function index(Request $request)
    {
        $buscar = $request->get('buscar');
        $estado = $request->get('estado');

        $query = MensajeContacto::orderBy('created_at', 'desc');

        if ($buscar) {
            $query->where(function($q) use ($buscar) {
                $q->where('nombre', 'like', '%' . $buscar . '%')
                  ->orWhere('email', 'like', '%' . $buscar . '%')
                  ->orWhere('asunto', 'like', '%' . $buscar . '%')
                  ->orWhere('mensaje', 'like', '%' . $buscar . '%');
            });
        }

        if ($estado) {
            $query->where('estado', $estado);
        }

        $mensajes = $query->paginate(15);

        return view('admin.mensajes.index', compact('mensajes'));
    }

    public function show($id)
    {
        $mensaje = MensajeContacto::findOrFail($id);

        // Auto mark as Leído if it was Pendiente
        if ($mensaje->estado === 'Pendiente') {
            $mensaje->estado = 'Leído';
            $mensaje->save();
        }

        return view('admin.mensajes.show', compact('mensaje'));
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'estado' => 'required|in:Pendiente,Leído,Respondido',
        ]);

        $mensaje = MensajeContacto::findOrFail($id);
        $mensaje->estado = $request->estado;
        $mensaje->save();

        return redirect()->back()
            ->with('mensaje', 'Estado del mensaje actualizado correctamente.')
            ->with('icono', 'success');
    }

    public function destroy($id)
    {
        $mensaje = MensajeContacto::findOrFail($id);
        $mensaje->delete();

        return redirect()->route('admin.mensajes.index')
            ->with('mensaje', 'Mensaje eliminado correctamente.')
            ->with('icono', 'success');
    }
}
