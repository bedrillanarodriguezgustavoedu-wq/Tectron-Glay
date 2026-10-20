<?php

namespace App\Models;

use App\Models\Concerns\UsesGalyConnection;
use Illuminate\Database\Eloquent\Model;

class Nosotros extends Model
{
    use UsesGalyConnection;

    protected $table = 'nosotros';

    protected $fillable = [
        'titulo_principal',
        'subtitulo',
        'descripcion_principal',
        'historia',
        'mision',
        'vision',
        'valores',
        'imagen_principal',
        'video_url',
        'experiencia_anos',
        'clientes_count',
        'productos_count',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'experiencia_anos' => 'integer',
        'clientes_count' => 'integer',
        'productos_count' => 'integer',
    ];
}
