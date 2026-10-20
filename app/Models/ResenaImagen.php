<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResenaImagen extends Model
{
    use \App\Models\Concerns\UsesGalyConnection;

    protected $table = 'resena_imagenes';

    protected $fillable = [
        'resena_id',
        'imagen',
        'orden',
    ];

    public function resena()
    {
        return $this->belongsTo(Resena::class, 'resena_id');
    }
}
