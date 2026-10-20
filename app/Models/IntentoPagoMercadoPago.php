<?php

namespace App\Models;

use App\Models\Concerns\UsesGalyConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class IntentoPagoMercadoPago extends Model
{
    use UsesGalyConnection, HasUuids;

    public const ESTADO_INICIANDO = 'iniciando';
    public const ESTADO_ESPERANDO = 'esperando_pago';
    public const ESTADO_CONFIRMADO = 'confirmado';
    public const ESTADO_FALLIDO = 'fallido';
    public const ESTADO_EXPIRADO = 'expirado';

    /** Cobros que Mercado Pago devolvió al cliente después de aprobarlos. */
    public const ESTADOS_DE_REVERSION = ['refunded', 'charged_back'];

    protected $table = 'intentos_pago_mercadopago';

    protected $primaryKey = 'referencia_externa';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'referencia_externa',
        'usuario_id',
        'firma_carrito',
        'monto',
        'divisa',
        'estado',
        'mercadopago_order_id',
        'mercadopago_payment_id',
        'estado_mercadopago',
        'detalle_estado_mercadopago',
        'pedido_id',
        'direccion_id',
        'direccion_envio',
        'modalidad_entrega',
        'opcion_envio',
        'items',
        'reserva_ids',
        'checkout_url',
        'expira_at',
        'confirmado_at',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'items' => 'array',
        'reserva_ids' => 'array',
        'expira_at' => 'datetime',
        'confirmado_at' => 'datetime',
    ];

    public function orden()
    {
        return $this->belongsTo(Orden::class, 'pedido_id');
    }

    public function estaVigente(): bool
    {
        return $this->estado === self::ESTADO_ESPERANDO && $this->expira_at && $this->expira_at->isFuture();
    }

    /**
     * Mercado Pago reportó el cobro como aprobado, pero el pedido no se pudo
     * confirmar solo (monto, divisa o referencia no cuadran). Lo ve un humano.
     */
    public function requiereRevisionManual(): bool
    {
        return $this->estado_mercadopago === 'approved' && $this->estado !== self::ESTADO_CONFIRMADO;
    }

    /**
     * El dinero entró y Mercado Pago lo devolvió al cliente (reintegro o
     * chargeback). El pedido no se toca solo: lo decide la tienda.
     */
    public function cobroRevertido(): bool
    {
        return in_array((string) $this->estado_mercadopago, self::ESTADOS_DE_REVERSION, true);
    }

    public function getEstadoTexto(): string
    {
        return match ($this->estado) {
            self::ESTADO_INICIANDO => 'Abriendo el pago',
            self::ESTADO_ESPERANDO => 'Esperando el pago',
            self::ESTADO_CONFIRMADO => 'Pago confirmado',
            self::ESTADO_FALLIDO => 'Pago rechazado',
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
