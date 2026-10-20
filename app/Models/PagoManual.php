<?php

namespace App\Models;

use App\Models\Concerns\UsesGalyConnection;
use Illuminate\Database\Eloquent\Model;

class PagoManual extends Model
{
    use UsesGalyConnection;

    protected $table = 'pedido_pagos_manuales';

    protected $fillable = [
        'pedido_id',
        'usuario_id',
        'metodo_pago',
        'numero_operacion',
        'comprobante_path',
        'estado',
        'comprobante_enviado_at',
        'confirmado_at',
        'confirmado_por',
    ];

    protected $casts = [
        'comprobante_enviado_at' => 'datetime',
        'confirmado_at' => 'datetime',
    ];

    public function orden()
    {
        return $this->belongsTo(Orden::class, 'pedido_id');
    }
}
