<?php

namespace App\Http\Controllers;

use App\Models\Carrusel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CarruselController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $buscar = $request->get('buscar');
        $query = Carrusel::query();

        if ($buscar) {
            $query->where('titulo', 'like', '%' . $buscar . '%')
                  ->orWhere('subtitulo', 'like', '%' . $buscar . '%');
        }

        $carruseles = $query->orderBy('orden', 'asc')->paginate(10);
        return view('admin.carruseles.index', compact('carruseles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.carruseles.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'imagen' => 'required|image|mimes:jpeg,png,jpg,webp|max:3072',
            'titulo' => 'nullable|string|max:255',
            'subtitulo' => 'nullable|string|max:255',
            'boton_texto' => 'nullable|string|max:100',
            'enlace' => 'nullable|string|max:500',
            'activo' => 'nullable|boolean',
            'orden' => 'required|integer|min:0'
        ]);

        $carrusel = new Carrusel();
        $carrusel->titulo = $request->titulo;
        $carrusel->subtitulo = $request->subtitulo;
        $carrusel->boton_texto = $request->boton_texto;
        $carrusel->enlace = $request->enlace;
        $carrusel->activo = $request->has('activo') ? (bool)$request->activo : true;
        $carrusel->orden = $request->orden;

        if ($request->hasFile('imagen')) {
            $carrusel->imagen = $request->file('imagen')->store('carruseles', 'public');
        }

        $carrusel->save();

        return redirect()->route('admin.carruseles.index')
            ->with('mensaje', 'Elemento de carrusel creado exitosamente')
            ->with('icono', 'success');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $carrusel = Carrusel::findOrFail($id);
        return view('admin.carruseles.show', compact('carrusel'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $carrusel = Carrusel::findOrFail($id);
        return view('admin.carruseles.edit', compact('carrusel'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:3072',
            'titulo' => 'nullable|string|max:255',
            'subtitulo' => 'nullable|string|max:255',
            'boton_texto' => 'nullable|string|max:100',
            'enlace' => 'nullable|string|max:500',
            'activo' => 'nullable|boolean',
            'orden' => 'required|integer|min:0'
        ]);

        $carrusel = Carrusel::findOrFail($id);
        $carrusel->titulo = $request->titulo;
        $carrusel->subtitulo = $request->subtitulo;
        $carrusel->boton_texto = $request->boton_texto;
        $carrusel->enlace = $request->enlace;
        $carrusel->activo = $request->has('activo') ? (bool)$request->activo : false;
        $carrusel->orden = $request->orden;

        if ($request->hasFile('imagen')) {
            // Delete old slider image
            if ($carrusel->imagen && Storage::disk('public')->exists($carrusel->imagen)) {
                Storage::disk('public')->delete($carrusel->imagen);
            }
            $carrusel->imagen = $request->file('imagen')->store('carruseles', 'public');
        }

        $carrusel->save();

        return redirect()->route('admin.carruseles.index')
            ->with('mensaje', 'Elemento de carrusel actualizado exitosamente')
            ->with('icono', 'success');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $carrusel = Carrusel::findOrFail($id);

        if ($carrusel->imagen && Storage::disk('public')->exists($carrusel->imagen)) {
            Storage::disk('public')->delete($carrusel->imagen);
        }

        $carrusel->delete();

        return redirect()->route('admin.carruseles.index')
            ->with('mensaje', 'Elemento de carrusel eliminado exitosamente')
            ->with('icono', 'success');
    }

    /**
     * Toggle carrusel active status.
     */
    public function toggleStatus($id)
    {
        $carrusel = Carrusel::findOrFail($id);
        $carrusel->activo = !$carrusel->activo;
        $carrusel->save();

        return redirect()->route('admin.carruseles.index')
            ->with('mensaje', 'Estado del elemento de carrusel actualizado exitosamente')
            ->with('icono', 'success');
    }
}
