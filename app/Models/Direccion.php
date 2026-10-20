<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Direccion extends Model
{
    protected $table = 'direccions';

    protected $fillable = [
        'usuario_id',
        'alias',
        'pais_id',
        'departamento_id',
        'provincia_id',
        'direccion_exacta',
        'referencia',
        'codigo_postal',
        'telefono',
        'es_predeterminada',
    ];

    protected $casts = [
        'es_predeterminada' => 'boolean',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function pais()
    {
        return $this->belongsTo(Pais::class, 'pais_id');
    }

    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'departamento_id');
    }

    public function provincia()
    {
        return $this->belongsTo(Provincia::class, 'provincia_id');
    }

    /**
     * Get the formatted address string.
     */
    public function getFormattedAddress()
    {
        $parts = [
            "Alias: " . $this->alias,
            "Dirección: " . $this->direccion_exacta
        ];

        if ($this->referencia) {
            $parts[] = "Ref: " . $this->referencia;
        }

        $parts[] = "Provincia: " . ($this->provincia ? $this->provincia->nombre : 'N/A');
        $parts[] = "Departamento: " . ($this->departamento ? $this->departamento->nombre : 'N/A');
        $parts[] = "País: " . ($this->pais ? $this->pais->nombre : 'N/A');

        if ($this->codigo_postal) {
            $parts[] = "C.P.: " . $this->codigo_postal;
        }
        if ($this->telefono) {
            $parts[] = "Telf: " . $this->telefono;
        }

        return implode(" | ", $parts);
    }
}
