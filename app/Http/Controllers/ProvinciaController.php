<?php

namespace App\Http\Controllers;

use App\Models\Provincia;
use App\Models\Departamento;
use Illuminate\Http\Request;

class ProvinciaController extends Controller
{
    public function index(Request $request)
    {
        $buscar = $request->get('buscar');
        $departamento_id = $request->get('departamento_id');

        $query = Provincia::with('departamento.pais')->orderBy('nombre', 'asc');

        if ($buscar) {
            $query->where('nombre', 'like', '%' . $buscar . '%');
        }

        if ($departamento_id) {
            $query->where('departamento_id', $departamento_id);
        }

        $provincias = $query->paginate(10);
        $departamentos = Departamento::with('pais')->orderBy('nombre', 'asc')->get();

        return view('admin.provincias.index', compact('provincias', 'departamentos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'departamento_id' => 'required|exists:departamentos,id',
            'nombre' => 'required|string|max:255',
            'activo' => 'nullable|boolean',
        ]);

        // Prevent duplicate province name in the same department
        $exists = Provincia::where('departamento_id', $request->departamento_id)
            ->where('nombre', $request->nombre)
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->withErrors(['nombre' => 'La provincia ya existe en este departamento.'])
                ->with('mensaje', 'Error al crear provincia.')
                ->with('icono', 'error');
        }

        Provincia::create([
            'departamento_id' => $request->departamento_id,
            'nombre' => $request->nombre,
            'activo' => $request->has('activo') ? (bool) $request->activo : true,
        ]);

        return redirect()->back()
            ->with('mensaje', 'Provincia creada correctamente.')
            ->with('icono', 'success');
    }

    public function update(Request $request, $id)
    {
        $prov = Provincia::findOrFail($id);

        $request->validate([
            'departamento_id' => 'required|exists:departamentos,id',
            'nombre' => 'required|string|max:255',
            'activo' => 'nullable|boolean',
        ]);

        // Prevent duplicates
        $exists = Provincia::where('departamento_id', $request->departamento_id)
            ->where('nombre', $request->nombre)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->withErrors(['nombre' => 'La provincia ya existe en este departamento.'])
                ->with('mensaje', 'Error al actualizar provincia.')
                ->with('icono', 'error');
        }

        $prov->update([
            'departamento_id' => $request->departamento_id,
            'nombre' => $request->nombre,
            'activo' => $request->has('activo') ? (bool) $request->activo : $prov->activo,
        ]);

        return redirect()->back()
            ->with('mensaje', 'Provincia actualizada correctamente.')
            ->with('icono', 'success');
    }

    public function toggleStatus($id)
    {
        $prov = Provincia::findOrFail($id);
        $prov->activo = !$prov->activo;
        $prov->save();

        return redirect()->back()
            ->with('mensaje', 'Estado de la provincia actualizado.')
            ->with('icono', 'success');
    }

    public function destroy($id)
    {
        $prov = Provincia::findOrFail($id);
        $prov->delete();

        return redirect()->back()
            ->with('mensaje', 'Provincia eliminada correctamente.')
            ->with('icono', 'success');
    }
}
