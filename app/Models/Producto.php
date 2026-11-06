<?php

namespace App\Models;

use App\Models\Concerns\UsesGalyConnection;
use Database\Factories\ProductoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    /** @use HasFactory<ProductoFactory> */
    use HasFactory;

    use SoftDeletes;

    use UsesGalyConnection;

    protected $fillable = [
        'categoria_id',
        'marca_id',
        'nombre',
        'codigo',
        'descripcion_corta',
        'descripcion_larga',
        'detalles_tecnicos',
        'precio_compra',
        'precio_venta',
        'precio_oferta',
        'descuento',
        'stock',
        'destacado',
        'nuevo',
        'recomendado',
        'mas_vendido',
        'en_oferta',
        'precio_oferta_manual',
        'rating_avg',
        'rating_count',
    ];

    public function getPrecioVentaAttribute($value)
    {
        return $this->attributes['precio_normal'] ?? $value;
    }

    public function setPrecioVentaAttribute($value): void
    {
        $this->attributes['precio_normal'] = $value;
    }

    public function getRatingAvgAttribute($value)
    {
        return $this->attributes['calificacion'] ?? $value;
    }

    public function setRatingAvgAttribute($value): void
    {
        $this->attributes['calificacion'] = $value;
    }

    public function getRatingCountAttribute($value)
    {
        return $this->attributes['num_revisiones'] ?? $value;
    }

    public function setRatingCountAttribute($value): void
    {
        $this->attributes['num_revisiones'] = $value;
    }

    public function getMarcaNombreAttribute(): ?string
    {
        $legacyBrand = $this->attributes['marca'] ?? null;
        if (is_string($legacyBrand) && trim($legacyBrand) !== '') {
            return $legacyBrand;
        }

        $brand = $this->getRelationValue('marca');

        return $brand instanceof Marca ? $brand->nombre : null;
    }

    public function getImagenPrincipalUrlAttribute(): string
    {
        $principal = trim((string) ($this->attributes['imagen_url'] ?? ''));

        // La foto que el admin marcó como principal manda. Antes se devolvía
        // siempre la primera de la galería, así que al subir una imagen nueva
        // la ficha seguía mostrando la anterior.
        if ($principal !== '') {
            $image = $this->imagenes->first(
                fn (ProductoImagen $item) => basename((string) $item->imagen) === basename($principal)
            );

            if ($image !== null) {
                return $image->url;
            }

            // La principal también puede ser una foto que aún no está en la
            // galería (por ejemplo, cargada desde el admin de la app). Si el
            // archivo existe, debe mandar igual.
            $resuelta = ProductoImagen::resolveUrl($principal);
            if ($resuelta !== asset('storage/productos/default.webp')) {
                return $resuelta;
            }
        }

        $primaryImage = $this->imagenes->first();

        return $primaryImage?->url ?? ProductoImagen::resolveUrl($principal !== '' ? $principal : null);
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function marca()
    {
        return $this->belongsTo(Marca::class);
    }

    public function imagenes()
    {
        return $this->hasMany(ProductoImagen::class);
    }

    public function favoritos()
    {
        return $this->hasMany(ProductoFavorito::class, 'producto_id');
    }

    public function carritos()
    {
        return $this->hasMany(Carrito::class, 'producto_id');
    }

    public function detallesOrden()
    {
        return $this->hasMany(DetalleOrden::class, 'producto_id');
    }

    public function ofertas()
    {
        return $this->belongsToMany(Oferta::class, 'oferta_producto');
    }

    /**
     * Sanitizes input HTML, stripping harmful script tags, inline event listeners,
     * and malicious javascript/data URIs while fully preserving formatting, colors, and tables.
     *
     * @param  string|null  $html
     * @return string
     */
    public static function sanitizeHtml($html)
    {
        if (empty($html)) {
            return '';
        }

        $dom = new \DOMDocument;

        // Suppress libxml warnings for HTML5 elements DOMDocument doesn't know about
        libxml_use_internal_errors(true);

        // Load with UTF-8 prefix to preserve non-ASCII characters
        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        // 1. Remove dangerous HTML tags
        $dangerousTags = ['script', 'iframe', 'embed', 'object', 'link', 'style', 'applet', 'meta', 'form'];
        foreach ($dangerousTags as $tag) {
            $elements = $dom->getElementsByTagName($tag);
            while ($elements->length > 0) {
                $item = $elements->item(0);
                $item->parentNode->removeChild($item);
            }
        }

        // 2. Scan all remaining elements for dangerous attributes
        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//*[@*]'); // Elements with any attributes

        foreach ($nodes as $node) {
            $attributes = iterator_to_array($node->attributes);
            foreach ($attributes as $attrName => $attrNode) {
                $nameLower = strtolower($attrName);

                // Strip event handlers (onload, onclick, onerror, etc.)
                if (str_starts_with($nameLower, 'on')) {
                    $node->removeAttribute($attrName);

                    continue;
                }

                // Strip URI schemes containing script execution (javascript: or data:)
                $valueLower = strtolower(trim($attrNode->value));
                if (str_starts_with($valueLower, 'javascript:') || str_starts_with($valueLower, 'data:')) {
                    $node->removeAttribute($attrName);
                }
            }
        }

        return $dom->saveHTML();
    }

    public function resenas()
    {
        return $this->hasMany(Resena::class, 'producto_id');
    }

    public function recalculateRatingCache()
    {
        $approvedReviews = $this->resenas()->where('aprobado', true);
        $count = $approvedReviews->count();
        $avg = $approvedReviews->avg('estrellas') ?? 0.00;

        $this->rating_count = $count;
        $this->rating_avg = round($avg, 2);
        $this->saveQuietly();
    }

    public function scopeFiltrar($query, $request)
    {
        // 1. Text Search
        if ($request->filled('buscar') || $request->filled('producto')) {
            $search = $request->input('buscar') ?: $request->input('producto');
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'LIKE', "%$search%")
                    ->orWhere('descripcion_corta', 'LIKE', "%$search%")
                    ->orWhereHas('categoria', function ($c) use ($search) {
                        $c->where('nombre', 'LIKE', "%$search%");
                    })
                    ->orWhereHas('marca', function ($m) use ($search) {
                        $m->where('nombre', 'LIKE', "%$search%");
                    });
            });
        }

        // 2. Category Filter
        if ($request->filled('categoria')) {
            $categorias = (array) $request->input('categoria');
            $query->whereHas('categoria', function ($q) use ($categorias) {
                $q->whereIn('slug', $categorias)->orWhereIn('id', $categorias);
            });
        }

        // 3. Brand Filter
        if ($request->filled('marca')) {
            $marcas = (array) $request->input('marca');
            $query->whereHas('marca', function ($q) use ($marcas) {
                $q->whereIn('slug', $marcas)->orWhereIn('id', $marcas);
            });
        }

        // 4. Price Filter
        if ($request->filled('precio_min')) {
            $query->whereRaw('COALESCE(NULLIF(precio_oferta, 0), precio_normal) >= ?', [$request->precio_min]);
        }
        if ($request->filled('precio_max')) {
            $query->whereRaw('COALESCE(NULLIF(precio_oferta, 0), precio_normal) <= ?', [$request->precio_max]);
        }

        // 5. Availability Filter
        if ($request->filled('disponibilidad')) {
            $disp = (array) $request->input('disponibilidad');
            $query->where(function ($q) use ($disp) {
                if (in_array('in_stock', $disp)) {
                    $q->orWhere('stock', '>', 0);
                }
                if (in_array('out_of_stock', $disp)) {
                    $q->orWhere('stock', '=', 0);
                }
            });
        }

        // 6. Rating Filter
        if ($request->filled('valoracion')) {
            $val = $request->input('valoracion');
            $query->where('calificacion', '>=', $val);
        }

        // 7. Product Status Filter
        if ($request->filled('estado')) {
            $estados = (array) $request->input('estado');
            foreach ($estados as $est) {
                if ($est === 'nuevo') {
                    $query->where('nuevo', true);
                } elseif ($est === 'en_oferta') {
                    $query->where('en_oferta', true);
                } elseif ($est === 'mas_vendido') {
                    $query->where('mas_vendido', true);
                } elseif ($est === 'destacado') {
                    $query->where('destacado', true);
                }
            }
        }

        // 8. Technical Specifications Filters (Processor, RAM, Almacenamiento, Tarjeta gráfica, Pantalla, Sistema Operativo, Color)
        $techFilters = ['procesador', 'ram', 'ssd', 'grafica', 'pantalla', 'so', 'color'];
        foreach ($techFilters as $tf) {
            if ($request->filled($tf)) {
                $vals = (array) $request->input($tf);
                $query->where(function ($q) use ($vals) {
                    foreach ($vals as $val) {
                        $q->orWhere('detalles_tecnicos', 'LIKE', "%$val%")
                            ->orWhere('nombre', 'LIKE', "%$val%");
                    }
                });
            }
        }

        // 9. Sorting
        if ($request->filled('orden')) {
            $orden = $request->input('orden');
            switch ($orden) {
                case 'recientes':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'mas_vendidos':
                    $query->orderBy('mas_vendido', 'desc')->orderBy('id', 'desc');
                    break;
                case 'mejor_calificados':
                    $query->orderBy('rating_avg', 'desc');
                    break;
                case 'precio_menor':
                    $query->orderByRaw('COALESCE(NULLIF(precio_oferta, 0), precio_normal) ASC');
                    break;
                case 'precio_mayor':
                    $query->orderByRaw('COALESCE(NULLIF(precio_oferta, 0), precio_normal) DESC');
                    break;
                case 'nombre_az':
                    $query->orderBy('nombre', 'asc');
                    break;
                case 'nombre_za':
                    $query->orderBy('nombre', 'desc');
                    break;
            }
        } else {
            $query->orderBy('nombre', 'asc');
        }

        return $query;
    }
}
