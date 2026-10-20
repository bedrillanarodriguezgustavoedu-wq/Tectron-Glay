<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\Direccion;
use App\Models\Pais;
use App\Models\Provincia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DireccionController extends Controller
{
    public function store(Request $request)
    {
        $validated = $this->validateAddress($request);

        $usuario_id = Auth::user()->id;

        // If this is the user's first address, or they marked it as default
        $first_address = ! Direccion::where('usuario_id', $usuario_id)->exists();
        $es_predeterminada = $request->has('es_predeterminada') || $first_address;

        if ($es_predeterminada) {
            // Remove previous default address
            Direccion::where('usuario_id', $usuario_id)->update(['es_predeterminada' => false]);
        }

        Direccion::create([
            'usuario_id' => $usuario_id,
            ...$validated,
            'es_predeterminada' => $es_predeterminada,
        ]);

        return redirect()->back()
            ->with('mensaje', 'Dirección registrada con éxito.')
            ->with('icono', 'success');
    }

    public function storeExpress(Request $request)
    {
        if (Auth::guest()) {
            return response()->json([
                'success' => false,
                'message' => 'Debes iniciar sesión para realizar esta acción.',
                'code' => 401,
            ], 401);
        }

        $validated = $this->validateAddress($request);

        $usuario_id = Auth::user()->id;

        $first_address = ! Direccion::where('usuario_id', $usuario_id)->exists();
        $es_predeterminada = $request->has('es_predeterminada') || $first_address;

        if ($es_predeterminada) {
            Direccion::where('usuario_id', $usuario_id)->update(['es_predeterminada' => false]);
        }

        $direccion = Direccion::create([
            'usuario_id' => $usuario_id,
            ...$validated,
            'es_predeterminada' => $es_predeterminada,
        ]);

        // Eager load relations to send back in JSON
        $direccion->load(['pais', 'departamento', 'provincia']);

        return response()->json([
            'success' => true,
            'direccion' => $direccion,
            'formatted' => $direccion->getFormattedAddress(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $direccion = Direccion::where('usuario_id', Auth::user()->id)->findOrFail($id);

        $validated = $this->validateAddress($request);

        $es_predeterminada = $request->has('es_predeterminada') || $direccion->es_predeterminada;

        if ($es_predeterminada && ! $direccion->es_predeterminada) {
            Direccion::where('usuario_id', Auth::user()->id)->update(['es_predeterminada' => false]);
        }

        $direccion->update([
            ...$validated,
            'es_predeterminada' => $es_predeterminada,
        ]);

        return redirect()->back()
            ->with('mensaje', 'Dirección actualizada con éxito.')
            ->with('icono', 'success');
    }

    public function setDefault($id)
    {
        $direccion = Direccion::where('usuario_id', Auth::user()->id)->findOrFail($id);

        // Reset others
        Direccion::where('usuario_id', Auth::user()->id)->update(['es_predeterminada' => false]);

        $direccion->es_predeterminada = true;
        $direccion->save();

        return redirect()->back()
            ->with('mensaje', 'Dirección predeterminada actualizada.')
            ->with('icono', 'success');
    }

    public function destroy($id)
    {
        $direccion = Direccion::where('usuario_id', Auth::user()->id)->findOrFail($id);
        $wasDefault = $direccion->es_predeterminada;

        $direccion->delete();

        // If the default one was deleted, make another default (if any exists)
        if ($wasDefault) {
            $other = Direccion::where('usuario_id', Auth::user()->id)->first();
            if ($other) {
                $other->es_predeterminada = true;
                $other->save();
            }
        }

        return redirect()->back()
            ->with('mensaje', 'Dirección eliminada con éxito.')
            ->with('icono', 'success');
    }

    // Dynamic cascaded APIs
    public function getDepartamentos($pais_id)
    {
        $pais = Pais::whereKey($pais_id)
            ->where('activo', true)
            ->where('realiza_envios', true)
            ->first();
        if (! $pais) {
            return response()->json([]);
        }

        $departamentos = Departamento::where('pais_id', $pais->id)
            ->where('activo', true)
            ->orderBy('nombre', 'asc')
            ->get(['id', 'nombre']);

        return response()->json($departamentos);
    }

    public function getProvincias($departamento_id)
    {
        $departamento = Departamento::whereKey($departamento_id)
            ->where('activo', true)
            ->whereHas('pais', fn ($query) => $query->where('activo', true)->where('realiza_envios', true))
            ->first();
        if (! $departamento) {
            return response()->json([]);
        }

        $provincias = Provincia::where('departamento_id', $departamento->id)
            ->where('activo', true)
            ->orderBy('nombre', 'asc')
            ->get(['id', 'nombre']);

        return response()->json($provincias);
    }

    private function validateAddress(Request $request): array
    {
        $paisId = $request->input('pais_id');
        $departamentoId = $request->input('departamento_id');

        return $request->validate([
            'alias' => ['required', 'string', 'max:100'],
            'pais_id' => [
                'required',
                Rule::exists('paises', 'id')->where('activo', true)->where('realiza_envios', true),
            ],
            'departamento_id' => [
                'required',
                Rule::exists('departamentos', 'id')
                    ->where('pais_id', $paisId)
                    ->where('activo', true),
            ],
            'provincia_id' => [
                'required',
                Rule::exists('provincias', 'id')
                    ->where('departamento_id', $departamentoId)
                    ->where('activo', true),
            ],
            'direccion_exacta' => ['required', 'string', 'max:255'],
            'referencia' => ['nullable', 'string', 'max:255'],
            'codigo_postal' => ['nullable', 'string', 'max:20'],
            'telefono' => ['nullable', 'string', 'max:20'],
        ]);
    }
}
