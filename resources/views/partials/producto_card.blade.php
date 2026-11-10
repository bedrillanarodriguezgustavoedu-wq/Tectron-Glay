@php
    $pcUrl = url('/producto/' . $producto->id);
    $pcEnOferta = (bool) $producto->en_oferta;
    $pcPrecio = $pcEnOferta ? $producto->precio_oferta : $producto->precio_venta;
    $pcDescuento = (int) ($producto->descuento ?? 0);
    if (!$pcDescuento && $pcEnOferta && $producto->precio_venta > 0 && $producto->precio_oferta > 0) {
        $pcDescuento = (int) round((1 - ($producto->precio_oferta / $producto->precio_venta)) * 100);
    }
    $pcSinStock = (int) ($producto->stock ?? 0) <= 0;
    $pcRating = (float) ($producto->rating_avg ?? 0);
    $pcRatingCount = (int) ($producto->rating_count ?? 0);
    $es_favorito = false;
    if (Auth::check() && isset($favoritos_ids)) {
        $es_favorito = $favoritos_ids->contains($producto->id);
    }
@endphp

<article class="product-card">
    {{-- Imagen --}}
    <div class="pc-media">
        <a href="{{ $pcUrl }}" aria-label="{{ $producto->nombre }}">
            <img src="{{ $producto->imagen_principal_url }}"
                 alt="{{ $producto->nombre }}"
                 loading="lazy"
                 decoding="async">
        </a>

        <div class="pc-badges">
            @if ($pcEnOferta && $pcDescuento > 0)
                <span class="pc-badge pc-badge--offer theme-offer-badge">-{{ $pcDescuento }}%</span>
            @elseif ($producto->nuevo)
                <span class="pc-badge pc-badge--new">Nuevo</span>
            @endif
            @if ($pcSinStock)
                <span class="pc-badge pc-badge--out">Agotado</span>
            @endif
        </div>

        <button type="button"
                class="pc-fav toggle-fav-btn {{ $es_favorito ? 'is-active' : '' }}"
                data-id="{{ $producto->id }}"
                aria-label="{{ $es_favorito ? 'Quitar de favoritos' : 'Agregar a favoritos' }}"
                title="Favoritos">
            <i class="bi {{ $es_favorito ? 'bi-heart-fill text-danger' : 'bi-heart' }}" aria-hidden="true"></i>
        </button>
    </div>

    {{-- Contenido --}}
    <div class="pc-body">
        @if ($producto->marca_nombre)
            <span class="pc-brand">{{ $producto->marca_nombre }}</span>
        @endif

        <h3 class="pc-title">
            <a href="{{ $pcUrl }}">{{ $producto->nombre }}</a>
        </h3>

        @if ($pcRatingCount > 0)
            <div class="pc-rating" aria-label="Calificación {{ number_format($pcRating, 1) }} de 5">
                @for ($i = 1; $i <= 5; $i++)
                    @if ($i <= floor($pcRating))
                        <i class="bi bi-star-fill" aria-hidden="true"></i>
                    @elseif ($i == floor($pcRating) + 1 && ($pcRating - floor($pcRating)) >= 0.5)
                        <i class="bi bi-star-half" aria-hidden="true"></i>
                    @else
                        <i class="bi bi-star" aria-hidden="true"></i>
                    @endif
                @endfor
                <span class="pc-rating-count">({{ number_format($pcRating, 1) }})</span>
            </div>
        @endif

        <div class="pc-price">
            @if ($pcEnOferta)
                <span class="pc-price-now is-offer">{{ \App\Models\Ajuste::formatear_precio($pcPrecio) }}</span>
                <span class="pc-price-old">{{ \App\Models\Ajuste::formatear_precio($producto->precio_venta) }}</span>
                @if ($pcDescuento > 0)
                    <span class="pc-save">-{{ $pcDescuento }}%</span>
                @endif
            @else
                <span class="pc-price-now">{{ \App\Models\Ajuste::formatear_precio($pcPrecio) }}</span>
            @endif
        </div>

        @unless ($pcSinStock)
            <span class="pc-stock">Disponible</span>
        @else
            <span class="pc-stock is-out">Sin stock</span>
        @endunless

        <div class="pc-actions">
            <a href="{{ $pcUrl }}" class="btn btn-detail" title="Ver detalle" aria-label="Ver detalle de {{ $producto->nombre }}">
                <i class="bi bi-eye" aria-hidden="true"></i>
            </a>

            @if (! $pcSinStock)
                <button type="button"
                        class="btn btn-primary btn-cart add-to-cart-btn theme-btn-cart"
                        data-id="{{ $producto->id }}">
                    <i class="bi bi-bag-plus" aria-hidden="true"></i>
                    <span>Agregar</span>
                </button>
            @else
                <button type="button" class="btn btn-secondary btn-cart" disabled>
                    <i class="bi bi-x-circle" aria-hidden="true"></i>
                    <span>Agotado</span>
                </button>
            @endif
        </div>
    </div>
</article>
