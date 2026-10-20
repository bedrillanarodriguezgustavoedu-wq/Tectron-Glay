<?php

namespace App\Http\Controllers;

use App\Models\Direccion;
use App\Models\Pais;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaisController extends Controller
{
    public function index(Request $request)
    {
        $query = Pais::query()
            ->withCount(['departamentos', 'direcciones'])
            ->orderBy('nombre');

        if ($request->filled('buscar')) {
            $query->where('nombre', 'like', '%'.trim($request->string('buscar')).'%');
        }

        $paises = $query->paginate(10)->withQueryString();

        return view('admin.paises.index', compact('paises'));
    }

    public function store(Request $request)
    {
        $request->merge(['nombre' => trim((string) $request->input('nombre'))]);
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255', Rule::unique('paises', 'nombre')],
            'realiza_envios' => ['sometimes', 'boolean'],
            'activo' => ['sometimes', 'boolean'],
        ]);

        Pais::create([
            'nombre' => $validated['nombre'],
            'realiza_envios' => $request->boolean('realiza_envios'),
            'activo' => $request->boolean('activo'),
        ]);

        return redirect()->back()
            ->with('mensaje', 'País creado correctamente.')
            ->with('icono', 'success');
    }

    public function update(Request $request, int $id)
    {
        $pais = Pais::findOrFail($id);

        $request->merge(['nombre' => trim((string) $request->input('nombre'))]);
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255', Rule::unique('paises', 'nombre')->ignore($pais->id)],
            'realiza_envios' => ['sometimes', 'boolean'],
            'activo' => ['sometimes', 'boolean'],
        ]);

        $pais->update([
            'nombre' => $validated['nombre'],
            'realiza_envios' => $request->boolean('realiza_envios'),
            'activo' => $request->boolean('activo'),
        ]);

        return redirect()->back()
            ->with('mensaje', 'País actualizado correctamente.')
            ->with('icono', 'success');
    }

    public function toggleStatus(int $id)
    {
        $pais = Pais::findOrFail($id);
        $pais->activo = ! $pais->activo;
        $pais->save();

        return redirect()->back()
            ->with('mensaje', 'Estado del país actualizado.')
            ->with('icono', 'success');
    }

    public function destroy(int $id)
    {
        $pais = Pais::findOrFail($id);

        if ($pais->departamentos()->exists() || Direccion::where('pais_id', $pais->id)->exists()) {
            return redirect()->route('admin.paises.index')
                ->withErrors(['pais' => 'No se puede eliminar este país porque tiene zonas o direcciones asociadas. Desactívalo para ocultarlo de los envíos.']);
        }

        $pais->delete();

        return redirect()->back()
            ->with('mensaje', 'País eliminado correctamente.')
            ->with('icono', 'success');
    }
}
