<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cupon extends Model
{
    protected $table = 'cupones';

    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'descuento_porcentaje',
        'fecha_expiracion',
        'activo',
    ];

    protected $casts = [
        'descuento_porcentaje' => 'integer',
        'fecha_expiracion' => 'date:Y-m-d',
        'activo' => 'boolean',
    ];

    public static function normalizarCodigo(string $codigo): string
    {
        return strtoupper(trim($codigo));
    }

    public function scopeVigentes($consulta)
    {
        return $consulta
            ->where('activo', true)
            ->where(function ($q) {
                $q->whereNull('fecha_expiracion')
                    ->orWhereDate('fecha_expiracion', '>=', now()->toDateString());
            });
    }

    public function getEstadoAttribute(): string
    {
        if (! $this->activo) {
            return 'Pausado';
        }

        if ($this->fecha_expiracion
            && $this->fecha_expiracion->toDateString() < now()->toDateString()) {
            return 'Vencido';
        }

        return 'Vigente';
    }
}
