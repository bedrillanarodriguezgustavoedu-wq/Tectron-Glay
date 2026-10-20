<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resena extends Model
{
    use \App\Models\Concerns\UsesGalyConnection;

    protected $table = 'resenas';

    protected $fillable = [
        'producto_id',
        'usuario_id',
        'orden_id',
        'estrellas',
        'titulo',
        'comentario',
        'aprobado',
        'respuesta_admin',
        'fecha_respuesta',
    ];

    protected $casts = [
        'aprobado' => 'boolean',
        'fecha_respuesta' => 'datetime',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id', 'galy_user_id');
    }

    public function orden()
    {
        return $this->belongsTo(Orden::class, 'orden_id');
    }

    public function imagenes()
    {
        return $this->hasMany(ResenaImagen::class, 'resena_id')->orderBy('orden', 'asc');
    }

    /**
     * Boot function to register hooks that update product ratings cache automatically.
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($resena) {
            if ($resena->producto) {
                $resena->producto->recalculateRatingCache();
            }
        });

        static::deleted(function ($resena) {
            if ($resena->producto) {
                $resena->producto->recalculateRatingCache();
            }
        });
    }
}
