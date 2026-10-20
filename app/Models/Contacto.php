<?php

namespace App\Models;

use App\Models\Concerns\UsesGalyConnection;
use Illuminate\Database\Eloquent\Model;

class Contacto extends Model
{
    use UsesGalyConnection;

    protected $table = 'contactos';

    protected $fillable = [
        'nombre_empresa',
        'direccion',
        'pais',
        'departamento',
        'provincia',
        'telefono_principal',
        'telefono_secundario',
        'whatsapp',
        'email',
        'horario_atencion',
        'google_maps_link',
        'facebook_link',
        'instagram_link',
        'youtube_link',
        'twitter_link',
        'mensaje_bienvenida',
        'imagen_banner',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];
}
