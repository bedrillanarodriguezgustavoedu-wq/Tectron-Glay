<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asesor extends Model
{
    protected $table = 'asesores';

    protected $fillable = [
        'nombre',
        'cargo',
        'whatsapp',
        'email',
        'horario',
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
