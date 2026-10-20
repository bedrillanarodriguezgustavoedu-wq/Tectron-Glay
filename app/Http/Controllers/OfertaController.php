<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOfertaRequest;
use App\Http\Requests\UpdateOfertaRequest;
use App\Models\Oferta;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OfertaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $buscar = $request->get('buscar');
        $query = Oferta::query();

        if ($buscar) {
            $query->where('nombre', 'like', '%'.$buscar.'%');
        }

        $ofertas = $query->withCount('productos')->paginate(10);

        return view('admin.ofertas.index', compact('ofertas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $productos = Producto::orderBy('nombre')->get();

        return view('admin.ofertas.create', compact('productos'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOfertaRequest $request)
    {
        DB::connection('galy')->transaction(function () use ($request): void {
            $oferta = new Oferta;
            $oferta->nombre = $request->nombre;
            $oferta->tipo_descuento = $request->tipo_descuento;
            $oferta->valor_descuento = $request->valor_descuento;
            $oferta->fecha_inicio = $request->fecha_inicio;
            $oferta->fecha_fin = $request->fecha_fin;
            $oferta->activo = $request->has('activo');
            $oferta->save();

            $oferta->productos()->sync($request->productos);
            Oferta::refreshProductPrices();
        });

        return redirect()->route('admin.ofertas.index')
            ->with('mensaje', 'Oferta creada y aplicada exitosamente')
            ->with('icono', 'success');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $oferta = Oferta::with('productos.imagenes')->findOrFail($id);

        return view('admin.ofertas.show', compact('oferta'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $oferta = Oferta::with('productos')->findOrFail($id);
        $productos = Producto::orderBy('nombre')->get();
        $productosAsociados = $oferta->productos->pluck('id')->toArray();

        return view('admin.ofertas.edit', compact('oferta', 'productos', 'productosAsociados'));
    }

    public function update(UpdateOfertaRequest $request, $id)
    {
        DB::connection('galy')->transaction(function () use ($request, $id): void {
            $oferta = Oferta::findOrFail($id);
            $oferta->nombre = $request->nombre;
            $oferta->tipo_descuento = $request->tipo_descuento;
            $oferta->valor_descuento = $request->valor_descuento;
            $oferta->fecha_inicio = $request->fecha_inicio;
            $oferta->fecha_fin = $request->fecha_fin;
            $oferta->activo = $request->has('activo');
            $oferta->save();

            $oferta->productos()->sync($request->productos);
            Oferta::refreshProductPrices();
        });

        return redirect()->route('admin.ofertas.index')
            ->with('mensaje', 'Oferta actualizada exitosamente')
            ->with('icono', 'success');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        DB::connection('galy')->transaction(function () use ($id): void {
            $oferta = Oferta::findOrFail($id);
            $oferta->productos()->detach();
            $oferta->delete();
            Oferta::refreshProductPrices();
        });

        return redirect()->route('admin.ofertas.index')
            ->with('mensaje', 'Oferta eliminada exitosamente')
            ->with('icono', 'success');
    }

    /**
     * Toggle offer status.
     */
    public function toggleStatus($id)
    {
        DB::connection('galy')->transaction(function () use ($id): void {
            $oferta = Oferta::findOrFail($id);
            $oferta->activo = ! $oferta->activo;
            $oferta->save();
            Oferta::refreshProductPrices();
        });

        return redirect()->route('admin.ofertas.index')
            ->with('mensaje', 'Estado de la oferta actualizado exitosamente')
            ->with('icono', 'success');
    }
}
