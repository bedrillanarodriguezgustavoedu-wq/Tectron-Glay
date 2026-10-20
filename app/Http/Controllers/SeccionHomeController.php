<?php

namespace App\Http\Controllers;

use App\Models\SeccionHome;
use Illuminate\Http\Request;

class SeccionHomeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $secciones = SeccionHome::orderBy('orden', 'asc')->get();
        return view('admin.secciones_home.index', compact('secciones'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $seccion = SeccionHome::findOrFail($id);
        return view('admin.secciones_home.edit', compact('seccion'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'orden' => 'required|integer|min:0',
            'limite' => 'required|integer|min:1|max:50',
            'activo' => 'nullable|boolean'
        ]);

        $seccion = SeccionHome::findOrFail($id);
        $seccion->titulo = $request->titulo;
        $seccion->orden = $request->orden;
        $seccion->limite = $request->limite;
        $seccion->activo = $request->has('activo') ? (bool)$request->activo : false;
        $seccion->save();

        return redirect()->route('admin.secciones_home.index')
            ->with('mensaje', 'Sección del Home actualizada exitosamente')
            ->with('icono', 'success');
    }

    /**
     * Toggle status.
     */
    public function toggleStatus($id)
    {
        $seccion = SeccionHome::findOrFail($id);
        $seccion->activo = !$seccion->activo;
        $seccion->save();

        return redirect()->route('admin.secciones_home.index')
            ->with('mensaje', 'Estado de la sección actualizado exitosamente')
            ->with('icono', 'success');
    }
}
