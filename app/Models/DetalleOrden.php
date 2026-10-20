<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleOrden extends Model
{
    use \App\Models\Concerns\UsesGalyConnection;

    protected $table = 'pedido_productos';

    protected $fillable = [
        'orden_id',
        'producto_id',
        'cantidad',
        'precio',
];

    public function getOrdenIdAttribute($value)
    {
        return $this->attributes['pedido_id'] ?? $value;
    }

    public function setOrdenIdAttribute($value): void
    {
        $this->attributes['pedido_id'] = $value;
    }

    public function orden()
    {
        return $this->belongsTo(Orden::class, 'pedido_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
