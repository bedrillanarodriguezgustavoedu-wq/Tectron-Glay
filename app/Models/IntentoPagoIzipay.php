<?php

namespace App\Models;

use App\Models\Concerns\UsesGalyConnection;
use Illuminate\Database\Eloquent\Model;

class IntentoPagoIzipay extends Model
{
    use UsesGalyConnection;

    public const ESTADO_INICIANDO = 'iniciando';
    public const ESTADO_ESPERANDO = 'esperando_pago';
    public const ESTADO_CONFIRMADO = 'confirmado';
    public const ESTADO_FALLIDO = 'fallido';
    public const ESTADO_EXPIRADO = 'expirado';

    protected $table = 'intentos_pago_izipay';

    protected $fillable = [
        'referencia',
        'usuario_id',
        'pedido_id',
        'monto',
        'divisa',
        'estado',
        'form_token',
        'izipay_uuid',
        'estado_izipay',
        'detalle_estado_izipay',
        'metodo_izipay',
        'expira_at',
        'pagado_at',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'expira_at' => 'datetime',
        'pagado_at' => 'datetime',
    ];

    public function orden()
    {
        return $this->belongsTo(Orden::class, 'pedido_id');
    }

    public function estaVigente(): bool
    {
        return $this->estado === self::ESTADO_ESPERANDO
            && (! $this->expira_at || $this->expira_at->isFuture());
    }

    /**
     * Izipay reportó el cobro como pagado, pero el pedido no se pudo confirmar
     * solo (monto, divisa o referencia no cuadran). Lo revisa una persona.
     */
    public function requiereRevisionManual(): bool
    {
        return $this->estado_izipay === 'PAID'
            && $this->estado !== self::ESTADO_CONFIRMADO;
    }

    public function getEstadoTexto(): string
    {
        return match ($this->estado) {
            self::ESTADO_INICIANDO => 'Abriendo el pago',
            self::ESTADO_ESPERANDO => 'Esperando el pago',
            self::ESTADO_CONFIRMADO => 'Pago confirmado',
            self::ESTADO_FALLIDO => 'Pago no completado',
            self::ESTADO_EXPIRADO => 'Pago expirado',
            default => ucfirst((string) $this->estado),
        };
    }

    public function getBadgeColor(): string
    {
        return match ($this->estado) {
            self::ESTADO_CONFIRMADO => 'success',
            self::ESTADO_FALLIDO, self::ESTADO_EXPIRADO => 'danger',
            self::ESTADO_INICIANDO, self::ESTADO_ESPERANDO => 'warning text-dark',
            default => 'secondary',
        };
    }
}
