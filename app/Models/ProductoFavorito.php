<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductoFavorito extends Model
{
    use \App\Models\Concerns\UsesGalyConnection;

    protected $table = 'favoritos';

    protected $fillable = [
    'usuario_id',
    'producto_id',
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
