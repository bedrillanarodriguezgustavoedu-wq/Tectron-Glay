<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asesor extends Model
{
    protected $table = 'asesores';

    protected $fillable = [
        'nombre',
        'cargo',
        'rubro',
        'whatsapp',
        'telefono',
        'email',
        'horario',
        'saludo',
        'foto',
        'orden',
        'activo',
    ];

    protected $casts = [
        'orden' => 'integer',
        'activo' => 'boolean',
    ];

    public function scopeActivos($consulta)
    {
        return $consulta->where('activo', true)->orderBy('orden')->orderBy('nombre');
    }

    /**
     * Acepta «987 654 321», «+51 987654321» o un enlace wa.me ya escrito.
     */
    public function getEnlaceWhatsAppAttribute(): ?string
    {
        $valor = trim((string) $this->whatsapp);

        if ($valor === '') {
            return null;
        }

        if (preg_match('~wa\.me/(\+?\d+)~i', $valor, $coincidencia)) {
            $digitos = preg_replace('/\D+/', '', $coincidencia[1]);
        } else {
            $digitos = preg_replace('/\D+/', '', $valor);
        }

        return strlen($digitos) >= 8 ? 'https://wa.me/'.$digitos : null;
    }

    /** Lo que llega al celular del asesor cuando el cliente pulsa WhatsApp. */
    public function mensajeWhatsApp(?string $producto = null): ?string
    {
        if (! $this->enlace_whatsapp) {
            return null;
        }

        $personalizado = trim((string) $this->saludo);

        if ($personalizado !== '') {
            return $producto ? $personalizado.' ('.$producto.')' : $personalizado;
        }

        $rubro = trim((string) $this->rubro);

        if ($producto) {
            return 'Hola '.$this->nombre.', vengo de la tienda virtual de GALY y quiero consultar por: '.$producto.'.';
        }

        return 'Hola '.$this->nombre.', vengo de la tienda virtual de GALY'
            .($rubro !== '' ? ' y necesito asesoría de '.$rubro.'.' : ' y necesito asesoría.');
    }

    /**
     * El enlace ya trae el mensaje escrito: el cliente solo pulsa enviar.
     */
    public function enlaceWhatsAppConMensaje(?string $producto = null): ?string
    {
        $mensaje = $this->mensajeWhatsApp($producto);

        return $mensaje === null ? null : $this->enlace_whatsapp.'?text='.rawurlencode($mensaje);
    }

    public function getEnlaceTelefonoAttribute(): ?string
    {
        $digitos = preg_replace('/\D+/', '', (string) $this->telefono);

        return strlen($digitos) >= 8 ? 'tel:'.$digitos : null;
    }

    /** El número como lo marcó el dueño, sin reescribirlo: sirve para copiarlo. */
    public function getNumeroWhatsAppAttribute(): ?string
    {
        return $this->enlace_whatsapp
            ? substr($this->enlace_whatsapp, strlen('https://wa.me/'))
            : null;
    }

    public function getRutaFotoAttribute(): ?string
    {
        if (! $this->foto) {
            return null;
        }

        return file_exists(public_path('storage/'.$this->foto)) ? asset('storage/'.$this->foto) : null;
    }

    public function getInicialesAttribute(): string
    {
        $partes = preg_split('/\s+/', trim($this->nombre)) ?: [];

        $iniciales = collect($partes)
            ->filter()
            ->take(2)
            ->map(fn ($parte) => mb_strtoupper(mb_substr($parte, 0, 1)))
            ->implode('');

        return $iniciales !== '' ? $iniciales : 'GA';
    }
}
