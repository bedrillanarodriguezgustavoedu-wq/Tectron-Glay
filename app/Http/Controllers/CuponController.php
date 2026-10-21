<?php

namespace App\Http\Controllers;

use App\Models\Cupon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CuponController extends Controller
{
    public function index(Request $request)
    {
        $buscar = $request->get('buscar');

        $cupones = Cupon::query()
            ->when($buscar, function ($consulta) use ($buscar) {
                $consulta->where('codigo', 'like', '%'.Cupon::normalizarCodigo($buscar).'%');
            })
            ->orderByDesc('activo')
            ->orderBy('codigo')
            ->paginate(15)
            ->withQueryString();

        return view('admin.cupones.index', compact('cupones', 'buscar'));
    }

    public function create()
    {
        return view('admin.cupones.create');
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);

        Cupon::create($datos);

        return redirect()->route('admin.cupones.index')
            ->with('mensaje', 'Cupón creado exitosamente')
            ->with('icono', 'success');
    }

    public function edit($id)
    {
        return view('admin.cupones.edit', ['cupon' => Cupon::findOrFail($id)]);
    }

    public function update(Request $request, $id)
    {
        $cupon = Cupon::findOrFail($id);

        $cupon->update($this->validar($request, $cupon->id));

        return redirect()->route('admin.cupones.index')
            ->with('mensaje', 'Cupón actualizado exitosamente')
            ->with('icono', 'success');
    }

    public function destroy($id)
    {
        $cupon = Cupon::findOrFail($id);
        $cupon->activo = false;
        $cupon->save();

        return redirect()->route('admin.cupones.index')
            ->with('mensaje', "El cupón {$cupon->codigo} quedó pausado: la app deja de aceptarlo, pero los pedidos que ya lo usaron no se modifican.")
            ->with('icono', 'success');
    }

    public function toggleStatus($id)
    {
        $cupon = Cupon::findOrFail($id);
        $cupon->activo = ! $cupon->activo;
        $cupon->save();

        $mensaje = $cupon->activo
            ? ($cupon->estado === 'Vencido'
                ? "El cupón {$cupon->codigo} está activo, pero su fecha de vencimiento ya pasó: la app no lo aceptará hasta que la cambies."
                : "El cupón {$cupon->codigo} quedó activo.")
            : "El cupón {$cupon->codigo} quedó pausado.";

        return redirect()->route('admin.cupones.index')
            ->with('mensaje', $mensaje)
            ->with('icono', 'success');
    }

    private function validar(Request $request, ?int $ignorarId = null): array
    {
        // La app busca el código en mayúsculas, así que se normaliza antes de validar.
        $request->merge(['codigo' => Cupon::normalizarCodigo((string) $request->input('codigo'))]);

        $datos = $request->validate([
            'codigo' => [
                'required', 'string', 'max:20', 'regex:/^[A-Z0-9]+$/',
                Rule::unique('cupones', 'codigo')->ignore($ignorarId),
            ],
            'descuento_porcentaje' => 'required|integer|min:1|max:99',
            'fecha_expiracion' => 'nullable|date',
            'activo' => 'nullable|boolean',
        ], [
            'codigo.regex' => 'El código solo admite letras mayúsculas y números, sin espacios ni tildes.',
            'codigo.unique' => 'Ya existe un cupón con ese código.',
            'descuento_porcentaje.min' => 'El descuento debe ser al menos 1%.',
            'descuento_porcentaje.max' => 'El descuento no puede ser 100% o más; pausa el cupón si ya no lo quieres ofrecer.',
        ]);

        return [
            'codigo' => $datos['codigo'],
            'descuento_porcentaje' => (int) $datos['descuento_porcentaje'],
            'fecha_expiracion' => $datos['fecha_expiracion'] ?? null,
            'activo' => $request->has('activo'),
        ];
    }
}
