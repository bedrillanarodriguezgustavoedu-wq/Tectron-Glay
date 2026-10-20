<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Carrusel extends Model
{
    use HasFactory;

    protected $table = 'carruseles';

    protected $fillable = [
        'imagen',
        'titulo',
        'subtitulo',
        'boton_texto',
        'enlace',
        'activo',
        'orden'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer'
    ];
}
