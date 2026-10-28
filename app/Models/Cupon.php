<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cupon extends Model
{
    // Un cupón nunca puede dejar un producto gratis: se limita el porcentaje.
    public const PORCENTAJE_MINIMO = 1;

    public const PORCENTAJE_MAXIMO = 90;

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

    /**
     * Reglas gemelas a `galy_api/coupon_api_common.php`: mismo código normalizado,
     * mismo tope de 90 % y mismo redondeo a centavos. Si una de las dos cambia sin
     * la otra, la app y la web cobrarían montos distintos por el mismo cupón.
     *
     * @return array{codigo: string|null, porcentaje: int, descuento: float, mensaje: string|null}
     */
    public static function aplicar(?string $codigo, float $subtotal): array
    {
        $codigo = self::normalizarCodigo((string) $codigo);

        if ($codigo === '' || strlen($codigo) > 20 || preg_match('/^[A-Z0-9][A-Z0-9 _\-]*$/', $codigo) !== 1) {
            return self::sinDescuento('Escribe un código válido (letras, números, espacio, guion o bajo guion).');
        }

        // LOWER() en lugar de `codigo = ?`: en MySQL da igual por la colación, pero
        // en sqlite un cupón guardado en minúsculas simplemente no coincidiría.
        $cupon = static::vigentes()
            ->whereRaw('LOWER(codigo) = ?', [strtolower($codigo)])
            ->first();

        if (! $cupon) {
            return self::sinDescuento('Ese cupón no existe, está pausado o ya venció.');
        }

        $porcentaje = (int) $cupon->descuento_porcentaje;

        if ($porcentaje < self::PORCENTAJE_MINIMO) {
            return self::sinDescuento('Ese cupón no tiene un descuento válido.');
        }

        $porcentaje = min($porcentaje, self::PORCENTAJE_MAXIMO);
        $subtotal = max(0.0, round($subtotal, 2));
        $descuento = round(min($subtotal * $porcentaje / 100, $subtotal), 2);

        return [
            'codigo' => (string) $cupon->codigo,
            'porcentaje' => $porcentaje,
            'descuento' => $descuento,
            'mensaje' => null,
        ];
    }

    /** @return array{codigo: string|null, porcentaje: int, descuento: float, mensaje: string} */
    private static function sinDescuento(string $mensaje): array
    {
        return ['codigo' => null, 'porcentaje' => 0, 'descuento' => 0.0, 'mensaje' => $mensaje];
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
