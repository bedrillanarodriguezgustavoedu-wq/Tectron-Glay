<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Carrito extends Model
{
    use \App\Models\Concerns\UsesGalyConnection;

    protected $table = 'carrito';

    protected $fillable = [
        'usuario_id',
        'producto_id',
        'cantidad',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id', 'galy_user_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
