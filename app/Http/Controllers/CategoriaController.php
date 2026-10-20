<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoriaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $buscar = $request->get('buscar');
        $query = Categoria::query();

        if ($buscar) {
            $query->where(function ($query) use ($buscar) {
                $query->where('nombre', 'like', '%'.$buscar.'%')
                    ->orWhere('descripcion', 'like', '%'.$buscar.'%');
            });
        }

        $categorias = $query->withCount('productos')->paginate(10);

        return view('admin.categorias.index', compact('categorias'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.categorias.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
            'icono' => 'nullable|string|max:100',
        ]);

        $categoria = new Categoria;
        $categoria->nombre = $request->nombre;
        $categoria->slug = $this->slugDisponible($request->slug ?: $request->nombre);
        $categoria->descripcion = $request->descripcion;
        $categoria->icono = $request->icono;
        $categoria->save();

        return redirect()->route('admin.categorias.index')
            ->with('mensaje', 'Categoría creada exitosamente')
            ->with('icono', 'success');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $categoria = Categoria::findOrFail($id);

        return view('admin.categorias.show', compact('categoria'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $categoria = Categoria::findOrFail($id);

        return view('admin.categorias.edit', compact('categoria'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
            'icono' => 'nullable|string|max:100',
        ]);

        $categoria = Categoria::findOrFail($id);
        $categoria->nombre = $request->nombre;
        $categoria->slug = $this->slugDisponible($request->slug ?: $request->nombre, $categoria->id);
        $categoria->descripcion = $request->descripcion;
        $categoria->icono = $request->icono;
        $categoria->getConnection()->transaction(function () use ($categoria) {
            $categoria->save();
            $categoria->productos()->update(['categoria_nombre' => $categoria->nombre]);
        });

        return redirect()->route('admin.categorias.index')
            ->with('mensaje', 'Categoría actualizada exitosamente')
            ->with('icono', 'success');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $categoria = Categoria::findOrFail($id);
        $productosCount = $categoria->productos()->count();
        if ($productosCount > 0) {
            return redirect()->route('admin.categorias.index')
                ->with('mensaje', "No se puede eliminar: la categoría tiene {$productosCount} productos. Cámbialos de categoría primero.")
                ->with('icono', 'error');
        }

        $categoria->delete();

        return redirect()->route('admin.categorias.index')
            ->with('mensaje', 'Categoría eliminada exitosamente')
            ->with('icono', 'success');
    }

    private function slugDisponible(string $value, ?int $ignorarId = null): string
    {
        $base = Str::slug($value) ?: 'categoria';
        $slug = $base;
        $sufijo = 2;

        while (
            Categoria::where('slug', $slug)
                ->when($ignorarId, fn ($query) => $query->where('id', '<>', $ignorarId))
                ->exists()
        ) {
            $slug = $base.'-'.$sufijo++;
        }

        return $slug;
    }
}
