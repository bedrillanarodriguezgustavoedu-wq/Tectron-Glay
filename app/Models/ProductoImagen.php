<?php

namespace App\Models;

use App\Models\Concerns\UsesGalyConnection;
use Database\Factories\ProductoImagenFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoImagen extends Model
{
    /** @use HasFactory<ProductoImagenFactory> */
    use HasFactory;

    use UsesGalyConnection;

    protected $table = 'producto_imagenes';

    protected $fillable = [
        'producto_id',
        'imagen',
    ];

    public function getUrlAttribute(): string
    {
        return self::resolveUrl($this->imagen);
    }

    /**
     * Base para mostrar fotos compartidas con GALY en pantallas que solo reciben
     * un prefijo. Si la tienda también sirve la carpeta, la URL se arma con el
     * host de la petición; `galy.public_url` está fijado a una máquina concreta.
     */
    public static function sharedImagesBaseUrl(): string
    {
        $carpeta = public_path('galy_api') . DIRECTORY_SEPARATOR . 'img';

        return is_dir($carpeta)
            ? asset('galy_api/img')
            : rtrim((string) config('galy.public_url'), '/') . '/img';
    }

    public static function sharedAssetUrl(?string $image): ?string
    {
        $image = trim((string) $image);

        if ($image === '' || filter_var($image, FILTER_VALIDATE_URL)) {
            return null;
        }

        $shared = 'galy_api/img/' . preg_replace('#^img/#', '', ltrim($image, '/\\'));

        return file_exists(public_path($shared)) ? asset($shared) : null;
    }

    public static function resolveUrl(?string $image): string
    {
        $image = trim((string) $image);
        $defaultImage = asset('storage/productos/default.webp');

        if ($image === '') {
            return $defaultImage;
        }

        if (filter_var($image, FILTER_VALIDATE_URL)) {
            return $image;
        }

        $localPath = ltrim($image, '/\\');
        $localAsset = 'storage/' . $localPath;

        if (file_exists(public_path($localAsset))) {
            return asset($localAsset);
        }

        // Las fotos que comparten la web y la app viven fuera de Laravel. Cuando
        // el propio servidor de la tienda también las sirve (public/galy_api/img),
        // la URL se arma con `asset()`, que sigue el host de la petición: el
        // dominio real o el túnel. `galy.api_url` en cambio está fijado a esta
        // máquina, y un cliente que no sea este PC vería la foto rota.
        if (($shared = self::sharedAssetUrl($image)) !== null) {
            return $shared;
        }

        $image = preg_replace('#^img/#', '', $localPath);
        $imagesPath = rtrim((string) config('galy.images_path'), '\\/');
        $externalFile = $imagesPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $image);

        $externalUrl = rtrim((string) config('galy.api_url'), '/') . '/img/' . implode(
            '/',
            array_map('rawurlencode', explode('/', $image))
        );

        if (is_file($externalFile)) {
            return $externalUrl;
        }

        return file_exists(public_path('storage/productos/default.webp')) ? $defaultImage : $externalUrl;
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
