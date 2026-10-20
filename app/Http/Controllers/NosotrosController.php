<?php

namespace App\Http\Controllers;

use App\Models\Nosotros;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class NosotrosController extends Controller
{
    public function index()
    {
        $nosotros = Nosotros::first();
        return view('admin.nosotros.index', compact('nosotros'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'titulo_principal' => 'required|string|max:255',
            'subtitulo' => 'required|string|max:255',
            'descripcion_principal' => 'required|string',
            'historia' => 'required|string',
            'mision' => 'required|string',
            'vision' => 'required|string',
            'valores' => 'required|string',
            'imagen_principal' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'video_url' => 'nullable|url|max:255',
            'experiencia_anos' => 'required|integer|min:0',
            'clientes_count' => 'required|integer|min:0',
            'productos_count' => 'required|integer|min:0',
        ]);

        $nosotros = Nosotros::first();
        if (!$nosotros) {
            $nosotros = new Nosotros();
        }

        $nosotros->titulo_principal = $request->titulo_principal;
        $nosotros->subtitulo = $request->subtitulo;
        $nosotros->descripcion_principal = $request->descripcion_principal;
        $nosotros->historia = $request->historia;
        $nosotros->mision = $request->mision;
        $nosotros->vision = $request->vision;
        $nosotros->valores = $request->valores;
        $nosotros->video_url = $request->video_url;
        $nosotros->experiencia_anos = $request->experiencia_anos;
        $nosotros->clientes_count = $request->clientes_count;
        $nosotros->productos_count = $request->productos_count;
        $nosotros->activo = $request->has('activo') ? true : false;

        if ($request->hasFile('imagen_principal')) {
            if ($nosotros->imagen_principal && Storage::disk('public')->exists($nosotros->imagen_principal)) {
                Storage::disk('public')->delete($nosotros->imagen_principal);
            }
            $nosotros->imagen_principal = $request->file('imagen_principal')->store('nosotros', 'public');
        }

        $nosotros->save();

        return redirect()->back()
            ->with('mensaje', 'Información de Nosotros actualizada exitosamente.')
            ->with('icono', 'success');
    }
}
