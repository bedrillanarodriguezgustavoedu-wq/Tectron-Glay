<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FooterSettings extends Model
{
    protected $table = 'footer_settings';

    protected $fillable = [
        'nombre_empresa',
        'descripcion',
        'logo',
        'copyright_texto',
        'facebook_link',
        'instagram_link',
        'tiktok_link',
        'twitter_link',
        'youtube_link',
        'linkedin_link',
        'whatsapp_link',
        'direccion',
        'telefono',
        'email',
        'horario_atencion',
        'categorias_destacadas',
        'terminos_condiciones_contenido',
        'politica_privacidad_contenido',
        'politica_cookies_contenido',
        'cambios_devoluciones_contenido',
        'app_store_link',
        'google_play_link',
    ];

    protected $casts = [
        'categorias_destacadas' => 'array',
    ];
}
