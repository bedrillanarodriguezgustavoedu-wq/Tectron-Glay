@extends('layouts.web')

@section('content')
    

    <style>
        .hover-zoom img {
            transition: transform .4s ease;
        }
        .hover-zoom:hover img {
            transform: scale(1.03);
        }
        .text-shadow-sm {
            text-shadow: 0 2px 4px rgba(0,0,0,0.6);
        }
        .shared-banner-link {
            display: block;
            height: min(var(--home-slider-height, 520px), 340px);
            position: relative;
            overflow: hidden;
            color: #fff;
            background: linear-gradient(90deg, #05070c 0%, #111827 40%, #111827 100%);
        }
        .shared-banner-image {
            display: block;
            position: absolute;
            right: 0;
            top: 0;
            width: 60%;
            height: 100%;
            object-fit: contain;
        }
        .shared-banner-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            padding: 0 clamp(1.25rem, 4.5vw, 5.5rem);
            background: none;
        }
        .shared-banner-copy {
            width: 40%;
            max-width: 650px;
            color: #fff !important;
            text-shadow: 0 2px 12px rgba(0,0,0,.7);
        }
        .shared-banner-copy h1,
        .shared-banner-copy p {
            color: #fff !important;
        }
        .shared-banner-copy h1 {
            font-size: clamp(1.8rem, 4vw, 3.5rem);
            font-weight: 800;
            line-height: 1.08;
            margin-bottom: .4rem;
        }
        .shared-banner-copy p {
            font-size: clamp(.9rem, 1.4vw, 1.1rem);
            display: table;
            padding: .45rem .8rem;
            border-radius: 999px;
            background: #ff0050;
            margin-bottom: 0;
        }
        @media (max-width: 768px) {
            .shared-banner-link {
                height: min(var(--home-slider-height, 520px), 220px);
            }
            .shared-banner-image {
                width: 50%;
            }
            .shared-banner-overlay {
                align-items: center;
                padding: 0 .75rem;
            }
            .shared-banner-copy {
                width: 43%;
            }
            .shared-banner-copy h1 {
                font-size: clamp(1rem, 4.2vw, 1.5rem);
            }
            .shared-banner-copy p {
                font-size: clamp(.7rem, 2.8vw, .9rem);
                padding: .35rem .6rem;
            }
            .shared-banner-copy .btn {
                margin-top: .5rem !important;
                padding: .4rem .75rem !important;
                font-size: .8rem;
            }
        }
        .legacy-home-carousel-item {
            height: var(--home-slider-height, 520px) !important;
        }
        @media (max-width: 576px) {
            .legacy-home-carousel-item {
                height: min(var(--home-slider-height, 520px), 360px) !important;
            }
        }
        .brand-card {
            transition: all 0.3s ease;
        }
        .brand-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important;
        }
        .category-card {
            transition: all 0.3s ease;
        }
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important;
        }
        /* Product Carousel Styles */
        .swiper-container {
            width: 100%;
            padding: 15px 5px;
        }
        .swiper-button-next, .swiper-button-prev {
            color: #0d6efd;
            background: rgba(255,255,255,0.9);
            width: 44px;
            height: 44px;
            border-radius: 50%;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            border: 1px solid #e2e8f0;
        }
        .swiper-button-next::after, .swiper-button-prev::after {
            font-size: 1.2rem;
            font-weight: bold;
        }
        .swiper-button-disabled {
            opacity: 0 !important;
        }
        .product-card {
            transition: all 0.3s ease;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.08) !important;
            border-color: #0d6efd !important;
        }
    </style>

    {{-- La franja de confianza se muestra una sola vez, debajo del carrusel --}}

    @php
        // La portada de bienvenida (Admin → Banners) abre el carrusel y el resto
        // de banners sigue en su orden. Un solo bloque de banner en la home.
        $bannersCarrusel = $banners->sortByDesc('portada')->values();
    @endphp

    <div class="g-home-hero">
    @if ($bannersCarrusel->isNotEmpty())
        <div id="heroCarousel" class="carousel slide mb-4" style="--home-slider-height: {{ max(300, min(900, (int) ($ajuste->home_slider_altura ?? 520))) }}px;" data-bs-ride="carousel" data-bs-interval="{{ $ajuste->home_slider_velocidad ?? 5000 }}">
            @if ($bannersCarrusel->count() > 1)
                <div class="carousel-indicators">
                    @foreach ($bannersCarrusel as $index => $banner)
                        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="{{ $index }}"
                            class="{{ $index === 0 ? 'active' : '' }}"
                            aria-label="Banner {{ $index + 1 }}"></button>
                    @endforeach
                </div>
            @endif
            <div class="carousel-inner">
                @foreach ($bannersCarrusel as $index => $banner)
                    @php
                        $bannerLink = $banner->enlace;
                        if (!$bannerLink || (!filter_var($bannerLink, FILTER_VALIDATE_URL) && !\Illuminate\Support\Str::startsWith($bannerLink, '/'))) {
                            $bannerLink = route('web.productos');
                        }
                    @endphp
                    <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                        <div class="shared-banner-link text-decoration-none {{ $banner->portada ? 'is-portada' : '' }}">
                            <img src="{{ $banner->image_url }}" class="shared-banner-image" alt="{{ $banner->titulo }}">
                            <a href="{{ $bannerLink }}" class="shared-banner-hit" aria-label="{{ $banner->titulo ?: 'Ver ofertas' }}"></a>
                            <div class="shared-banner-overlay">
                                <div class="shared-banner-copy">
                                    <span class="g-hero-eyebrow"><i class="bi bi-lightning-charge-fill"></i> {{ $banner->portada ? 'Bienvenido' : 'Oferta de la semana' }}</span>
                                    @if ($banner->titulo)
                                        <h1>{{ $banner->titulo }}</h1>
                                    @endif
                                    @if ($banner->subtitulo)
                                        <p>{{ $banner->subtitulo }}</p>
                                    @endif
                                    <div class="g-hero-cta">
                                        <a href="{{ $bannerLink }}" class="btn btn-light fw-bold">{{ $banner->boton_texto ?: 'Comprar ahora' }} <i class="bi bi-arrow-right ms-1"></i></a>
                                        <a href="{{ route('web.ofertas') }}" class="btn btn-outline-light">Ver ofertas</a>
                                    </div>
                                    @if ($banner->portada)
                                        <div class="g-hero-trust">
                                            <span><i class="bi bi-truck"></i> Envío a todo el Perú</span>
                                            <span><i class="bi bi-shield-check"></i> Pago seguro</span>
                                            <span><i class="bi bi-credit-card"></i> Yape, Plin y tarjetas</span>
                                        </div>
                                        <div class="g-hero-float-card" aria-hidden="true">
                                            <span class="g-hero-float-icon"><i class="bi bi-shield-check"></i></span>
                                            <span>
                                                <strong>Compra protegida</strong>
                                                <small>Mercado Pago · Yape · Plin</small>
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            @if ($bannersCarrusel->count() > 1)
                <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev" aria-label="Banner anterior">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next" aria-label="Banner siguiente">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                </button>
            @endif
        </div>
    @elseif ($carruseles->isNotEmpty())
        <!-- Hero Carousel Section -->
        <div id="heroCarousel" class="carousel slide carousel-fade mb-4" style="--home-slider-height: {{ max(300, min(900, (int) ($ajuste->home_slider_altura ?? 520))) }}px;" data-bs-ride="carousel" data-bs-interval="{{ $ajuste->home_slider_velocidad ?? 5000 }}">
            <div class="carousel-indicators">
                @foreach ($carruseles as $index => $carrusel)
                    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="{{ $index }}"
                        class="{{ $index === 0 ? 'active' : '' }}" aria-current="{{ $index === 0 ? 'true' : 'false' }}"
                        aria-label="Slide {{ $index + 1 }}"></button>
                @endforeach
            </div>
            <div class="carousel-inner">
                @foreach ($carruseles as $index => $carrusel)
                    <div class="carousel-item legacy-home-carousel-item {{ $index === 0 ? 'active' : '' }}">
                        <img src="{{ asset('storage/' . $carrusel->imagen) }}" class="d-block w-100 h-100" style="object-fit: cover; filter: brightness(0.65);" alt="{{ $carrusel->titulo }}">
                        <div class="carousel-caption d-flex flex-column justify-content-center align-items-center h-100" style="bottom: 0;">
                            <div class="container text-center text-white">
                                @if ($carrusel->titulo)
                                    <h1 class="display-3 fw-bold mb-3 animate__animated animate__fadeInDown" style="text-shadow: 0 4px 15px rgba(0,0,0,0.6); font-family: 'Poppins', sans-serif;">{{ $carrusel->titulo }}</h1>
                                @endif
                                @if ($carrusel->descripcion)
                                    <p class="lead fs-4 mb-4 animate__animated animate__fadeInUp animate__delay-1s" style="text-shadow: 0 2px 10px rgba(0,0,0,0.6); max-width: 800px; margin: 0 auto;">{{ $carrusel->descripcion }}</p>
                                @endif
                                @if ($carrusel->enlace)
                                    <a href="{{ $carrusel->enlace }}" class="btn btn-primary btn-lg px-5 py-3 rounded-pill fw-bold text-uppercase animate__animated animate__zoomIn animate__delay-2s" style="box-shadow: 0 4px 15px rgba(13, 110, 253, 0.4);">
                                        Comprar Ahora <i class="bi bi-arrow-right ms-2"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Anterior</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Siguiente</span>
            </button>
        </div>
    @else
        <!-- Hero Section Fallback -->
        <section id="hero" class="hero section">
            <div class="hero-container">
                <div class="hero-content">
                    <div class="content-wrapper" data-aos="fade-up" data-aos-delay="100">
                        <h1 class="hero-title">Descubra productos increíbles</h1>
                        <p class="hero-description">Explore nuestra colección seleccionada de artículos premium diseñados para mejorar su estilo de vida.</p>
                        <div class="hero-actions">
                            <a href="#categories" class="btn-secondary">Explorar Categorías</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif
    </div>

    <!-- Franja de confianza -->
    <div class="container">
        <div class="g-trust">
            <div class="g-trust-item">
                <div class="g-trust-icon"><i class="bi bi-truck" aria-hidden="true"></i></div>
                <div>
                    <strong>Envío a todo el Perú</strong>
                    <span>Despacho rápido y seguimiento</span>
                </div>
            </div>
            <div class="g-trust-item">
                <div class="g-trust-icon"><i class="bi bi-credit-card-2-front" aria-hidden="true"></i></div>
                <div>
                    <strong>Pago 100% seguro</strong>
                    <span>Yape, Plin, tarjetas y transferencia</span>
                </div>
            </div>
            <div class="g-trust-item">
                <div class="g-trust-icon"><i class="bi bi-shield-check" aria-hidden="true"></i></div>
                <div>
                    <strong>Garantía oficial</strong>
                    <span>Productos originales con garantía</span>
                </div>
            </div>
            <div class="g-trust-item">
                <div class="g-trust-icon"><i class="bi bi-headset" aria-hidden="true"></i></div>
                <div>
                    <strong>Asesoría personalizada</strong>
                    <span>Te ayudamos a elegir</span>
                </div>
            </div>
        </div>
    </div>

    @if ($oferta_activa || $oferta_programada)
        @php
            $oferta_promo = $oferta_activa ?? $oferta_programada;
            $es_activa = (bool)$oferta_activa;
            $target_date = $es_activa ? $oferta_promo->fecha_fin : $oferta_promo->fecha_inicio;
            $valor_descuento = $oferta_promo->valor_descuento;
            $tipo_descuento = $oferta_promo->tipo_descuento;
        @endphp
        <!-- Countdown Promotion Section -->
        <section id="countdown-promotion" class="countdown-promotion py-4 bg-light">
            <style>
                .countdown-card {
                    min-width: 95px;
                    transition: all 0.3s ease;
                    box-shadow: 0 10px 25px rgba(0,0,0,0.08) !important;
                }
                .countdown-card:hover {
                    transform: translateY(-4px);
                    box-shadow: 0 15px 30px rgba(0,0,0,0.15) !important;
                }
                @media (max-width: 576px) {
                    .countdown-card {
                        min-width: 72px !important;
                        padding: 0.5rem 0.25rem !important;
                    }
                    .countdown-card div.fs-1 {
                        font-size: 1.6rem !important;
                    }
                    .countdown-card span {
                        font-size: 9px !important;
                        letter-spacing: 0.5px !important;
                    }
                }
            </style>
            <div class="container">
                <div class="card border-0 shadow-lg p-4 text-center position-relative overflow-hidden" 
                    style="border-radius: 20px; background: linear-gradient(135deg, #1e3a8a, #3b82f6);">
                    
                    <div class="position-absolute top-0 start-0 w-100 h-100 opacity-10" style="background-image: radial-gradient(circle, #fff 10%, transparent 11%); background-size: 20px 20px;"></div>
                    
                    <div class="row align-items-center position-relative z-1 py-2">
                        <div class="col-md-5 text-md-start text-center text-white mb-3 mb-md-0">
                            @if ($es_activa)
                                <span class="badge bg-danger text-uppercase px-3 py-2 rounded-pill fw-bold mb-2">¡OFERTA DESTACADA ACTIVA!</span>
                            @else
                                <span class="badge bg-warning text-dark text-uppercase px-3 py-2 rounded-pill fw-bold mb-2">¡PRÓXIMA OFERTA PROGRAMADA!</span>
                            @endif
                            <h2 class="fw-bold mb-1" style="font-family: 'Poppins', sans-serif; text-shadow: 0 2px 4px rgba(0,0,0,0.15);">{{ $oferta_promo->nombre }}</h2>
                            @if ($tipo_descuento === 'porcentaje')
                                <p class="mb-0 text-white fs-5" style="opacity: 0.95;">{{ $es_activa ? 'Termina en:' : 'Comienza en:' }} <strong class="text-warning fs-3" style="text-shadow: 0 1px 2px rgba(0,0,0,0.2);">-{{ $valor_descuento }}%</strong> en productos seleccionados.</p>
                            @else
                                <p class="mb-0 text-white fs-5" style="opacity: 0.95;">{{ $es_activa ? 'Termina en:' : 'Comienza en:' }} <strong class="text-warning fs-3" style="text-shadow: 0 1px 2px rgba(0,0,0,0.2);">-{{ \App\Models\Ajuste::formatear_precio($valor_descuento) }}</strong> de descuento.</p>
                            @endif
                        </div>
                        
                        <div class="col-md-7">
                            <div class="d-flex justify-content-center gap-3">
                                <div class="countdown-card bg-white rounded-4 p-3 text-center">
                                    <div id="days" class="fs-1 fw-extrabold mb-0" style="font-family: 'Poppins', sans-serif; font-weight: 800; color: #1e3a8a; line-height: 1.1;">00</div>
                                    <span class="text-uppercase fw-bold text-secondary" style="font-size: 11px; letter-spacing: 1px; display: block; margin-top: 2px;">Días</span>
                                </div>
                                <div class="countdown-card bg-white rounded-4 p-3 text-center">
                                    <div id="hours" class="fs-1 fw-extrabold mb-0" style="font-family: 'Poppins', sans-serif; font-weight: 800; color: #1e3a8a; line-height: 1.1;">00</div>
                                    <span class="text-uppercase fw-bold text-secondary" style="font-size: 11px; letter-spacing: 1px; display: block; margin-top: 2px;">Horas</span>
                                </div>
                                <div class="countdown-card bg-white rounded-4 p-3 text-center">
                                    <div id="minutes" class="fs-1 fw-extrabold mb-0" style="font-family: 'Poppins', sans-serif; font-weight: 800; color: #1e3a8a; line-height: 1.1;">00</div>
                                    <span class="text-uppercase fw-bold text-secondary" style="font-size: 11px; letter-spacing: 1px; display: block; margin-top: 2px;">Minutos</span>
                                </div>
                                <div class="countdown-card bg-white rounded-4 p-3 text-center">
                                    <div id="seconds" class="fs-1 fw-extrabold mb-0" style="font-family: 'Poppins', sans-serif; font-weight: 800; color: #1e3a8a; line-height: 1.1;">00</div>
                                    <span class="text-uppercase fw-bold text-secondary" style="font-size: 11px; letter-spacing: 1px; display: block; margin-top: 2px;">Segundos</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const targetDate = new Date("{{ \Carbon\Carbon::parse($target_date)->toIso8601String() }}").getTime();
                
                function updateCountdown() {
                    const now = new Date().getTime();
                    const distance = targetDate - now;
                    
                    if (distance < 0) {
                        window.location.reload();
                        return;
                    }
                    
                    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                    
                    document.getElementById('days').innerText = String(days).padStart(2, '0');
                    document.getElementById('hours').innerText = String(hours).padStart(2, '0');
                    document.getElementById('minutes').innerText = String(minutes).padStart(2, '0');
                    document.getElementById('seconds').innerText = String(seconds).padStart(2, '0');
                }
                
                updateCountdown();
                const intervalId = setInterval(updateCountdown, 1000);
            });
        </script>
    @endif

    <!-- Carousel of Products by Sales-Prioritized Categories -->
    @if ($categorias_home->isNotEmpty())
        @foreach ($categorias_home as $categoria)
            @if ($categoria->productos_carrusel->isNotEmpty())
                <section class="section g-section py-5">
                    <div class="container">
                        <div class="g-section-head" data-aos="fade-up">
                            <div>
                                <span class="g-eyebrow">Lo más vendido</span>
                                <h2>{{ $categoria->nombre }}</h2>
                                <p>Encuentra los mejores productos seleccionados</p>
                            </div>
                            <a href="{{ route('web.productos', ['categoria' => $categoria->slug]) }}" class="g-link-all">
                                Ver todo <i class="bi bi-arrow-right" aria-hidden="true"></i>
                            </a>
                        </div>

                        <!-- Swiper Carousel -->
                        <div class="position-relative px-md-5">
                            <div class="swiper mySwiper-{{ $categoria->id }}">
                                <div class="swiper-wrapper">
                                    @foreach ($categoria->productos_carrusel as $producto)
                                        <div class="swiper-slide h-auto p-2">
                                            @include('partials.producto_card', ['producto' => $producto])
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            
                            <!-- Navigation controls outside swiper wrapper for premium display -->
                            <div class="swiper-button-next swiper-button-next-{{ $categoria->id }} d-none d-md-flex"></div>
                            <div class="swiper-button-prev swiper-button-prev-{{ $categoria->id }} d-none d-md-flex"></div>
                        </div>
                    </div>
                </section>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        new Swiper('.mySwiper-{{ $categoria->id }}', {
                            slidesPerView: 1,
                            spaceBetween: 15,
                            autoplay: {
                                delay: {{ $ajuste->home_carrusel_velocidad ?? 3000 }},
                                disableOnInteraction: false,
                            },
                            navigation: {
                                nextEl: '.swiper-button-next-{{ $categoria->id }}',
                                prevEl: '.swiper-button-prev-{{ $categoria->id }}',
                            },
                            breakpoints: {
                                576: { slidesPerView: 2 },
                                768: { slidesPerView: 3 },
                                992: { slidesPerView: 4 }
                            }
                        });
                    });
                </script>
            @endif
        @endforeach
    @endif

    <!-- Dynamic Storefront Sections -->
    @foreach ($secciones as $seccion)
        @php
            $datos = $datos_secciones[$seccion->clave] ?? collect();
        @endphp

        @if ($datos->isNotEmpty())
            @if ($seccion->clave === 'marcas')
                <!-- Sección de Marcas -->
                <section class="section g-section g-section--muted py-5">
                    <div class="container">
                        <div class="g-section-head" data-aos="fade-up">
                            <div>
                                <span class="g-eyebrow">Marcas aliadas</span>
                                <h2>{{ $seccion->titulo }}</h2>
                                <p>Nuestras marcas oficiales aliadas</p>
                            </div>
                            <a href="{{ route('web.marcas') }}" class="g-link-all">
                                Ver marcas <i class="bi bi-arrow-right" aria-hidden="true"></i>
                            </a>
                        </div>

                        <div class="row g-4 justify-content-center">
                            @foreach ($datos as $marca)
                                <div class="col-6 col-md-3 col-lg-2">
                                    <div class="card h-100 border rounded p-3 text-center brand-card shadow-sm">
                                        <x-marca-logo :marca="$marca" width="100%" height="60px" class="mb-2" />
                                        <div class="fw-bold text-dark">{{ $marca->nombre }}</div>
                                        <small class="text-muted">{{ $marca->productos_count }} productos</small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            @elseif ($seccion->clave === 'categorias_populares')
                <!-- Sección de Categorías -->
                <section class="section g-section py-5">
                    <div class="container">
                        <div class="g-section-head" data-aos="fade-up">
                            <div>
                                <span class="g-eyebrow">Explora</span>
                                <h2>{{ $seccion->titulo }}</h2>
                                <p>Explora por tus categorías favoritas</p>
                            </div>
                            <a href="{{ route('web.productos') }}" class="g-link-all">
                                Ver catálogo <i class="bi bi-arrow-right" aria-hidden="true"></i>
                            </a>
                        </div>

                        <div class="g-cat-grid">
                            @foreach ($datos->take(12) as $categoria)
                                <a href="{{ route('web.categoria', $categoria->slug) }}" class="g-cat-card">
                                    <span class="g-cat-icon"><i class="{{ $categoria->icono ?? 'bi-tag' }}" aria-hidden="true"></i></span>
                                    <strong>{{ $categoria->nombre }}</strong>
                                    <span>{{ $categoria->productos_count }} productos</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </section>
            @else
                <!-- Sección de Productos (destacados, ofertas, nuevos, recomendados) -->
                <section class="section g-section py-5 {{ $loop->even ? 'g-section--muted' : '' }}">
                    <div class="container">
                        <div class="g-section-head" data-aos="fade-up">
                            <div>
                                <span class="g-eyebrow">
                                    @if ($seccion->clave === 'ofertas')
                                        Ofertas
                                    @elseif ($seccion->clave === 'nuevos_productos')
                                        Recién llegados
                                    @elseif ($seccion->clave === 'productos_recomendados')
                                        Para ti
                                    @else
                                        Selección
                                    @endif
                                </span>
                                <h2>{{ $seccion->titulo }}</h2>
                                @if ($seccion->clave === 'productos_destacados')
                                    <p>Nuestra selección de productos destacados</p>
                                @elseif ($seccion->clave === 'ofertas')
                                    <p>Descuentos especiales por tiempo limitado</p>
                                @elseif ($seccion->clave === 'nuevos_productos')
                                    <p>Explora nuestras últimas novedades</p>
                                @elseif ($seccion->clave === 'productos_recomendados')
                                    <p>Nuestras recomendaciones exclusivas</p>
                                @else
                                    <p>Encuentra lo que buscas</p>
                                @endif
                            </div>
                            <a href="{{ $seccion->clave === 'ofertas' ? route('web.ofertas') : route('web.productos') }}" class="g-link-all">
                                Ver todo <i class="bi bi-arrow-right" aria-hidden="true"></i>
                            </a>
                        </div>

                        <div class="row g-3 g-md-4">
                            @foreach ($datos as $producto)
                                <div class="col-lg-3 col-md-6 col-6">
                                    @include('partials.producto_card', ['producto' => $producto])
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif
        @endif
    @endforeach

    <!-- Banda de asesoría -->
    <section class="g-cta-band">
        <div class="container">
            <div class="g-cta-inner">
                <div>
                    <span class="g-eyebrow" style="color:#93c5fd">Asesoría gratuita</span>
                    <h2>¿No sabes qué equipo elegir?</h2>
                    <p>Cuéntanos qué necesitas y un asesor te recomienda la mejor opción según tu presupuesto.</p>
                </div>
                <div class="g-cta-actions">
                    <a href="{{ route('web.asesores') }}" class="btn btn-light fw-bold">
                        <i class="bi bi-whatsapp me-1" aria-hidden="true"></i> Hablar con un asesor
                    </a>
                    <a href="{{ route('web.productos') }}" class="btn btn-outline-light">Ver catálogo</a>
                </div>
            </div>
        </div>
    </section>

@endsection
