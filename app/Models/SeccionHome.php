<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeccionHome extends Model
{
    use HasFactory;

    protected $table = 'secciones_home';

    protected $fillable = [
        'clave',
        'titulo',
        'activo',
        'orden',
        'limite'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
        'limite' => 'integer'
    ];
}
