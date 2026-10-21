<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Orden extends Model
{
    use \App\Models\Concerns\UsesGalyConnection;

    protected $table = 'pedidos';

    protected $fillable = [
        'usuario_id',
        'total',
        'divisa',
        'estado_pago',
        'estado_orden',
        'transaccion_id',
        'direccion_envio',
        'nota',
        'modalidad_entrega',
        'direccion_id',
        'metodo_pago',
    ];

    /**
     * La app Android crea sus pedidos con la API heredada, que escribe
     * `pedidos.fecha` y deja `created_at` en NULL: para esas filas la única
     * fecha real es la heredada.
     */
    public function getFechaRegistroAttribute(): ?\Carbon\Carbon
    {
        if ($this->created_at) {
            return $this->created_at;
        }

        $fecha = $this->attributes['fecha'] ?? null;

        return $fecha ? \Carbon\Carbon::parse($fecha) : null;
    }

    public function getEstadoOrdenAttribute($value)
    {
        $estado = strtoupper((string) ($this->attributes['estado'] ?? $value ?? ''));

        return match ($estado) {
            'PENDIENTE', 'EN PROCESO' => 'En proceso',
            'RECIBIDO' => 'Recibido',
            'ENVIADO' => 'Enviado',
            'EN TRANSPORTE' => 'En transporte',
            'ENTREGADO' => 'Entregado',
            'LISTO PARA SU RECOJO' => 'Listo para su recojo',
            default => ucfirst(strtolower($estado)),
        };
    }

    public function setEstadoOrdenAttribute($value): void
    {
        $estado = strtoupper(trim((string) $value));
        $this->attributes['estado'] = match ($estado) {
            'EN PROCESO' => 'PENDIENTE',
            'LISTO PARA SU RECOJO' => 'LISTO PARA SU RECOJO',
            default => $estado,
        };
    }

    public function getModalidadEntregaAttribute($value)
    {
        return match (strtoupper((string) ($this->attributes['metodo_entrega'] ?? $value))) {
            'RECOJO' => 'recojo',
            'PROVINCIA' => 'provincia',
            default => 'envio',
        };
    }

    public function setModalidadEntregaAttribute($value): void
    {
        // La columna heredada es `metodo_entrega` y la comparten la app Android y
        // el panel PHP como texto libre, así que un valor nuevo no rompe a nadie.
        $this->attributes['metodo_entrega'] = match (strtolower((string) $value)) {
            'recojo' => 'RECOJO',
            'provincia' => 'PROVINCIA',
            default => 'DOMICILIO',
        };
    }

    public function getDireccionEnvioAttribute($value)
    {
        return $this->attributes['direccion'] ?? $value;
    }

    public function setDireccionEnvioAttribute($value): void
    {
        $this->attributes['direccion'] = $value;
    }

    /**
     * La columna heredada es texto libre que escriben la web, la app Android y el
     * panel, casi siempre como «Alias: casa | Dirección: jr cristo rey | …», y en
     * la pantalla del cliente eso es un muro ilegible que repite la calle. Se
     * limpia solo al mostrar: el pedido conserva su texto histórico, que es lo que
     * ya vio la tienda cuando despachó.
     *
     * @return list<string>
     */
    public function direccion_envio_lineas(): array
    {
        $lineas = [];
        $vistas = [];

        foreach (preg_split('/\s*\|\s*/', (string) $this->direccion_envio) ?: [] as $parte) {
            $parte = trim($parte);

            if (preg_match('/^alias\s*:/iu', $parte)) {
                continue;
            }

            $valor = trim((string) preg_replace(
                '/^(direcci[oó]n|referencia|ref|provincia|departamento|pa[ií]s|c\.?\s?p\.?|tel[eé]fono|telf)\s*:\s*/iu',
                '',
                $parte
            ));

            // «N/A» es lo que guarda una dirección sin provincia: no le sirve a nadie.
            if ($valor === '' || in_array(strtoupper($valor), ['N/A', 'NA'], true)) {
                continue;
            }

            $clave = mb_strtolower($valor);

            if (in_array($clave, $vistas, true)) {
                continue;
            }

            $vistas[] = $clave;
            $lineas[] = $valor;
        }

        return $lineas;
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id', 'galy_user_id');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleOrden::class, 'pedido_id');
    }

    public function historial()
    {
        return $this->hasMany(PedidoHistorial::class, 'orden_id')->orderBy('created_at', 'asc');
    }

    public function direccion()
    {
        return $this->belongsTo(Direccion::class, 'direccion_id');
    }

    public function intentosPago()
    {
        return $this->hasMany(IntentoPagoMercadoPago::class, 'pedido_id')->orderByDesc('created_at');
    }

    public function pagoManual()
    {
        return $this->hasOne(PagoManual::class, 'pedido_id');
    }

    public function getFlujoEstados()
    {
        if ($this->modalidad_entrega === 'recojo') {
            return ['En proceso', 'Recibido', 'Listo para su recojo'];
        }
        return ['En proceso', 'Recibido', 'Enviado', 'En transporte', 'Entregado'];
    }

    public function getSiguienteEstado()
    {
        $flujo = $this->getFlujoEstados();
        $index = array_search($this->estado_orden, $flujo);
        if ($index !== false && $index < count($flujo) - 1) {
            return $flujo[$index + 1];
        }
        return null;
    }

    public function getBadgeColor($estado = null)
    {
        $estado = $estado ?: $this->estado_orden;
        switch ($estado) {
            case 'En proceso':
                return 'primary';
            case 'Recibido':
                return 'info';
            case 'Enviado':
                return 'warning';
            case 'En transporte':
                return 'secondary';
            case 'Entregado':
            case 'Listo para su recojo':
                return 'success';
            default:
                return 'dark';
        }
    }

    public function getModalidadTexto()
    {
        return match ($this->modalidad_entrega) {
            'recojo' => 'Recojo en tienda',
            'provincia' => 'Envío a provincias',
            default => 'Envío a domicilio',
        };
    }

    public function getMetodoPagoTexto()
    {
        switch ($this->metodo_pago) {
            case 'paypal':
                return 'PayPal';
            case 'mercadopago':
                return 'Mercado Pago (checkout seguro)';
            case 'yape':
                return 'Yape';
            case 'plin':
                return 'Plin';
            case 'bcp':
                return 'Transferencia BCP';
            case 'pagar_en_tienda':
                return 'Pagar al recoger';
            case 'tarjeta':
                return 'Tarjeta de Crédito/Débito';
            default:
                return ucfirst($this->metodo_pago);
        }
    }

    public function getEstadoPagoBadgeColor()
    {
        switch (strtolower($this->estado_pago)) {
            case 'pagado':
            case 'completed':
            case 'approved':
                return 'success';
            case 'pendiente':
            case 'pendiente de pago':
                return 'warning';
            case 'reembolsado':
                return 'info';
            case 'cancelado':
                return 'danger';
            default:
                return 'secondary';
        }
    }

    public function getEstadoPagoTexto()
    {
        $status = strtolower($this->estado_pago);
        if ($status === 'completed' || $status === 'approved' || $status === 'pagado') {
            return 'Pagado';
        }
        if ($status === 'pendiente' || $status === 'pendiente de pago') {
            return 'Pendiente';
        }
        if ($status === 'reembolsado') {
            return 'Reembolsado';
        }
        if ($status === 'cancelado') {
            return 'Cancelado';
        }
        return ucfirst($this->estado_pago);
    }

    public function resenas()
    {
        return $this->hasMany(Resena::class, 'orden_id');
    }
}
