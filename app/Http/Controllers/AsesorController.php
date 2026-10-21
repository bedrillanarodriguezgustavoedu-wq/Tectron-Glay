<?php

namespace App\Http\Controllers;

use App\Models\Asesor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AsesorController extends Controller
{
    public function index()
    {
        $asesores = Asesor::orderBy('activo')->orderBy('orden')->orderBy('nombre')->paginate(15);

        return view('admin.asesores.index', compact('asesores'));
    }

    public function create()
    {
        return view('admin.asesores.create');
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);

        Asesor::create($datos);

        return redirect()->route('admin.asesores.index')
            ->with('mensaje', 'Asesor creado exitosamente')
            ->with('icono', 'success');
    }

    public function edit($id)
    {
        return view('admin.asesores.edit', ['asesor' => Asesor::findOrFail($id)]);
    }

    public function update(Request $request, $id)
    {
        $asesor = Asesor::findOrFail($id);
        $datos = $this->validar($request, $asesor);

        $asesor->update($datos);

        return redirect()->route('admin.asesores.index')
            ->with('mensaje', 'Asesor actualizado exitosamente')
            ->with('icono', 'success');
    }

    public function destroy($id)
    {
        $asesor = Asesor::findOrFail($id);
        $asesor->activo = false;
        $asesor->save();

        return redirect()->route('admin.asesores.index')
            ->with('mensaje', "{$asesor->nombre} dejó de mostrarse en la tienda; el registro se conserva.")
            ->with('icono', 'success');
    }

    public function toggleStatus($id)
    {
        $asesor = Asesor::findOrFail($id);
        $asesor->activo = ! $asesor->activo;
        $asesor->save();

        return redirect()->route('admin.asesores.index')
            ->with('mensaje', $asesor->activo
                ? "{$asesor->nombre} vuelve a aparecer en la página de asesores."
                : "{$asesor->nombre} quedó oculto en la página de asesores.")
            ->with('icono', 'success');
    }

    private function validar(Request $request, ?Asesor $asesor = null): array
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:100',
            'cargo' => 'nullable|string|max:100',
            'whatsapp' => [
                'nullable', 'string', 'max:30',
                function ($atributo, $valor, $fallar) {
                    // Un enlace wa.me con un número incompleto abre un chat que no existe:
                    // preferimos no mostrar el botón antes que mostrarlo roto.
                    if (trim((string) $valor) !== '' && strlen(preg_replace('/\D+/', '', $valor)) < 8) {
                        $fallar('Ese número no tiene al menos 8 dígitos, así que el botón de WhatsApp no funcionaría.');
                    }
                },
            ],
            'email' => 'nullable|email|max:150',
            'horario' => 'nullable|string|max:100',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'orden' => 'nullable|integer|min:0|max:999',
            'activo' => 'nullable|boolean',
        ], [
            'whatsapp.max' => 'El número de WhatsApp no puede tener más de 30 caracteres.',
        ]);

        if ($request->hasFile('foto')) {
            if ($asesor?->foto && Storage::disk('public')->exists($asesor->foto)) {
                Storage::disk('public')->delete($asesor->foto);
            }
            $datos['foto'] = $request->file('foto')->store('asesores', 'public');
        }

        $datos['activo'] = $request->has('activo');
        $datos['orden'] = (int) ($datos['orden'] ?? 0);

        return $datos;
    }
}
