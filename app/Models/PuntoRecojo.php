<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PuntoRecojo extends Model
{
    protected $table = 'puntos_recojo';

    protected $fillable = [
        'nombre',
        'direccion',
        'horario',
        'telefono',
        'provincia_id',
        'orden',
        'activo',
    ];

    protected $casts = [
        'orden' => 'integer',
        'activo' => 'boolean',
    ];

    public function provincia()
    {
        return $this->belongsTo(Provincia::class, 'provincia_id');
    }

    public function scopeActivos($consulta)
    {
        return $consulta->where('activo', true)->orderBy('orden')->orderBy('nombre');
    }

    /**
     * Locales propios de la tienda: los que se ofrecen bajo «Recojo en tienda».
     */
    public function scopeDeTienda($consulta)
    {
        return $consulta->whereNull('provincia_id');
    }

    /**
     * Agencias de «Envío a provincias», agrupadas por provincia para que el
     * carrito solo muestre las de la ciudad donde va el pedido.
     */
    public static function agenciasPorProvincia()
    {
        return static::activos()->whereNotNull('provincia_id')->get()->groupBy('provincia_id');
    }

    /**
     * Lo que queda guardado en `pedidos.direccion_envio`. Es texto y no una
     * foreign key a propósito: `pedidos` es tabla heredada que también escribe
     * la app Android, y si el local se edita o se oculta después de la compra,
     * el pedido tiene que seguir diciendo dónde quedó la mercadería.
     */
    public function getTextoParaPedidoAttribute(): string
    {
        if ($this->provincia_id === null) {
            return 'Recojo en '.$this->nombre.' – '.$this->direccion;
        }

        $provincia = $this->provincia;

        return 'Agencia '.$this->nombre.' ('.$provincia?->nombre.', '.$provincia?->departamento?->nombre.') – '.$this->direccion;
    }
}
