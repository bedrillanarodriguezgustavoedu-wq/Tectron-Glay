<?php

namespace App\Http\Controllers;

use App\Models\Marca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MarcaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $buscar = $request->get('buscar');
        $query = Marca::query()->orderBy('nombre');

        if ($buscar) {
            $query->where('nombre', 'like', '%'.$buscar.'%');
        }

        // Show products count as requested: "Mostrar cantidad de productos asociados"
        $marcas = $query->withCount('productos')->paginate(10)->withQueryString();

        $totalMarcas = Marca::count();
        $marcasActivas = Marca::where('activo', true)->count();
        $marcasConLogo = Marca::whereNotNull('imagen')->where('imagen', '<>', '')->count();

        return view('admin.marcas.index', compact(
            'marcas',
            'buscar',
            'totalMarcas',
            'marcasActivas',
            'marcasConLogo'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.marcas.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:marcas,nombre',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'activo' => 'nullable|boolean',
        ]);

        $marca = new Marca;
        $marca->nombre = $request->nombre;
        $marca->slug = $this->slugDisponible($request->nombre);
        $marca->activo = $request->has('activo') ? (bool) $request->activo : true;

        if ($request->hasFile('imagen')) {
            $marca->imagen = $request->file('imagen')->store('marcas', 'public');
        }

        $marca->save();

        return redirect()->route('admin.marcas.index')
            ->with('mensaje', 'Marca creada exitosamente')
            ->with('icono', 'success');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $marca = Marca::withCount('productos')->findOrFail($id);

        return view('admin.marcas.show', compact('marca'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $marca = Marca::findOrFail($id);

        return view('admin.marcas.edit', compact('marca'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:marcas,nombre,'.$id,
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'activo' => 'nullable|boolean',
        ]);

        $marca = Marca::findOrFail($id);
        $marca->nombre = $request->nombre;
        $marca->slug = $this->slugDisponible($request->nombre, $marca->id);
        $marca->activo = $request->has('activo') ? (bool) $request->activo : false;

        if ($request->hasFile('imagen')) {
            // Delete old logo
            if ($marca->imagen && Storage::disk('public')->exists($marca->imagen)) {
                Storage::disk('public')->delete($marca->imagen);
            }
            $marca->imagen = $request->file('imagen')->store('marcas', 'public');
        }

        $marca->getConnection()->transaction(function () use ($marca) {
            $marca->save();
            $marca->productos()->update(['marca' => $marca->nombre]);
        });

        return redirect()->route('admin.marcas.index')
            ->with('mensaje', 'Marca actualizada exitosamente')
            ->with('icono', 'success');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $marca = Marca::findOrFail($id);
        $marca->activo = false;
        $marca->save();

        return redirect()->route('admin.marcas.index')
            ->with('mensaje', 'Marca desactivada; se conservaron sus productos asociados.')
            ->with('icono', 'success');
    }

    private function slugDisponible(string $nombre, ?int $ignorarId = null): string
    {
        $base = Str::slug($nombre) ?: 'marca';
        $slug = $base;
        $sufijo = 2;

        while (
            Marca::where('slug', $slug)
                ->when($ignorarId, fn ($query) => $query->where('id', '<>', $ignorarId))
                ->exists()
        ) {
            $slug = $base.'-'.$sufijo++;
        }

        return $slug;
    }

    /**
     * Toggle brand active status.
     */
    public function toggleStatus($id)
    {
        $marca = Marca::findOrFail($id);
        $marca->activo = ! $marca->activo;
        $marca->save();

        return redirect()->route('admin.marcas.index')
            ->with('mensaje', 'Estado de la marca actualizado exitosamente')
            ->with('icono', 'success');
    }
}
