<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\PuntoRecojo;
use Illuminate\Http\Request;

class PuntoRecojoController extends Controller
{
    public function index(Request $request)
    {
        $puntos = PuntoRecojo::with('provincia.departamento')
            ->when($request->input('tipo') === 'tienda', fn ($consulta) => $consulta->deTienda())
            ->when($request->input('tipo') === 'agencia', fn ($consulta) => $consulta->whereNotNull('provincia_id'))
            ->when(
                $request->input('departamento_id'),
                fn ($consulta, $departamentoId) => $consulta->whereHas(
                    'provincia',
                    fn ($provincia) => $provincia->where('departamento_id', $departamentoId)
                )
            )
            // Los ocultos van al final: al abrir el panel lo que importa es lo que
            // se está ofreciendo ahora mismo en el carrito.
            ->orderByDesc('activo')
            // Y dentro de lo visible, primero los locales de la tienda y después
            // las agencias, que son otra decisión y otra modalidad de entrega.
            ->orderByRaw('provincia_id is null desc')
            ->orderBy('orden')
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('admin.puntos_recojo.index', [
            'puntos' => $puntos,
            'departamentos' => Departamento::orderBy('nombre')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.puntos_recojo.create', ['departamentos' => $this->departamentos()]);
    }

    public function store(Request $request)
    {
        PuntoRecojo::create($this->validar($request));

        return redirect()->route('admin.puntos_recojo.index')
            ->with('mensaje', 'Punto de recojo creado exitosamente')
            ->with('icono', 'success');
    }

    public function edit($id)
    {
        return view('admin.puntos_recojo.edit', [
            'punto' => PuntoRecojo::findOrFail($id),
            'departamentos' => $this->departamentos(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $punto = PuntoRecojo::findOrFail($id);
        $punto->update($this->validar($request, $punto));

        return redirect()->route('admin.puntos_recojo.index')
            ->with('mensaje', 'Punto de recojo actualizado exitosamente')
            ->with('icono', 'success');
    }

    public function destroy($id)
    {
        $punto = PuntoRecojo::findOrFail($id);
        $punto->activo = false;
        $punto->save();

        return redirect()->route('admin.puntos_recojo.index')
            ->with('mensaje', "{$punto->nombre} dejó de ofrecerse en el carrito; los pedidos que ya lo eligieron se conservan.")
            ->with('icono', 'success');
    }

    public function toggleStatus($id)
    {
        $punto = PuntoRecojo::findOrFail($id);
        $punto->activo = ! $punto->activo;
        $punto->save();

        return redirect()->route('admin.puntos_recojo.index')
            ->with('mensaje', $punto->activo
                ? "{$punto->nombre} vuelve a aparecer en el carrito."
                : "{$punto->nombre} quedó oculto en el carrito.")
            ->with('icono', 'success');
    }

    private function validar(Request $request): array
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:100',
            'direccion' => 'required|string|max:255',
            'horario' => 'nullable|string|max:100',
            'telefono' => 'nullable|string|max:30',
            'provincia_id' => 'nullable|integer|exists:provincias,id',
            'orden' => 'nullable|integer|min:0|max:999',
            'activo' => 'nullable|boolean',
        ], [
            'nombre.required' => 'Ponle un nombre al punto: el cliente elige por el nombre.',
            'direccion.required' => 'Sin dirección el punto no sirve: es lo que ve el cliente al recoger.',
            'provincia_id.exists' => 'Esa provincia ya no existe en las zonas de envío. Elige otra o déjalo vacío.',
        ]);

        $datos['activo'] = $request->has('activo');
        $datos['orden'] = (int) ($datos['orden'] ?? 0);
        $datos['provincia_id'] = $datos['provincia_id'] ?? null;

        return $datos;
    }

    /**
     * Provincias activas agrupadas por departamento para el selector del
     * formulario: 196 opciones sueltas en orden alfabético no se pueden leer.
     */
    private function departamentos()
    {
        return Departamento::with(['provincias' => fn ($consulta) => $consulta
                ->where('activo', true)
                ->orderBy('nombre')])
            ->orderBy('nombre')
            ->get();
    }
}
