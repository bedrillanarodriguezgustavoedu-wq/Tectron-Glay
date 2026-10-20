<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FooterEnlace extends Model
{
    protected $table = 'footer_enlaces';

    protected $fillable = [
        'titulo',
        'url',
        'orden',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
    ];
}
