@extends('layouts.web')

@php
    $precioParaBuscador = $producto->en_oferta ? $producto->precio_oferta : $producto->precio_venta;
    $descripcionParaBuscador = trim($producto->descripcion_corta ?: strip_tags((string) $producto->descripcion_larga));
@endphp

@section('title', $producto->nombre.' · '.\App\Models\Ajuste::formatear_precio($precioParaBuscador))
@section('meta_descripcion', \Illuminate\Support\Str::limit($descripcionParaBuscador, 155))

@section('content')
    <!-- Page Title -->
    <div class="page-title light-background">
        <div class="container d-lg-flex justify-content-between align-items-center">
            <h1 class="mb-2 mb-lg-0">Detalle del producto: {{ $producto->nombre }}</h1>
            <nav class="breadcrumbs">
                <ol>
                    <li><a href="{{ url('/') }}">Principal</a></li>
                    <li class="current">Detalle del producto</li>
                </ol>
            </nav>
        </div>
    </div><!-- End Page Title -->

    <!-- Product Details Section -->
    <section id="product-details" class="product-details section">

        <div class="container" data-aos="fade-up" data-aos-delay="100">

            <div class="row g-4">
                <!-- Product Gallery -->
                <div class="col-lg-7" data-aos="zoom-in" data-aos-delay="150">
                    <div class="product-gallery">
                        <div class="main-showcase">
                            <div class="image-zoom-container">
                                @php
                                    $imagen = $producto->imagen_principal_url;
                                @endphp
                                <img src="{{ $imagen }}" alt="Product Main"
                                    class="img-fluid main-product-image drift-zoom" id="main-product-image"
                                    data-zoom="{{ $imagen }}">

                                <div class="image-navigation">
                                    <button class="nav-arrow prev-image image-nav-btn prev-image" type="button">
                                        <i class="bi bi-chevron-left"></i>
                                    </button>
                                    <button class="nav-arrow next-image image-nav-btn next-image" type="button">
                                        <i class="bi bi-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="thumbnail-grid">

                            @foreach ($producto->imagenes as $item)
                                <div class="thumbnail-wrapper thumbnail-item active"
                                    data-image="{{ $item->url }}">
                                    <img src="{{ $item->url }}" alt="View {{ $loop->iteration }}"
                                        alt="Product Thumbnail" class="img-fluid">
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Product Details -->
                <div class="col-lg-5" data-aos="fade-left" data-aos-delay="200">
                    <div class="product-details">
                        <div class="product-badge-container">
                            <span class="badge-category">{{ $producto->categoria?->nombre ?? 'Sin categoría' }}</span>
                            <div class="rating-group">
                                <div class="stars text-warning">
                                    @php
                                        $avg_p = $producto->rating_avg ?? 0;
                                        $fullStars_p = floor($avg_p);
                                        $hasHalf_p = ($avg_p - $fullStars_p) >= 0.5;
                                    @endphp
                                    @for ($i = 1; $i <= 5; $i++)
                                        @if ($i <= $fullStars_p)
                                            <i class="bi bi-star-fill"></i>
                                        @elseif ($i == $fullStars_p + 1 && $hasHalf_p)
                                            <i class="bi bi-star-half"></i>
                                        @else
                                            <i class="bi bi-star"></i>
                                        @endif
                                    @endfor
                                </div>
                                <span class="review-text">({{ number_format($avg_p, 1) }} - {{ $producto->rating_count ?? 0 }} {{ ($producto->rating_count ?? 0) == 1 ? 'opinión' : 'opiniones' }})</span>
                            </div>
                        </div>

                        <h1 class="product-name">{{ $producto->nombre }}</h1>

                        <div class="pricing-section">
                            <div class="price-display">
                                @if ($producto->en_oferta)
                                    <span class="original-price text-decoration-line-through text-muted small me-2" style="font-size: 1.2rem;">{{ \App\Models\Ajuste::formatear_precio($producto->precio_venta) }}</span>
                                    <span class="sale-price text-danger fw-bold fs-2">{{ \App\Models\Ajuste::formatear_precio($producto->precio_oferta) }}</span>
                                @else
                                    <span class="sale-price">{{ \App\Models\Ajuste::formatear_precio($producto->precio_venta) }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="product-description">
                            <p>{{ $producto->descripcion_corta }}</p>
                        </div>

                        <div class="availability-status">
                            <div class="stock-indicator">
                                <i class="bi bi-check-circle-fill"></i>
                                <span class="stock-text">Disponible</span>
                            </div>
                            <div class="quantity-left">Solo quedan {{ $producto->stock }} productos</div>
                        </div>
                        <form action="{{ url('/carrito/agregar') }}" method="POST" id="product-purchase-form">
                            @csrf
                            <!-- Purchase Options -->
                            <div class="purchase-section">
                                <div class="quantity-control">
                                    <label class="control-label">Cantidad:</label>
                                    <div class="quantity-input-group">
                                        <div class="quantity-selector">
                                            <button class="quantity-btn decrease" type="button">
                                                <i class="bi bi-dash"></i>
                                            </button>
                                            <input type="number" name="cantidad" class="quantity-input" value="1" min="1"
                                                max="{{ $producto->stock }}">
                                            <button class="quantity-btn increase" type="button">
                                                <i class="bi bi-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="action-buttons">

                                    <input type="hidden" name="producto_id" value="{{ $producto->id }}">
                                    <button class="btn primary-action">
                                        <i class="bi bi-bag-plus"></i>
                                        agregar al carrito
                                    </button>
                        </form>
                        <form action="{{ route('web.reservas.store') }}" method="POST" class="mt-2">
                            @csrf
                            <input type="hidden" name="producto_id" value="{{ $producto->id }}">
                            <input type="hidden" name="cantidad" id="reservation-quantity" value="1">
                            <button type="submit" class="btn btn-outline-primary w-100">
                                <i class="bi bi-bookmark-check me-1"></i>Apartar por 24 horas
                            </button>
                            @error('cantidad')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                            @error('producto_id')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </form>
                        <form action="{{ url('/favoritos') }}" method="POST">
                            @csrf
                            <input type="hidden" name="producto_id" value="{{ $producto->id }}">
                            <button type="submit" class="btn icon-action">
                                <i class="bi bi-heart"></i>
                            </button>
                        </form>
                    </div>

                    <script>
                        document.querySelector('#product-purchase-form input[name="cantidad"]')?.addEventListener('input', event => {
                            document.getElementById('reservation-quantity').value = event.target.value;
                        });
                    </script>
                </div>

                <!-- Benefits List -->
                <div class="benefits-list">
                    <div class="benefit-item">
                        <i class="bi bi-truck"></i>
                        <span>Entrega gratuita en pedidos superiores a S/300</span>
                    </div>
                    <div class="benefit-item">
                        <i class="bi bi-arrow-clockwise"></i>
                        <span>Devoluciones sin complicaciones durante 30 días</span>
                    </div>
                    <div class="benefit-item">
                        <i class="bi bi-shield-check"></i>
                        <span>Garantia del fabricante de 3 años</span>
                    </div>
                    <div class="benefit-item">
                        <i class="bi bi-headset"></i>
                        <span>Atencion al cliente 24 horas al día, 7 días a la semana </span>
                    </div>
                </div>
            </div>
        </div>
        </div>

        <!-- Information Tabs -->
        <div class="row mt-5" data-aos="fade-up" data-aos-delay="300">
            <div class="col-12">
                <div class="info-tabs-container">
                    <nav class="tabs-navigation nav">
                        <button class="nav-link active" data-bs-toggle="tab"
                            data-bs-target="#ecommerce-product-details-5-overview" type="button">Descripcion</button>
                        @if(!empty($producto->detalles_tecnicos))
                            <button class="nav-link" data-bs-toggle="tab"
                                data-bs-target="#ecommerce-product-details-5-technical" type="button">Detalles Técnicos</button>
                        @endif
                        <button class="nav-link" data-bs-toggle="tab"
                            data-bs-target="#ecommerce-product-details-5-customer-reviews" type="button">Reviews
                            ({{ $producto->rating_count ?? 0 }})</button>
                    </nav>

                    <div class="tab-content">
                        <!-- Descripcion Tab-->
                        <div class="tab-pane fade show active" id="ecommerce-product-details-5-overview">
                            <div class="overview-content">
                                <div class="row g-4">
                                    <div class="col-lg-8">
                                        <div class="content-section">
                                            {!! $producto->descripcion_larga !!}
                                        </div>
                                    </div>


                                </div>
                            </div>
                        </div>

                        <!-- Detalles Tecnicos Tab-->
                        @if(!empty($producto->detalles_tecnicos))
                            <style>
                                .technical-content table {
                                    width: 100% !important;
                                    border-collapse: collapse;
                                    margin-bottom: 1.5rem;
                                }
                                .technical-content table th, 
                                .technical-content table td {
                                    padding: 0.75rem 1rem;
                                    border: 1px solid #e3e6f0;
                                    vertical-align: middle;
                                }
                                .technical-content table tr:nth-child(even) {
                                    background-color: #f8f9fc;
                                }
                                .technical-content table th {
                                    background-color: #f1f3f9;
                                    font-weight: 600;
                                    color: #4e73df;
                                }
                            </style>
                            <div class="tab-pane fade" id="ecommerce-product-details-5-technical">
                                <div class="technical-content">
                                    <div class="row g-4">
                                        <div class="col-md-12 table-responsive">
                                            {!! $producto->detalles_tecnicos !!}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Reviews Tab -->
                        <div class="tab-pane fade" id="ecommerce-product-details-5-customer-reviews">
                            @php
                                $resenas_aprobadas = $producto->resenas()->where('aprobado', true)->with(['usuario', 'imagenes'])->orderBy('created_at', 'desc')->get();
                                $total_resenas = $resenas_aprobadas->count();
                                $promedio = $total_resenas > 0 ? round($resenas_aprobadas->avg('estrellas'), 1) : 0;

                                $distribucion = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
                                foreach ($resenas_aprobadas as $r) {
                                    if (isset($distribucion[$r->estrellas])) {
                                        $distribucion[$r->estrellas]++;
                                    }
                                }

                                $orden_entregada = null;
                                if (auth()->check()) {
                                    $orden_entregada = \App\Models\Orden::where('usuario_id', auth()->id())
                                        ->where('estado_orden', 'Entregado')
                                        ->whereHas('detalles', function($q) use ($producto) {
                                            $q->where('producto_id', $producto->id);
                                        })
                                        ->whereDoesntHave('resenas', function($q) use ($producto) {
                                            $q->where('producto_id', $producto->id)->where('usuario_id', auth()->id());
                                        })
                                        ->first();
                                }
                                $ajuste_resenas = \App\Models\Ajuste::first() ?? new \App\Models\Ajuste;
                            @endphp

                            <div class="reviews-content">
                                <div class="reviews-header row g-4 align-items-center mb-4">
                                    <div class="col-md-5">
                                        <div class="rating-overview text-center p-3 bg-light rounded shadow-sm">
                                            <div class="score-display display-3 fw-bold text-primary">{{ number_format($promedio, 1) }}</div>
                                            <div class="score-stars text-warning fs-4 mb-2">
                                                @php
                                                    $fullStars = floor($promedio);
                                                    $hasHalf = ($promedio - $fullStars) >= 0.5;
                                                @endphp
                                                @for ($i = 1; $i <= 5; $i++)
                                                    @if ($i <= $fullStars)
                                                        <i class="bi bi-star-fill"></i>
                                                    @elseif ($i == $fullStars + 1 && $hasHalf)
                                                        <i class="bi bi-star-half"></i>
                                                    @else
                                                        <i class="bi bi-star"></i>
                                                    @endif
                                                @endfor
                                            </div>
                                            <div class="total-reviews text-muted">
                                                {{ $total_resenas }} {{ $total_resenas == 1 ? 'opinión' : 'opiniones de clientes' }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-7">
                                        <div class="rating-distribution p-2">
                                            @for ($stars = 5; $stars >= 1; $stars--)
                                                @php
                                                    $count = $distribucion[$stars];
                                                    $pct = $total_resenas > 0 ? ($count / $total_resenas) * 100 : 0;
                                                @endphp
                                                <div class="rating-row d-flex align-items-center mb-2">
                                                    <span class="stars-label me-2 small fw-bold" style="min-width: 30px;">{{ $stars }}★</span>
                                                    <div class="progress flex-grow-1" style="height: 10px; background-color: #e9ecef; border-radius: 5px;">
                                                        <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $pct }}%; border-radius: 5px;" 
                                                            aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                    <span class="count-label ms-2 small text-muted" style="min-width: 30px; text-align: right;">{{ $count }}</span>
                                                </div>
                                            @endfor
                                        </div>
                                    </div>
                                </div>

                                <div class="my-4">
                                    @if ($orden_entregada)
                                        <div class="write-review-cta p-4 bg-light rounded shadow-sm text-center">
                                            <h4 class="fw-bold mb-2">¿Compraste este producto?</h4>
                                            <p class="text-muted mb-3">Ayuda a otros compradores con tu opinión y sube fotos del producto recibido.</p>
                                            <button class="btn btn-primary px-4 fw-bold" type="button" data-bs-toggle="collapse" 
                                                data-bs-target="#collapseWriteReview" aria-expanded="false" aria-controls="collapseWriteReview">
                                                <i class="bi bi-pencil-square me-1"></i> Escribir Reseña
                                            </button>
                                        </div>

                                        <!-- Collapsible Write Review Form -->
                                        <div class="collapse mt-3" id="collapseWriteReview">
                                            <div class="card card-body shadow-sm border-0">
                                                <h5 class="fw-bold mb-3"><i class="bi bi-pencil-fill text-primary"></i> Nueva Valoración</h5>
                                                <form action="{{ route('web.reviews.store') }}" method="POST" enctype="multipart/form-data">
                                                    @csrf
                                                    <input type="hidden" name="producto_id" value="{{ $producto->id }}">
                                                    <input type="hidden" name="orden_id" value="{{ $orden_entregada->id }}">

                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold d-block text-secondary">Calificación (*)</label>
                                                        <div class="d-flex gap-2 fs-3 text-warning" id="star-selector">
                                                            @for ($i = 1; $i <= 5; $i++)
                                                                <i class="bi bi-star star-select-btn" data-value="{{ $i }}" style="cursor: pointer;"></i>
                                                            @endfor
                                                        </div>
                                                        <input type="hidden" name="estrellas" id="estrellas-input" value="" required>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="titulo" class="form-label fw-bold text-secondary">Título (Opcional)</label>
                                                        <input type="text" name="titulo" id="titulo" class="form-control" placeholder="Ej. Excelente monitor, muy rápido...">
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="comentario" class="form-label fw-bold text-secondary">Tu Comentario (*)</label>
                                                        <textarea name="comentario" id="comentario" rows="4" class="form-control" 
                                                            maxlength="{{ $ajuste_resenas->review_longitud_maxima ?? 1000 }}" 
                                                            placeholder="Escribe tu opinión y experiencia..." required></textarea>
                                                        <div class="form-text text-muted text-end">
                                                            Máx. {{ $ajuste_resenas->review_longitud_maxima ?? 1000 }} caracteres.
                                                        </div>
                                                    </div>

                                                    @if ($ajuste_resenas->review_permitir_imagenes)
                                                        <div class="mb-3">
                                                            <label for="imagenes" class="form-label fw-bold text-secondary">Fotos del Producto (Opcional)</label>
                                                            <input type="file" name="imagenes[]" id="imagenes" class="form-control" multiple accept="image/*">
                                                            <div class="form-text text-muted">
                                                                Puedes subir hasta {{ $ajuste_resenas->review_max_imagenes ?? 5 }} imágenes reales del producto.
                                                            </div>
                                                        </div>
                                                    @endif

                                                    <div class="d-flex justify-content-end gap-2">
                                                        <button type="button" class="btn btn-secondary" data-bs-toggle="collapse" data-bs-target="#collapseWriteReview">Cancelar</button>
                                                        <button type="submit" class="btn btn-primary fw-bold px-4">Publicar Reseña</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>

                                        <script>
                                            document.addEventListener('DOMContentLoaded', function() {
                                                const stars = document.querySelectorAll('.star-select-btn');
                                                const input = document.getElementById('estrellas-input');
                                                
                                                stars.forEach(star => {
                                                    star.addEventListener('click', function() {
                                                        const val = parseInt(this.getAttribute('data-value'));
                                                        input.value = val;
                                                        
                                                        stars.forEach(s => {
                                                            const sVal = parseInt(s.getAttribute('data-value'));
                                                            if (sVal <= val) {
                                                                s.classList.remove('bi-star');
                                                                s.classList.add('bi-star-fill');
                                                            } else {
                                                                s.classList.remove('bi-star-fill');
                                                                s.classList.add('bi-star');
                                                            }
                                                        });
                                                    });
                                                });
                                            });
                                        </script>
                                    @else
                                        <div class="write-review-cta p-3 bg-light rounded text-center">
                                            <span class="text-muted d-block small mb-1">
                                                <i class="bi bi-shield-lock-fill text-secondary"></i> Compra Verificada
                                            </span>
                                            <p class="small text-muted mb-0">Solo los clientes que han comprado y recibido este producto pueden escribir una reseña.</p>
                                        </div>
                                    @endif
                                </div>

                                <div class="customer-reviews-list mt-4">
                                    @forelse ($resenas_aprobadas as $r)
                                        <div class="review-card mb-4 p-3 border rounded shadow-sm bg-white">
                                            <div class="reviewer-profile d-flex align-items-center mb-3">
                                                <div class="avatar-circle bg-secondary text-white d-flex align-items-center justify-content-center rounded-circle me-3" 
                                                    style="width: 45px; height: 45px; font-weight: bold; font-size: 1.2rem;">
                                                    {{ strtoupper(substr($r->usuario->name, 0, 1)) }}
                                                </div>
                                                <div class="profile-details">
                                                    <div class="customer-name fw-bold">{{ $r->usuario->name }}</div>
                                                    <div class="review-meta d-flex align-items-center gap-2">
                                                        <div class="review-stars text-warning" style="font-size: 0.9rem;">
                                                            @for ($i = 1; $i <= 5; $i++)
                                                                @if ($i <= $r->estrellas)
                                                                    <i class="bi bi-star-fill"></i>
                                                                @else
                                                                    <i class="bi bi-star"></i>
                                                                @endif
                                                            @endfor
                                                        </div>
                                                        <span class="review-date text-muted small">{{ $r->created_at->format('d/m/Y') }}</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mb-2">
                                                <span class="badge text-success fw-bold p-2" style="font-size: 0.75rem; background-color: #e8f5e9; border: 1px solid #c8e6c9;">
                                                    <i class="bi bi-patch-check-fill me-1"></i> Compra verificada
                                                </span>
                                            </div>

                                            @if ($r->titulo)
                                                <h6 class="review-headline fw-bold text-dark mb-1">{{ $r->titulo }}</h6>
                                            @endif
                                            <div class="review-text text-secondary mb-3" style="font-size: 0.95rem; line-height: 1.5; white-space: pre-line;">
                                                {{ $r->comentario }}
                                            </div>

                                            @if ($r->imagenes->isNotEmpty())
                                                <div class="d-flex gap-2 mb-3 flex-wrap">
                                                    @foreach ($r->imagenes as $img)
                                                        <a href="{{ asset('storage/' . $img->imagen) }}" target="_blank">
                                                            <img src="{{ asset('storage/' . $img->imagen) }}" 
                                                                class="img-thumbnail rounded" style="width: 80px; height: 80px; object-fit: cover;">
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @endif

                                            @if ($r->respuesta_admin)
                                                <div class="bg-light p-3 rounded border-start border-primary border-3 mt-2 ms-4" style="font-size: 0.9rem;">
                                                    <div class="fw-semibold text-primary mb-1"><i class="bi bi-reply-fill me-1"></i> Respuesta del Administrador:</div>
                                                    <p class="mb-0 text-muted">{{ $r->respuesta_admin }}</p>
                                                </div>
                                            @endif
                                        </div>
                                    @empty
                                        <div class="text-center py-4 text-muted">
                                            <i class="bi bi-chat-left-dots fs-1 d-block mb-2"></i>
                                            Aún no hay opiniones de este producto. ¡Sé el primero en calificarlo!
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        </div>
    </section><!-- /Product Details Section -->

    <!-- Product Recommendations Sections -->
    <style>
        .swiper-container-rec {
            width: 100%;
            padding: 15px 5px;
        }
        .swiper-button-next-rec, .swiper-button-prev-rec {
            color: #0d6efd;
            background: rgba(255,255,255,0.9);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            border: 1px solid #e2e8f0;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .swiper-button-next-rec:hover, .swiper-button-prev-rec:hover {
            background: #0d6efd;
            color: #fff;
            box-shadow: 0 6px 15px rgba(13, 110, 253, 0.3);
        }
        .swiper-button-next-rec::after, .swiper-button-prev-rec::after {
            font-family: 'bootstrap-icons';
            font-size: 1.1rem;
            font-weight: bold;
        }
        .swiper-button-next-rec::after {
            content: "\F285"; /* bi-chevron-right */
        }
        .swiper-button-prev-rec::after {
            content: "\F281"; /* bi-chevron-left */
        }
        .swiper-button-next-rec {
            right: -10px;
        }
        .swiper-button-prev-rec {
            left: -10px;
        }
        .swiper-button-disabled {
            opacity: 0 !important;
            pointer-events: none;
        }
        @media (max-width: 768px) {
            .swiper-button-next-rec, .swiper-button-prev-rec {
                display: none !important;
            }
        }
    </style>

    <div class="recommendations-area py-5 bg-light">
        <div class="container">
            
            {{-- 1. Productos Relacionados --}}
            @if(isset($productos_relacionados) && $productos_relacionados->isNotEmpty())
                <div class="recommendation-section mb-5" data-aos="fade-up">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="fw-bold text-dark mb-0" style="font-family: 'Poppins', sans-serif;">Productos relacionados</h3>
                    </div>
                    <div class="position-relative px-md-4">
                        <div class="swiper swiper-relacionados">
                            <div class="swiper-wrapper">
                                @foreach($productos_relacionados as $prod_rec)
                                    <div class="swiper-slide h-auto p-2">
                                        @include('partials.producto_card', ['producto' => $prod_rec])
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="swiper-button-next-rec swiper-button-next-rel"></div>
                        <div class="swiper-button-prev-rec swiper-button-prev-rel"></div>
                    </div>
                </div>
            @endif

            {{-- 2. Los Clientes También Compraron --}}
            @if(isset($tambien_compraron) && $tambien_compraron->isNotEmpty())
                <div class="recommendation-section mb-5" data-aos="fade-up">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="fw-bold text-dark mb-0" style="font-family: 'Poppins', sans-serif;">Los clientes también compraron</h3>
                    </div>
                    <div class="position-relative px-md-4">
                        <div class="swiper swiper-tambien">
                            <div class="swiper-wrapper">
                                @foreach($tambien_compraron as $prod_rec)
                                    <div class="swiper-slide h-auto p-2">
                                        @include('partials.producto_card', ['producto' => $prod_rec])
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="swiper-button-next-rec swiper-button-next-tambien"></div>
                        <div class="swiper-button-prev-rec swiper-button-prev-tambien"></div>
                    </div>
                </div>
            @endif

            {{-- 3. Productos Mejor Valorados --}}
            @if(isset($mejor_valorados) && $mejor_valorados->isNotEmpty())
                <div class="recommendation-section mb-5" data-aos="fade-up">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="fw-bold text-dark mb-0" style="font-family: 'Poppins', sans-serif;">Productos mejor valorados</h3>
                    </div>
                    <div class="position-relative px-md-4">
                        <div class="swiper swiper-valorados">
                            <div class="swiper-wrapper">
                                @foreach($mejor_valorados as $prod_rec)
                                    <div class="swiper-slide h-auto p-2">
                                        @include('partials.producto_card', ['producto' => $prod_rec])
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="swiper-button-next-rec swiper-button-next-valorados"></div>
                        <div class="swiper-button-prev-rec swiper-button-prev-valorados"></div>
                    </div>
                </div>
            @endif

            {{-- 4. Productos en Oferta --}}
            @if(isset($productos_oferta) && $productos_oferta->isNotEmpty())
                <div class="recommendation-section mb-5" data-aos="fade-up">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="fw-bold text-dark mb-0" style="font-family: 'Poppins', sans-serif;">Aprovecha estas ofertas</h3>
                    </div>
                    <div class="position-relative px-md-4">
                        <div class="swiper swiper-ofertas">
                            <div class="swiper-wrapper">
                                @foreach($productos_oferta as $prod_rec)
                                    <div class="swiper-slide h-auto p-2">
                                        @include('partials.producto_card', ['producto' => $prod_rec])
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="swiper-button-next-rec swiper-button-next-ofertas"></div>
                        <div class="swiper-button-prev-rec swiper-button-prev-ofertas"></div>
                    </div>
                </div>
            @endif

            {{-- 5. Más Productos de la Marca --}}
            @if(isset($mas_de_marca) && $mas_de_marca->isNotEmpty())
                <div class="recommendation-section mb-5" data-aos="fade-up">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="fw-bold text-dark mb-0" style="font-family: 'Poppins', sans-serif;">Más productos de {{ $producto->marca_nombre ?? '' }}</h3>
                    </div>
                    <div class="position-relative px-md-4">
                        <div class="swiper swiper-marca">
                            <div class="swiper-wrapper">
                                @foreach($mas_de_marca as $prod_rec)
                                    <div class="swiper-slide h-auto p-2">
                                        @include('partials.producto_card', ['producto' => $prod_rec])
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="swiper-button-next-rec swiper-button-next-marca"></div>
                        <div class="swiper-button-prev-rec swiper-button-prev-marca"></div>
                    </div>
                </div>
            @endif

            {{-- 6. Productos Vistos Recientemente --}}
            @if(isset($vistos_recientemente) && $vistos_recientemente->isNotEmpty())
                <div class="recommendation-section mb-5" data-aos="fade-up">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="fw-bold text-dark mb-0" style="font-family: 'Poppins', sans-serif;">Vistos recientemente</h3>
                    </div>
                    <div class="position-relative px-md-4">
                        <div class="swiper swiper-vistos">
                            <div class="swiper-wrapper">
                                @foreach($vistos_recientemente as $prod_rec)
                                    <div class="swiper-slide h-auto p-2">
                                        @include('partials.producto_card', ['producto' => $prod_rec])
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="swiper-button-next-rec swiper-button-next-vistos"></div>
                        <div class="swiper-button-prev-rec swiper-button-prev-vistos"></div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const swiperOptions = {
                slidesPerView: 1,
                spaceBetween: 20,
                breakpoints: {
                    576: { slidesPerView: 2 },
                    768: { slidesPerView: 3 },
                    992: { slidesPerView: 4 }
                }
            };

            @if(isset($productos_relacionados) && $productos_relacionados->isNotEmpty())
                new Swiper('.swiper-relacionados', {
                    ...swiperOptions,
                    navigation: {
                        nextEl: '.swiper-button-next-rel',
                        prevEl: '.swiper-button-prev-rel',
                    }
                });
            @endif

            @if(isset($tambien_compraron) && $tambien_compraron->isNotEmpty())
                new Swiper('.swiper-tambien', {
                    ...swiperOptions,
                    navigation: {
                        nextEl: '.swiper-button-next-tambien',
                        prevEl: '.swiper-button-prev-tambien',
                    }
                });
            @endif

            @if(isset($mejor_valorados) && $mejor_valorados->isNotEmpty())
                new Swiper('.swiper-valorados', {
                    ...swiperOptions,
                    navigation: {
                        nextEl: '.swiper-button-next-valorados',
                        prevEl: '.swiper-button-prev-valorados',
                    }
                });
            @endif

            @if(isset($productos_oferta) && $productos_oferta->isNotEmpty())
                new Swiper('.swiper-ofertas', {
                    ...swiperOptions,
                    navigation: {
                        nextEl: '.swiper-button-next-ofertas',
                        prevEl: '.swiper-button-prev-ofertas',
                    }
                });
            @endif

            @if(isset($mas_de_marca) && $mas_de_marca->isNotEmpty())
                new Swiper('.swiper-marca', {
                    ...swiperOptions,
                    navigation: {
                        nextEl: '.swiper-button-next-marca',
                        prevEl: '.swiper-button-prev-marca',
                    }
                });
            @endif

            @if(isset($vistos_recientemente) && $vistos_recientemente->isNotEmpty())
                new Swiper('.swiper-vistos', {
                    ...swiperOptions,
                    navigation: {
                        nextEl: '.swiper-button-next-vistos',
                        prevEl: '.swiper-button-prev-vistos',
                    }
                });
            @endif
        });
    </script>
@endsection
