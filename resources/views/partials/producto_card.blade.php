<div class="product-card border rounded p-3 h-100 d-flex flex-column justify-content-between position-relative shadow-sm hover-brand-card" style="background: #fff; border-radius: 14px; transition: transform 0.3s ease, box-shadow 0.3s ease;">
    
    <!-- Discount Badge (Top-Left) -->
    @if ($producto->en_oferta && $producto->descuento)
        <div class="position-absolute top-0 start-0 p-2 z-3">
            <span class="badge rounded-pill px-2 py-1 theme-offer-badge" style="font-size: 11px; background-color: var(--color-offer-bg, #dc3545) !important;">
                -{{ $producto->descuento }}%
            </span>
        </div>
    @elseif ($producto->nuevo)
        <div class="position-absolute top-0 start-0 p-2 z-3">
            <span class="badge bg-primary rounded-pill px-2 py-1" style="font-size: 11px;">
                Nuevo
            </span>
        </div>
    @endif

    <!-- Favorite Toggle (Top-Right) -->
    <div class="position-absolute top-0 end-0 p-2 z-3">
        @php
            $es_favorito = false;
            if (Auth::check() && isset($favoritos_ids)) {
                $es_favorito = $favoritos_ids->contains($producto->id);
            }
        @endphp
        <button type="button" class="btn btn-light rounded-circle shadow-sm border p-0 d-flex align-items-center justify-content-center toggle-fav-btn" 
            data-id="{{ $producto->id }}" 
            style="width: 36px; height: 36px; transition: all 0.2s ease;">
            <i class="bi {{ $es_favorito ? 'bi-heart-fill text-danger' : 'bi-heart text-secondary' }} fs-5"></i>
        </button>
    </div>

    <!-- Image Area -->
    <div class="product-image-container text-center mb-3 position-relative overflow-hidden" style="height: 180px; display: flex; align-items: center; justify-content: center; border-radius: 8px; background-color: #f8fafc;">
        <a href="{{ url('/producto/' . $producto->id) }}" class="w-100 h-100 d-flex align-items-center justify-content-center">
            <img src="{{ $producto->imagen_principal_url }}" alt="{{ $producto->nombre }}" class="img-fluid product-img-hover" style="max-height: 150px; object-fit: contain; transition: transform 0.5s ease;">
        </a>
    </div>

    <!-- Content Area -->
    <div class="flex-grow-1 d-flex flex-column justify-content-between">
        <div class="mb-2">
            @if ($producto->marca_nombre)
                <span class="text-xs text-uppercase text-muted fw-bold d-block mb-1" style="font-size: 10px; letter-spacing: 0.5px;">{{ $producto->marca_nombre }}</span>
            @endif
            <h5 class="product-name fs-6 fw-bold mb-1" style="line-height: 1.4; height: 2.8em; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                <a href="{{ url('/producto/' . $producto->id) }}" class="text-dark text-decoration-none hover-primary">
                    {{ $producto->nombre }}
                </a>
            </h5>
            
            <div class="text-muted text-truncate mb-2" style="font-size: 12px;">{{ $producto->descripcion_corta }}</div>
            
            <!-- Stars rating -->
            @if (($producto->rating_count ?? 0) > 0)
                <div class="d-flex align-items-center gap-1 mb-2 text-warning" style="font-size: 0.8rem;">
                    @php
                        $avg_c = $producto->rating_avg ?? 0;
                        $fullStars_c = floor($avg_c);
                        $hasHalf_c = ($avg_c - $fullStars_c) >= 0.5;
                    @endphp
                    @for ($i = 1; $i <= 5; $i++)
                        @if ($i <= $fullStars_c)
                            <i class="bi bi-star-fill"></i>
                        @elseif ($i == $fullStars_c + 1 && $hasHalf_c)
                            <i class="bi bi-star-half"></i>
                        @else
                            <i class="bi bi-star"></i>
                        @endif
                    @endfor
                    <span class="text-muted small ms-1" style="font-size: 0.75rem;">
                        ({{ number_format($avg_c, 1) }})
                    </span>
                </div>
            @endif
            
            <div class="d-flex align-items-center gap-2 mb-2">
                @if($producto->stock > 0)
                    <span class="badge bg-success-subtle text-success rounded-pill px-2 py-0.5" style="font-size: 10px;">En Stock ({{ $producto->stock }})</span>
                @else
                    <span class="badge bg-danger-subtle text-danger rounded-pill px-2 py-0.5" style="font-size: 10px;">Agotado</span>
                @endif
            </div>
        </div>

        <!-- Price & Buttons -->
        <div>
            <div class="mb-3 d-flex align-items-baseline gap-2">
                @if ($producto->en_oferta)
                    <span class="text-danger fw-bold fs-5 theme-offer-price" style="color: var(--color-offer-bg, #dc3545) !important;">
                        {{ \App\Models\Ajuste::formatear_precio($producto->precio_oferta) }}
                    </span>
                    <span class="text-muted text-decoration-line-through small">
                        {{ \App\Models\Ajuste::formatear_precio($producto->precio_venta) }}
                    </span>
                @else
                    <span class="text-dark fw-bold fs-5">
                        {{ \App\Models\Ajuste::formatear_precio($producto->precio_venta) }}
                    </span>
                @endif
            </div>

            <div class="d-flex flex-column gap-2">
                <a href="{{ url('/producto/' . $producto->id) }}" class="btn btn-outline-primary btn-sm rounded-pill py-2 w-100 fw-bold d-flex align-items-center justify-content-center gap-1">
                    <i class="bi bi-eye"></i> Ver Detalle
                </a>
                
                @if($producto->stock > 0)
                    <button type="button" class="btn btn-primary btn-sm rounded-pill py-2 w-100 fw-bold add-to-cart-btn d-flex align-items-center justify-content-center gap-1 theme-btn-cart"
                        data-id="{{ $producto->id }}" style="background-color: var(--color-btn-cart-bg, #0d6efd) !important; border-color: var(--color-btn-cart-bg, #0d6efd) !important;">
                        <i class="bi bi-cart-plus"></i> Agregar al Carrito
                    </button>
                @else
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill py-2 w-100 fw-bold" disabled>
                        <i class="bi bi-x-circle"></i> Agotado
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
