<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOfertaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:255',
            'tipo_descuento' => 'required|in:porcentaje,monto',
            'valor_descuento' => [
                'required',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) {
                    if ($this->tipo_descuento === 'porcentaje' && $value > 100) {
                        $fail('El descuento porcentual no puede ser superior al 100%.');
                    }
                }
            ],
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'activo' => 'nullable|boolean',
            'productos' => [
                'required',
                'array',
                'min:1',
                function ($attribute, $value, $fail) {
                    $fecha_inicio = $this->fecha_inicio;
                    $fecha_fin = $this->fecha_fin;
                    
                    if (!$fecha_inicio || !$fecha_fin) {
                        return;
                    }

                    // Get currently edited offer ID from route parameter '{id}'
                    $ofertaId = $this->route('id');

                    // Find if any of the selected products has an overlapping active offer campaign
                    $overlappingOffers = \App\Models\Oferta::where('activo', true)
                        ->where('id', '!=', $ofertaId)
                        ->where(function($query) use ($fecha_inicio, $fecha_fin) {
                            $query->whereBetween('fecha_inicio', [$fecha_inicio, $fecha_fin])
                                  ->orWhereBetween('fecha_fin', [$fecha_inicio, $fecha_fin])
                                  ->orWhere(function($q) use ($fecha_inicio, $fecha_fin) {
                                      $q->where('fecha_inicio', '<=', $fecha_inicio)
                                        ->where('fecha_fin', '>=', $fecha_fin);
                                  });
                        })
                        ->with('productos')
                        ->get();

                    $conflictingProductIds = [];
                    foreach ($overlappingOffers as $off) {
                        foreach ($off->productos as $prod) {
                            if (in_array($prod->id, $value)) {
                                $conflictingProductIds[$prod->id] = $prod->nombre;
                            }
                        }
                    }

                    if (!empty($conflictingProductIds)) {
                        $names = implode(', ', $conflictingProductIds);
                        $fail("Los siguientes productos ya están asociados a otra oferta activa en las fechas seleccionadas: {$names}.");
                    }
                }
            ],
            'productos.*' => 'required|exists:productos,id',
        ];
    }

    public function messages(): array
    {
        return [
            'fecha_fin.after' => 'La fecha de finalización debe ser posterior a la fecha de inicio.',
            'productos.required' => 'Debe asociar al menos un producto a la oferta.',
            'valor_descuento.min' => 'El valor del descuento debe ser mayor a cero.'
        ];
    }
}
