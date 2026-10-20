<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\Pais;
use Illuminate\Http\Request;

class DepartamentoController extends Controller
{
    public function index(Request $request)
    {
        $buscar = $request->get('buscar');
        $pais_id = $request->get('pais_id');

        $query = Departamento::with('pais')->orderBy('nombre', 'asc');

        if ($buscar) {
            $query->where('nombre', 'like', '%' . $buscar . '%');
        }

        if ($pais_id) {
            $query->where('pais_id', $pais_id);
        }

        $departamentos = $query->paginate(10);
        $paises = Pais::orderBy('nombre', 'asc')->get();

        return view('admin.departamentos.index', compact('departamentos', 'paises'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'pais_id' => 'required|exists:paises,id',
            'nombre' => 'required|string|max:255',
            'activo' => 'nullable|boolean',
        ]);

        // Prevent duplicate department name in the same country
        $exists = Departamento::where('pais_id', $request->pais_id)
            ->where('nombre', $request->nombre)
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->withErrors(['nombre' => 'El departamento ya existe en este país.'])
                ->with('mensaje', 'Error al crear departamento.')
                ->with('icono', 'error');
        }

        Departamento::create([
            'pais_id' => $request->pais_id,
            'nombre' => $request->nombre,
            'activo' => $request->has('activo') ? (bool) $request->activo : true,
        ]);

        return redirect()->back()
            ->with('mensaje', 'Departamento creado correctamente.')
            ->with('icono', 'success');
    }

    public function update(Request $request, $id)
    {
        $dpto = Departamento::findOrFail($id);

        $request->validate([
            'pais_id' => 'required|exists:paises,id',
            'nombre' => 'required|string|max:255',
            'activo' => 'nullable|boolean',
        ]);

        // Prevent duplicates
        $exists = Departamento::where('pais_id', $request->pais_id)
            ->where('nombre', $request->nombre)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->withErrors(['nombre' => 'El departamento ya existe en este país.'])
                ->with('mensaje', 'Error al actualizar departamento.')
                ->with('icono', 'error');
        }

        $dpto->update([
            'pais_id' => $request->pais_id,
            'nombre' => $request->nombre,
            'activo' => $request->has('activo') ? (bool) $request->activo : $dpto->activo,
        ]);

        return redirect()->back()
            ->with('mensaje', 'Departamento actualizado correctamente.')
            ->with('icono', 'success');
    }

    public function toggleStatus($id)
    {
        $dpto = Departamento::findOrFail($id);
        $dpto->activo = !$dpto->activo;
        $dpto->save();

        return redirect()->back()
            ->with('mensaje', 'Estado del departamento actualizado.')
            ->with('icono', 'success');
    }

    public function destroy($id)
    {
        $dpto = Departamento::findOrFail($id);
        $dpto->delete();

        return redirect()->back()
            ->with('mensaje', 'Departamento eliminado correctamente.')
            ->with('icono', 'success');
    }
}
