<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pais extends Model
{
    protected $table = 'paises';

    protected $fillable = [
        'nombre',
        'activo',
        'realiza_envios',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'realiza_envios' => 'boolean',
    ];

    public function departamentos()
    {
        return $this->hasMany(Departamento::class, 'pais_id');
    }

    public function direcciones()
    {
        return $this->hasMany(Direccion::class, 'pais_id');
    }
}
