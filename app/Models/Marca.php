<?php

namespace App\Models;

use App\Models\Concerns\UsesGalyConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Marca extends Model
{
    use HasFactory;
    use UsesGalyConnection;

    protected $fillable = [
        'nombre',
        'slug',
        'imagen',
        'activo',
    ];

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->imagen || ! Storage::disk('public')->exists($this->imagen)) {
            return null;
        }

        return asset('storage/'.$this->imagen);
    }

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }
}
