<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = [
        'imagen',
        'titulo',
        'subtitulo',
        'boton_texto',
        'enlace',
        'color_fondo',
        'activo',
        'portada',
        'orden',
        'fecha_publicacion',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'portada' => 'boolean',
        'orden' => 'integer',
        'fecha_publicacion' => 'datetime',
    ];

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->imagen) {
            return null;
        }

        if (filter_var($this->imagen, FILTER_VALIDATE_URL)) {
            return $this->imagen;
        }

        if (str_starts_with($this->imagen, 'banners/')) {
            return Storage::disk('public')->url($this->imagen);
        }

        if (str_starts_with($this->imagen, 'assets/')) {
            return asset($this->imagen);
        }

        if (($compartida = \App\Models\ProductoImagen::sharedAssetUrl($this->imagen)) !== null) {
            return $compartida;
        }

        return rtrim((string) config('galy.api_url'), '/').'/img/'.rawurlencode(basename($this->imagen));
    }
}
