<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PedidoHistorial extends Model
{
    use \App\Models\Concerns\UsesGalyConnection;

    protected $table = 'pedido_historials';

    protected $fillable = [
        'orden_id',
        'estado_anterior',
        'estado_nuevo',
        'fecha',
        'hora',
        'nota',
        'usuario_id',
    ];

    public function orden()
    {
        return $this->belongsTo(Orden::class, 'orden_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id', 'galy_user_id');
    }
}
