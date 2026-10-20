<?php $__env->startSection('content'); ?>
    
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

    <?php if($banners->isNotEmpty()): ?>
        <div id="heroCarousel" class="carousel slide mb-4" style="--home-slider-height: <?php echo e(max(300, min(900, (int) ($ajuste->home_slider_altura ?? 520)))); ?>px;" data-bs-ride="carousel" data-bs-interval="<?php echo e($ajuste->home_slider_velocidad ?? 5000); ?>">
            <?php if($banners->count() > 1): ?>
                <div class="carousel-indicators">
                    <?php $__currentLoopData = $banners; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $banner): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?php echo e($index); ?>"
                            class="<?php echo e($index === 0 ? 'active' : ''); ?>"
                            aria-label="Banner <?php echo e($index + 1); ?>"></button>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php endif; ?>
            <div class="carousel-inner">
                <?php $__currentLoopData = $banners; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $banner): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $bannerLink = $banner->enlace;
                        if (!$bannerLink || (!filter_var($bannerLink, FILTER_VALIDATE_URL) && !\Illuminate\Support\Str::startsWith($bannerLink, '/'))) {
                            $bannerLink = route('web.productos');
                        }
                    ?>
                    <div class="carousel-item <?php echo e($index === 0 ? 'active' : ''); ?>">
                        <a href="<?php echo e($bannerLink); ?>" class="shared-banner-link text-decoration-none">
                            <img src="<?php echo e($banner->image_url); ?>" class="shared-banner-image" alt="<?php echo e($banner->titulo); ?>">
                            <div class="shared-banner-overlay">
                                <div class="shared-banner-copy">
                                    <?php if($banner->titulo): ?>
                                        <h1><?php echo e($banner->titulo); ?></h1>
                                    <?php endif; ?>
                                    <?php if($banner->subtitulo): ?>
                                        <p><?php echo e($banner->subtitulo); ?></p>
                                    <?php endif; ?>
                                    <span class="btn btn-light rounded-pill fw-bold mt-3 py-2">Ver catálogo <i class="bi bi-arrow-right ms-1"></i></span>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            <?php if($banners->count() > 1): ?>
                <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev" aria-label="Banner anterior">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next" aria-label="Banner siguiente">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                </button>
            <?php endif; ?>
        </div>
    <?php elseif($carruseles->isNotEmpty()): ?>
        <!-- Hero Carousel Section -->
        <div id="heroCarousel" class="carousel slide carousel-fade mb-4" style="--home-slider-height: <?php echo e(max(300, min(900, (int) ($ajuste->home_slider_altura ?? 520)))); ?>px;" data-bs-ride="carousel" data-bs-interval="<?php echo e($ajuste->home_slider_velocidad ?? 5000); ?>">
            <div class="carousel-indicators">
                <?php $__currentLoopData = $carruseles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $carrusel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?php echo e($index); ?>"
                        class="<?php echo e($index === 0 ? 'active' : ''); ?>" aria-current="<?php echo e($index === 0 ? 'true' : 'false'); ?>"
                        aria-label="Slide <?php echo e($index + 1); ?>"></button>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            <div class="carousel-inner">
                <?php $__currentLoopData = $carruseles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $carrusel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="carousel-item legacy-home-carousel-item <?php echo e($index === 0 ? 'active' : ''); ?>">
                        <img src="<?php echo e(asset('storage/' . $carrusel->imagen)); ?>" class="d-block w-100 h-100" style="object-fit: cover; filter: brightness(0.65);" alt="<?php echo e($carrusel->titulo); ?>">
                        <div class="carousel-caption d-flex flex-column justify-content-center align-items-center h-100" style="bottom: 0;">
                            <div class="container text-center text-white">
                                <?php if($carrusel->titulo): ?>
                                    <h1 class="display-3 fw-bold mb-3 animate__animated animate__fadeInDown" style="text-shadow: 0 4px 15px rgba(0,0,0,0.6); font-family: 'Poppins', sans-serif;"><?php echo e($carrusel->titulo); ?></h1>
                                <?php endif; ?>
                                <?php if($carrusel->descripcion): ?>
                                    <p class="lead fs-4 mb-4 animate__animated animate__fadeInUp animate__delay-1s" style="text-shadow: 0 2px 10px rgba(0,0,0,0.6); max-width: 800px; margin: 0 auto;"><?php echo e($carrusel->descripcion); ?></p>
                                <?php endif; ?>
                                <?php if($carrusel->enlace): ?>
                                    <a href="<?php echo e($carrusel->enlace); ?>" class="btn btn-primary btn-lg px-5 py-3 rounded-pill fw-bold text-uppercase animate__animated animate__zoomIn animate__delay-2s" style="box-shadow: 0 4px 15px rgba(13, 110, 253, 0.4);">
                                        Comprar Ahora <i class="bi bi-arrow-right ms-2"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
    <?php else: ?>
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
    <?php endif; ?>

    <?php if($oferta_activa || $oferta_programada): ?>
        <?php
            $oferta_promo = $oferta_activa ?? $oferta_programada;
            $es_activa = (bool)$oferta_activa;
            $target_date = $es_activa ? $oferta_promo->fecha_fin : $oferta_promo->fecha_inicio;
            $valor_descuento = $oferta_promo->valor_descuento;
            $tipo_descuento = $oferta_promo->tipo_descuento;
        ?>
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
                            <?php if($es_activa): ?>
                                <span class="badge bg-danger text-uppercase px-3 py-2 rounded-pill fw-bold mb-2">¡OFERTA DESTACADA ACTIVA!</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark text-uppercase px-3 py-2 rounded-pill fw-bold mb-2">¡PRÓXIMA OFERTA PROGRAMADA!</span>
                            <?php endif; ?>
                            <h2 class="fw-bold mb-1" style="font-family: 'Poppins', sans-serif; text-shadow: 0 2px 4px rgba(0,0,0,0.15);"><?php echo e($oferta_promo->nombre); ?></h2>
                            <?php if($tipo_descuento === 'porcentaje'): ?>
                                <p class="mb-0 text-white fs-5" style="opacity: 0.95;"><?php echo e($es_activa ? 'Termina en:' : 'Comienza en:'); ?> <strong class="text-warning fs-3" style="text-shadow: 0 1px 2px rgba(0,0,0,0.2);">-<?php echo e($valor_descuento); ?>%</strong> en productos seleccionados.</p>
                            <?php else: ?>
                                <p class="mb-0 text-white fs-5" style="opacity: 0.95;"><?php echo e($es_activa ? 'Termina en:' : 'Comienza en:'); ?> <strong class="text-warning fs-3" style="text-shadow: 0 1px 2px rgba(0,0,0,0.2);">-<?php echo e(\App\Models\Ajuste::formatear_precio($valor_descuento)); ?></strong> de descuento.</p>
                            <?php endif; ?>
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
                const targetDate = new Date("<?php echo e(\Carbon\Carbon::parse($target_date)->toIso8601String()); ?>").getTime();
                
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
    <?php endif; ?>

    <!-- Carousel of Products by Sales-Prioritized Categories -->
    <?php if($categorias_home->isNotEmpty()): ?>
        <?php $__currentLoopData = $categorias_home; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $categoria): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if($categoria->productos_carrusel->isNotEmpty()): ?>
                <section class="section py-5 bg-white">
                    <div class="container">
                        <div class="container section-title d-flex justify-content-between align-items-center mb-2" data-aos="fade-up">
                            <div>
                                <h2 class="mb-0 text-start"><?php echo e($categoria->nombre); ?></h2>
                                <p class="text-start">Encuentra los mejores productos seleccionados</p>
                            </div>
                            <a href="<?php echo e(route('web.productos', ['categoria' => $categoria->slug])); ?>" class="btn btn-outline-primary rounded-pill px-4 btn-sm fw-semibold">
                                Ver Todo
                            </a>
                        </div>

                        <!-- Swiper Carousel -->
                        <div class="position-relative px-md-5">
                            <div class="swiper mySwiper-<?php echo e($categoria->id); ?>">
                                <div class="swiper-wrapper">
                                    <?php $__currentLoopData = $categoria->productos_carrusel; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $producto): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="swiper-slide h-auto p-2">
                                            <?php echo $__env->make('partials.producto_card', ['producto' => $producto], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            </div>
                            
                            <!-- Navigation controls outside swiper wrapper for premium display -->
                            <div class="swiper-button-next swiper-button-next-<?php echo e($categoria->id); ?> d-none d-md-flex"></div>
                            <div class="swiper-button-prev swiper-button-prev-<?php echo e($categoria->id); ?> d-none d-md-flex"></div>
                        </div>
                    </div>
                </section>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        new Swiper('.mySwiper-<?php echo e($categoria->id); ?>', {
                            slidesPerView: 1,
                            spaceBetween: 15,
                            autoplay: {
                                delay: <?php echo e($ajuste->home_carrusel_velocidad ?? 3000); ?>,
                                disableOnInteraction: false,
                            },
                            navigation: {
                                nextEl: '.swiper-button-next-<?php echo e($categoria->id); ?>',
                                prevEl: '.swiper-button-prev-<?php echo e($categoria->id); ?>',
                            },
                            breakpoints: {
                                576: { slidesPerView: 2 },
                                768: { slidesPerView: 3 },
                                992: { slidesPerView: 4 }
                            }
                        });
                    });
                </script>
            <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php endif; ?>

    <!-- Dynamic Storefront Sections -->
    <?php $__currentLoopData = $secciones; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $seccion): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
            $datos = $datos_secciones[$seccion->clave] ?? collect();
        ?>

        <?php if($datos->isNotEmpty()): ?>
            <?php if($seccion->clave === 'marcas'): ?>
                <!-- Sección de Marcas -->
                <section class="section py-5 bg-light border-top">
                    <div class="container">
                        <div class="container section-title" data-aos="fade-up">
                            <h2><?php echo e($seccion->titulo); ?></h2>
                            <p>Nuestras marcas oficiales aliadas</p>
                        </div>

                        <div class="row g-4 justify-content-center">
                            <?php $__currentLoopData = $datos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $marca): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="col-6 col-md-3 col-lg-2">
                                    <div class="card h-100 border rounded p-3 text-center brand-card shadow-sm">
                                        <?php if (isset($component)) { $__componentOriginal3c5517e3a65bd079027085ae592b6aba = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c5517e3a65bd079027085ae592b6aba = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.marca-logo','data' => ['marca' => $marca,'width' => '100%','height' => '60px','class' => 'mb-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('marca-logo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['marca' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($marca),'width' => '100%','height' => '60px','class' => 'mb-2']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3c5517e3a65bd079027085ae592b6aba)): ?>
<?php $attributes = $__attributesOriginal3c5517e3a65bd079027085ae592b6aba; ?>
<?php unset($__attributesOriginal3c5517e3a65bd079027085ae592b6aba); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3c5517e3a65bd079027085ae592b6aba)): ?>
<?php $component = $__componentOriginal3c5517e3a65bd079027085ae592b6aba; ?>
<?php unset($__componentOriginal3c5517e3a65bd079027085ae592b6aba); ?>
<?php endif; ?>
                                        <div class="fw-bold text-dark"><?php echo e($marca->nombre); ?></div>
                                        <small class="text-muted"><?php echo e($marca->productos_count); ?> productos</small>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                </section>
            <?php elseif($seccion->clave === 'categorias_populares'): ?>
                <!-- Sección de Categorías -->
                <section class="section py-5 bg-white border-top">
                    <div class="container">
                        <div class="container section-title" data-aos="fade-up">
                            <h2><?php echo e($seccion->titulo); ?></h2>
                            <p>Explore por sus categorías favoritas</p>
                        </div>

                        <div class="row g-4 justify-content-center">
                            <?php $__currentLoopData = $datos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $categoria): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="card h-100 border-0 shadow-sm category-card text-center p-4" style="border-radius: 12px; background: #f8fafc;">
                                        <div class="fs-1 text-primary mb-3">
                                            <i class="<?php echo e($categoria->icono ?? 'bi-tag'); ?>"></i>
                                        </div>
                                        <h5 class="fw-bold mb-1"><?php echo e($categoria->nombre); ?></h5>
                                        <small class="text-muted d-block mb-3"><?php echo e($categoria->productos_count); ?> productos</small>
                                        <a href="<?php echo e(route('web.categoria', $categoria->slug)); ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                            Ver productos
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                </section>
            <?php else: ?>
                <!-- Sección de Productos (destacados, ofertas, nuevos, recomendados) -->
                <section class="section py-5 <?php echo e($loop->even ? 'bg-white' : 'bg-light'); ?> border-top">
                    <div class="container">
                        <div class="container section-title text-start mb-4" data-aos="fade-up">
                            <h2><?php echo e($seccion->titulo); ?></h2>
                            <?php if($seccion->clave === 'productos_destacados'): ?>
                                <p>Nuestra selección de productos destacados</p>
                            <?php elseif($seccion->clave === 'ofertas'): ?>
                                <p>Descuentos especiales por tiempo limitado</p>
                            <?php elseif($seccion->clave === 'nuevos_productos'): ?>
                                <p>Explora nuestras últimas novedades</p>
                            <?php elseif($seccion->clave === 'productos_recomendados'): ?>
                                <p>Nuestras recomendaciones exclusivas</p>
                            <?php else: ?>
                                <p>Encuentra lo que buscas</p>
                            <?php endif; ?>
                        </div>

                        <div class="row g-4">
                            <?php $__currentLoopData = $datos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $producto): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="col-lg-3 col-md-6 col-sm-12">
                                    <?php echo $__env->make('partials.producto_card', ['producto' => $producto], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>
        <?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.web', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\ecommerce (4)\ecommerce\resources\views/web/index.blade.php ENDPATH**/ ?>