<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    /** @use HasFactory<\Database\Factories\CategoriaFactory> */
    use HasFactory;
    use \App\Models\Concerns\UsesGalyConnection;

    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
        'icono'
    ];

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }
}
