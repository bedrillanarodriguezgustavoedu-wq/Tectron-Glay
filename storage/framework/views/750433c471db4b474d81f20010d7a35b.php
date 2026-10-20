<?php
    $ajuste = \App\Models\Ajuste::first() ?? new \App\Models\Ajuste();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title><?php if (! empty(trim($__env->yieldContent('title')))): ?><?php echo $__env->yieldContent('title'); ?> — <?php echo e($ajuste->nombre); ?><?php else: ?><?php echo e($ajuste->nombre); ?> · <?php echo e($ajuste->descripcion); ?><?php endif; ?></title>
    <meta name="description" content="<?php echo $__env->yieldContent('meta_descripcion', $ajuste->descripcion); ?>">
    <meta name="keywords" content="">
    <meta name="robots" content="<?php echo $__env->yieldContent('meta_robots', 'index, follow'); ?>">

    <!-- Favicons -->
    <link href="<?php echo e(asset('storage/' . $ajuste->logo)); ?>" rel="icon">
    <link href="<?php echo e(asset('storage/' . $ajuste->logo)); ?>" rel="apple-touch-icon">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Montserrat:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="<?php echo e(asset('/assets/vendor/bootstrap/css/bootstrap.min.css')); ?>" rel="stylesheet">
    <link href="<?php echo e(asset('/assets/vendor/bootstrap-icons/bootstrap-icons.css')); ?>" rel="stylesheet">
    <link href="<?php echo e(asset('/assets/vendor/swiper/swiper-bundle.min.css')); ?>" rel="stylesheet">
    <link href="<?php echo e(asset('/assets/vendor/aos/aos.css')); ?>" rel="stylesheet">
    <link href="<?php echo e(asset('/assets/vendor/glightbox/css/glightbox.min.css')); ?>" rel="stylesheet">
    <link href="<?php echo e(asset('/assets/vendor/drift-zoom/drift-basic.css')); ?>" rel="stylesheet">



    <!-- Main CSS File -->
    <link href="<?php echo e(asset('/assets/css/main.css')); ?>" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* Dynamic Theme Colors from Admin Settings */
        :root {
            --color-primary: <?php echo e($ajuste->color_primario ?? '#0d6efd'); ?>;
            --color-secondary: <?php echo e($ajuste->color_secundario ?? '#6c757d'); ?>;
            --color-accent: <?php echo e($ajuste->color_acento ?? '#0dcaf0'); ?>;
            --color-text: <?php echo e($ajuste->color_textos_principales ?? '#212529'); ?>;
            --color-header-bg: <?php echo e($ajuste->color_encabezado ?? '#ffffff'); ?>;
            --color-footer-bg: <?php echo e($ajuste->color_pie_pagina ?? '#212529'); ?>;
            --color-card-bg: <?php echo e($ajuste->color_tarjetas ?? '#ffffff'); ?>;
            --color-badge-bg: <?php echo e($ajuste->color_badges ?? '#0d6efd'); ?>;
            --color-offer-bg: <?php echo e($ajuste->color_etiquetas_ofertas ?? '#dc3545'); ?>;
            --color-btn-cart-bg: <?php echo e($ajuste->color_botones_carrito ?? '#0d6efd'); ?>;
            --color-btn-primary-bg: <?php echo e($ajuste->color_botones_principales ?? '#0d6efd'); ?>;
            
            --color-nav-bg: rgba(255, 255, 255, 0.92);
            --color-glass-border: rgba(220, 224, 230, 0.5);
            --color-shadow-soft: 0 10px 30px rgba(0, 0, 0, 0.04);
        }

        body {
            color: var(--color-text) !important;
        }

        a {
            color: <?php echo e($ajuste->color_enlaces ?? '#0d6efd'); ?>;
        }

        .header {
            background-color: var(--color-header-bg) !important;
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-bottom: 1px solid var(--color-glass-border);
            transition: all 0.3s ease;
        }

        /* Personalización del Menú de Navegación */
        .header .header-nav {
            background-color: <?php echo e($ajuste->menu_bg_color ?? '#ffffff'); ?> !important;
            <?php if(isset($ajuste->menu_opacity)): ?>
                opacity: <?php echo e($ajuste->menu_opacity); ?> !important;
            <?php endif; ?>
        }

        .navmenu a {
            color: <?php echo e($ajuste->menu_text_color ?? '#475569'); ?> !important;
        }

        .navmenu a i {
            color: <?php echo e($ajuste->menu_icon_color ?? '#475569'); ?> !important;
        }

        .navmenu a:hover {
            color: <?php echo e($ajuste->menu_hover_color ?? '#0d6efd'); ?> !important;
            background: <?php echo e($ajuste->menu_hover_bg_color ?? '#f1f5f9'); ?> !important;
        }

        .navmenu a:hover i {
            color: <?php echo e($ajuste->menu_hover_color ?? '#0d6efd'); ?> !important;
        }

        .navmenu a.active, .navmenu .active {
            color: <?php echo e($ajuste->menu_active_color ?? '#0d6efd'); ?> !important;
            background: <?php echo e($ajuste->menu_hover_bg_color ?? '#f1f5f9'); ?> !important;
            <?php if(isset($ajuste->menu_active_border_color)): ?>
                border-bottom: 2px solid <?php echo e($ajuste->menu_active_border_color); ?> !important;
                border-radius: 8px 8px 0 0 !important;
            <?php endif; ?>
        }

        .navmenu a.active i, .navmenu .active i {
            color: <?php echo e($ajuste->menu_active_color ?? '#0d6efd'); ?> !important;
        }

        .footer {
            background-color: var(--color-footer-bg) !important;
        }

        .btn-primary, .theme-btn-cart {
            background-color: var(--color-btn-primary-bg) !important;
            border-color: var(--color-btn-primary-bg) !important;
        }
        
        .btn-primary:hover, .theme-btn-cart:hover {
            filter: brightness(0.9) !important;
        }

        .btn-outline-primary {
            color: var(--color-primary) !important;
            border-color: var(--color-primary) !important;
        }

        .btn-outline-primary:hover {
            background-color: var(--color-primary) !important;
            color: #fff !important;
        }

        .badge.bg-primary, .theme-badge {
            background-color: var(--color-badge-bg) !important;
        }

        .badge.bg-danger, .theme-offer-badge {
            background-color: var(--color-offer-bg) !important;
        }

        .sitename {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            letter-spacing: -0.5px;
            background: linear-gradient(45deg, var(--color-primary), var(--color-accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }



        /* Custom Search Container */
        .search-container {
            border-radius: 50px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            background: #f8fafc;
            width: 100%;
        }
        .search-container:focus-within {
            border-color: var(--color-primary);
            box-shadow: 0 4px 15px color-mix(in srgb, var(--color-primary) 10%, transparent);
            background: #ffffff;
        }
        .search-input {
            border: none !important;
            background: transparent !important;
            padding-left: 20px;
            font-size: 0.95rem;
        }
        .search-input:focus {
            box-shadow: none !important;
        }
        .search-btn {
            background: transparent !important;
            border: none !important;
            padding-right: 15px;
            color: #64748b;
            transition: color 0.2s ease;
        }
        .search-btn:hover {
            color: var(--color-primary);
        }

        /* Autocomplete Suggestions */
        .search-suggestions-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 1050;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid var(--color-glass-border);
            border-radius: 16px;
            margin-top: 8px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            max-height: 400px;
            overflow-y: auto;
            padding: 10px 0;
        }
        .suggestion-item {
            display: flex;
            align-items: center;
            padding: 8px 16px;
            text-decoration: none;
            color: #1e293b;
            transition: background-color 0.2s ease;
        }
        .suggestion-item:hover {
            background-color: color-mix(in srgb, var(--color-primary) 5%, transparent);
            color: var(--color-primary) !important;
        }
        .suggestion-thumb {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 12px;
            border: 1px solid #f1f5f9;
        }
        .suggestion-info {
            flex-grow: 1;
        }
        .suggestion-title {
            font-size: 0.9rem;
            font-weight: 600;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .suggestion-meta {
            font-size: 0.75rem;
            color: #64748b;
        }
        .suggestion-price {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--color-primary);
            text-align: right;
            margin-left: 10px;
        }

        /* Header Action Buttons */
        .header-action-btn {
            background: transparent;
            border: none;
            font-size: 1.4rem;
            color: #475569;
            padding: 8px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            position: relative;
            text-decoration: none;
        }
        .header-action-btn:hover {
            color: var(--color-primary);
            background-color: color-mix(in srgb, var(--color-primary) 5%, transparent);
        }
        .header-action-btn .badge {
            font-size: 0.65rem;
            padding: 4px 6px;
            top: 2px !important;
            right: 2px !important;
        }

        /* Glass Dropdowns */
        .glass-dropdown {
            background: rgba(255, 255, 255, 0.97) !important;
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid var(--color-glass-border) !important;
            border-radius: 16px !important;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06) !important;
            overflow: hidden;
        }

        /* MegaMenu Navigation Styles */
        .navmenu ul {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .navmenu a {
            font-weight: 500;
            color: #475569;
            padding: 10px 15px;
            border-radius: 8px;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-block;
        }
        .navmenu a:hover, .navmenu .active {
            color: var(--color-primary) !important;
            background: color-mix(in srgb, var(--color-primary) 4%, transparent);
        }

        /* Mega dropdown trigger items */
        .mega-dropdown {
            position: static !important;
        }
        .mega-dropdown .mega-menu-container {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.98) !important;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--color-glass-border) !important;
            border-top: none !important;
            box-shadow: 0 20px 45px rgba(0,0,0,0.08) !important;
            border-radius: 0 0 24px 24px !important;
            opacity: 0;
            visibility: hidden;
            display: block !important;
            transform: translateY(10px);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 999;
        }
        .mega-dropdown:hover .mega-menu-container {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        /* Sidebar structure inside Megamenu */
        .mega-menu-sidebar {
            border-right: 1px solid #f1f5f9;
        }
        .mega-menu-sidebar-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #475569;
            text-decoration: none;
            font-weight: 500;
            border-radius: 10px;
            margin-bottom: 4px;
            transition: all 0.2s ease;
            border: none;
            background: transparent;
            width: 100%;
            text-align: left;
        }
        .mega-menu-sidebar-link i {
            font-size: 1.2rem;
            margin-right: 12px;
            color: #64748b;
            transition: color 0.2s ease;
        }
        .mega-menu-sidebar-link:hover, 
        .mega-menu-sidebar-link.active {
            color: var(--color-primary);
            background-color: color-mix(in srgb, var(--color-primary) 5%, transparent);
        }
        .mega-menu-sidebar-link:hover i, 
        .mega-menu-sidebar-link.active i {
            color: var(--color-primary);
        }

        /* Custom Product Card inside Megamenu */
        .megamenu-product-card {
            background: #ffffff;
            border-radius: 14px;
            border: 1px solid #f1f5f9;
            padding: 12px;
            transition: all 0.2s ease;
        }
        .megamenu-product-card:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.04);
            border-color: #e2e8f0;
        }
        .megamenu-product-card img {
            width: 100%;
            height: 120px;
            object-fit: contain;
            border-radius: 8px;
            margin-bottom: 8px;
            background: #f8fafc;
            padding: 8px;
        }
        .megamenu-product-card h6 {
            font-size: 0.85rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 6px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .megamenu-product-card .price {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--color-primary);
        }
        .megamenu-product-card .original-price {
            font-size: 0.75rem;
            color: #64748b;
            text-decoration: line-through;
            margin-right: 6px;
        }
        .megamenu-product-card .btn-view {
            display: block;
            text-align: center;
            font-size: 0.75rem;
            font-weight: 600;
            color: #ffffff;
            background: var(--color-primary);
            padding: 6px 10px;
            border-radius: 8px;
            text-decoration: none;
            margin-top: 8px;
            transition: background 0.2s ease, filter 0.2s ease;
        }
        .megamenu-product-card .btn-view:hover {
            filter: brightness(0.9);
            color: #ffffff !important;
        }

        /* Brand Card inside Brand Mega Menu */
        .brand-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            transition: all 0.2s ease;
        }
        .brand-card:hover {
            border-color: var(--color-primary);
            box-shadow: 0 8px 20px color-mix(in srgb, var(--color-primary) 5%, transparent);
        }
    </style>
</head>

<body class="index-page">

    <header id="header" class="header sticky-top">

        <!-- Main Header -->
        <div class="main-header py-3">
            <div class="container-fluid container-xl">
                <div class="row align-items-center g-3">
                    
                    <!-- Logo -->
                    <div class="col-6 col-md-3 order-1">
                        <a href="<?php echo e(route('web.index')); ?>" class="logo d-flex align-items-center text-decoration-none gap-2">
                            <?php if($ajuste && $ajuste->logo): ?>
                                <img src="<?php echo e(asset('storage/' . $ajuste->logo)); ?>" alt="<?php echo e($ajuste->nombre); ?>" style="max-height: 40px;">
                            <?php endif; ?>
                            <h1 class="sitename mb-0 fs-4 fw-bold text-dark"><?php echo e($ajuste->nombre ?? 'Tienda'); ?></h1>
                        </a>
                    </div>

                    <!-- Search Form with Autocomplete -->
                    <div class="col-12 col-md-6 order-3 order-md-2">
                        <form class="search-form position-relative" method="GET" action="<?php echo e(route('web.buscar_producto')); ?>">
                            <div class="input-group search-container">
                                <input type="text" id="search-input" autocomplete="off" class="form-control search-input" value="<?php echo e($query ?? ''); ?>" name="producto" placeholder="Busca laptops, componentes, marcas...">
                                <button class="btn search-btn" type="submit">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                            <!-- Autocomplete list -->
                            <div id="search-suggestions" class="search-suggestions-dropdown d-none"></div>
                        </form>
                    </div>

                    <!-- Actions -->
                    <div class="col-6 col-md-3 order-2 order-md-3 d-flex align-items-center justify-content-end gap-2">
                        
                        <!-- Account Dropdown -->
                        <div class="dropdown">
                            <?php if(Auth::check()): ?>
                                <button class="header-action-btn" data-bs-toggle="dropdown">
                                    <i class="bi bi-person-circle"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow border-0 glass-dropdown py-2 mt-2" style="width: 240px;">
                                    <li class="px-3 py-2 border-bottom">
                                        <div class="fw-bold text-truncate"><?php echo e(Auth::user()->name); ?></div>
                                        <div class="small text-muted text-truncate"><?php echo e(Auth::user()->email); ?></div>
                                    </li>
                                    <li><a class="dropdown-item py-2" href="<?php echo e(url('/dashboard')); ?>"><i class="bi bi-person me-2"></i> Mi Perfil</a></li>
                                    <li><a class="dropdown-item py-2" href="<?php echo e(url('/dashboard')); ?>"><i class="bi bi-bag-check me-2"></i> Mis Pedidos</a></li>
                                    <li><a class="dropdown-item py-2" href="<?php echo e(route('web.favoritos.index')); ?>"><i class="bi bi-heart me-2"></i> Favoritos</a></li>
                                    <li><a class="dropdown-item py-2" href="<?php echo e(route('web.reservas.index')); ?>"><i class="bi bi-bookmark-check me-2"></i> Mis reservas</a></li>
                                    <li><a class="dropdown-item py-2" href="<?php echo e(url('/ajustes')); ?>"><i class="bi bi-gear me-2"></i> Ajustes</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li class="px-3">
                                        <form method="POST" action="<?php echo e(route('logout')); ?>">
                                            <?php echo csrf_field(); ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger w-100 rounded-pill"><i class="bi bi-box-arrow-right me-1"></i> Cerrar Sesión</button>
                                        </form>
                                    </li>
                                </ul>
                            <?php else: ?>
                                <button class="header-action-btn" data-bs-toggle="dropdown">
                                    <i class="bi bi-person"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow border-0 glass-dropdown p-3 mt-2" style="width: 220px;">
                                    <li class="text-center mb-2 small text-muted">Bienvenido</li>
                                    <li><a href="<?php echo e(route('web.login')); ?>" class="btn btn-primary btn-sm w-100 mb-2 rounded-pill">Iniciar Sesión</a></li>
                                    <li><a href="<?php echo e(route('web.registro')); ?>" class="btn btn-outline-primary btn-sm w-100 rounded-pill">Registrarse</a></li>
                                </ul>
                            <?php endif; ?>
                        </div>

                        <!-- Wishlist link -->
                        <a href="<?php echo e(route('web.favoritos.index')); ?>" class="header-action-btn d-flex align-items-center gap-1 text-decoration-none">
                            <i class="bi bi-heart"></i>
                            <span class="fav-counter text-dark small" style="font-size: 13px;">(<?php echo e($cantidad_favoritos ?? 0); ?>)</span>
                        </a>

                        <!-- Mini Cart Dropdown -->
                        <div class="dropdown mini-cart-dropdown-wrapper">
                            <a href="<?php echo e(route('web.carrito.index')); ?>" class="header-action-btn d-flex align-items-center gap-1 text-decoration-none" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                                <i class="bi bi-cart"></i>
                                <span class="cart-counter text-dark small" style="font-size: 13px;">(<?php echo e($carrito_cantidad ?? 0); ?>)</span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 glass-dropdown py-3 mt-2" style="width: 320px;">
                                <div class="px-3 pb-2 border-bottom d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 fw-bold">Mi Carrito</h6>
                                    <span class="badge bg-primary rounded-pill"><?php echo e($carrito_cantidad ?? 0); ?> ítems</span>
                                </div>
                                <div class="mini-cart-items px-3 py-2 overflow-y-auto" style="max-height: 250px;">
                                    <?php $__empty_1 = true; $__currentLoopData = $carrito_items ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                        <?php if($item->producto): ?>
                                            <div class="d-flex align-items-center mb-3">
                                                <img src="<?php echo e($item->producto->imagenes->first()?->url ?? asset('storage/productos/default.webp')); ?>" class="img-thumbnail me-2" style="width: 50px; height: 50px; object-fit: cover;">
                                                <div class="flex-grow-1 min-w-0">
                                                    <h6 class="mb-0 text-truncate text-small fw-semibold"><?php echo e($item->producto->nombre); ?></h6>
                                                    <div class="text-muted small"><?php echo e($item->cantidad); ?> x <?php echo \App\Models\Ajuste::formatear_precio($item->producto->precio_oferta ?? $item->producto->precio_venta); ?></div>
                                                </div>
                                                <form method="POST" action="<?php echo e(route('web.carrito.destroy', $item->id)); ?>" class="ms-2">
                                                    <?php echo csrf_field(); ?>
                                                    <?php echo method_field('DELETE'); ?>
                                                    <button type="submit" class="btn text-danger p-0 border-0 bg-transparent"><i class="bi bi-trash"></i></button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                        <div class="text-center py-4 text-muted">
                                            <i class="bi bi-cart-x display-6 mb-2 d-block text-secondary"></i>
                                            <span>Tu carrito está vacío</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php if(($carrito_cantidad ?? 0) > 0): ?>
                                    <div class="px-3 pt-3 border-top">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span class="text-muted">Subtotal:</span>
                                            <strong class="fs-5 text-primary"><?php echo \App\Models\Ajuste::formatear_precio($carrito_subtotal ?? 0); ?></strong>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <a href="<?php echo e(route('web.carrito.index')); ?>" class="btn btn-outline-primary btn-sm w-100 rounded-pill">Ver Carrito</a>
                                            </div>
                                            <div class="col-6">
                                                <a href="<?php echo e(route('web.carrito.index')); ?>" class="btn btn-primary btn-sm w-100 rounded-pill">Comprar</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Mobile Menu Toggle -->
                        <i class="mobile-nav-toggle d-xl-none bi bi-list me-0 fs-2" style="cursor: pointer;"></i>

                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Bar -->
        <div class="header-nav">
            <div class="container-fluid container-xl position-relative">
                <nav id="navmenu" class="navmenu">
                    <ul>
                        <li><a href="<?php echo e(route('web.index')); ?>" class="<?php echo e(Route::is('web.index') ? 'active' : ''); ?>">Inicio</a></li>
                        
                        <!-- Categories MegaMenu -->
                        <li class="dropdown mega-dropdown">
                            <a href="#"><span>Categorías</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
                            
                            <!-- Mobile Categories list -->
                            <ul class="d-xl-none">
                                <li><a href="<?php echo e(route('web.productos')); ?>"><i class="bi bi-star-fill text-warning me-2"></i> Productos Destacados</a></li>
                                <li><a href="<?php echo e(route('web.productos')); ?>"><i class="bi bi-clock-fill text-primary me-2"></i> Nuevos Productos</a></li>
                                <?php if(($productos_ofertas ?? collect())->isNotEmpty()): ?>
                                    <li><a href="<?php echo e(route('web.ofertas')); ?>"><i class="bi bi-percent text-success me-2"></i> Ofertas</a></li>
                                <?php endif; ?>
                                <li class="dropdown"><a href="#"><span>Ver Por Categoría</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
                                    <ul>
                                        <?php $__currentLoopData = $menu_categorias ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <li><a href="<?php echo e(route('web.categoria', $cat->slug)); ?>"><i class="<?php echo e($cat->icono ?? 'bi-tag'); ?> me-2"></i> <?php echo e($cat->nombre); ?></a></li>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </ul>
                                </li>
                            </ul>

                            <!-- Desktop MegaMenu View -->
                            <div class="mega-menu-container d-none d-xl-block dropdown-menu p-4">
                                <div class="row">
                                    
                                    <!-- Sidebar link controllers -->
                                    <div class="col-md-3 mega-menu-sidebar d-flex flex-column gap-1">
                                        <a href="<?php echo e(route('web.productos')); ?>" class="mega-menu-sidebar-link text-danger active" data-bs-target="#tab-destacados">
                                            <i class="bi bi-star-fill"></i> Productos Destacados
                                        </a>
                                        <a href="<?php echo e(route('web.productos')); ?>" class="mega-menu-sidebar-link text-primary" data-bs-target="#tab-nuevos">
                                            <i class="bi bi-clock-fill"></i> Nuevos Productos
                                        </a>
                                        <?php if(($productos_ofertas ?? collect())->isNotEmpty()): ?>
                                            <a href="<?php echo e(route('web.ofertas')); ?>" class="mega-menu-sidebar-link text-success" data-bs-target="#tab-ofertas">
                                                <i class="bi bi-percent"></i> Ofertas
                                            </a>
                                        <?php endif; ?>
                                        <hr class="my-2 text-muted">
                                        <?php $__currentLoopData = $menu_categorias ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <a href="<?php echo e(route('web.categoria', $cat->slug)); ?>" class="mega-menu-sidebar-link" data-bs-target="#tab-cat-<?php echo e($cat->id); ?>">
                                                <i class="<?php echo e($cat->icono ?? 'bi-tag'); ?>"></i> <?php echo e($cat->nombre); ?>

                                            </a>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>

                                    <!-- Content container panel -->
                                    <div class="col-md-9 p-3 bg-light rounded-4 overflow-hidden">
                                        
                                        <!-- Hidden standard buttons for tab triggers -->
                                        <div class="d-none">
                                            <button id="btn-tab-destacados" data-bs-toggle="tab" data-bs-target="#tab-destacados" type="button"></button>
                                            <button id="btn-tab-nuevos" data-bs-toggle="tab" data-bs-target="#tab-nuevos" type="button"></button>
                                            <?php if(($productos_ofertas ?? collect())->isNotEmpty()): ?>
                                                <button id="btn-tab-ofertas" data-bs-toggle="tab" data-bs-target="#tab-ofertas" type="button"></button>
                                            <?php endif; ?>
                                            <?php $__currentLoopData = $menu_categorias ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <button id="btn-tab-cat-<?php echo e($cat->id); ?>" data-bs-toggle="tab" data-bs-target="#tab-cat-<?php echo e($cat->id); ?>" type="button"></button>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </div>

                                        <div class="tab-content" id="megaMenuTabContent">
                                            
                                            <!-- Destacados tab -->
                                            <div class="tab-pane fade show active" id="tab-destacados" role="tabpanel">
                                                <h5 class="fw-bold mb-3"><i class="bi bi-star-fill text-warning me-1"></i> Productos Destacados</h5>
                                                <div class="row row-cols-4 g-3">
                                                    <?php $__currentLoopData = $productos_destacados ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $prod): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <div class="col">
                                                            <div class="megamenu-product-card shadow-sm border-0 h-100 d-flex flex-column justify-content-between">
                                                                <div>
                                                                    <img src="<?php echo e($prod->imagenes->first()?->url ?? asset('storage/productos/default.webp')); ?>" alt="<?php echo e($prod->nombre); ?>">
                                                                    <h6 class="mb-1 fw-bold"><?php echo e($prod->nombre); ?></h6>
                                                                </div>
                                                                <div>
                                                                    <div class="price mb-2">
                                                                        <?php if($prod->precio_oferta): ?>
                                                                            <span class="original-price text-decoration-line-through text-muted small me-1"><?php echo \App\Models\Ajuste::formatear_precio($prod->precio_venta); ?></span>
                                                                            <span class="text-danger fw-bold"><?php echo \App\Models\Ajuste::formatear_precio($prod->precio_oferta); ?></span>
                                                                        <?php else: ?>
                                                                            <span><?php echo \App\Models\Ajuste::formatear_precio($prod->precio_venta); ?></span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <a href="<?php echo e(route('web.detalle_producto', $prod->id)); ?>" class="btn-view w-100 rounded-pill">Ver producto</a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </div>
                                            </div>

                                            <!-- Nuevos tab -->
                                            <div class="tab-pane fade" id="tab-nuevos" role="tabpanel">
                                                <h5 class="fw-bold mb-3"><i class="bi bi-clock-fill text-primary me-1"></i> Nuevos Productos</h5>
                                                <div class="row row-cols-4 g-3">
                                                    <?php $__currentLoopData = $productos_nuevos ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $prod): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <div class="col">
                                                            <div class="megamenu-product-card shadow-sm border-0 h-100 d-flex flex-column justify-content-between">
                                                                <div>
                                                                    <img src="<?php echo e($prod->imagenes->first()?->url ?? asset('storage/productos/default.webp')); ?>" alt="<?php echo e($prod->nombre); ?>">
                                                                    <h6 class="mb-1 fw-bold"><?php echo e($prod->nombre); ?></h6>
                                                                </div>
                                                                <div>
                                                                    <div class="price mb-2">
                                                                        <?php if($prod->precio_oferta): ?>
                                                                            <span class="original-price text-decoration-line-through text-muted small me-1"><?php echo \App\Models\Ajuste::formatear_precio($prod->precio_venta); ?></span>
                                                                            <span class="text-danger fw-bold"><?php echo \App\Models\Ajuste::formatear_precio($prod->precio_oferta); ?></span>
                                                                        <?php else: ?>
                                                                            <span><?php echo \App\Models\Ajuste::formatear_precio($prod->precio_venta); ?></span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <a href="<?php echo e(route('web.detalle_producto', $prod->id)); ?>" class="btn-view w-100 rounded-pill">Ver producto</a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </div>
                                            </div>

                                            <!-- Ofertas tab -->
                                            <?php if(($productos_ofertas ?? collect())->isNotEmpty()): ?>
                                                <div class="tab-pane fade" id="tab-ofertas" role="tabpanel">
                                                    <h5 class="fw-bold mb-3"><i class="bi bi-percent text-success me-1"></i> Ofertas Especiales</h5>
                                                    <div class="row row-cols-4 g-3">
                                                        <?php $__currentLoopData = $productos_ofertas ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $prod): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                            <div class="col">
                                                                <div class="megamenu-product-card shadow-sm border-0 h-100 d-flex flex-column justify-content-between">
                                                                    <div>
                                                                        <img src="<?php echo e($prod->imagenes->first()?->url ?? asset('storage/productos/default.webp')); ?>" alt="<?php echo e($prod->nombre); ?>">
                                                                        <h6 class="mb-1 fw-bold"><?php echo e($prod->nombre); ?></h6>
                                                                    </div>
                                                                    <div>
                                                                        <div class="price mb-2">
                                                                            <span class="original-price text-decoration-line-through text-muted small me-1"><?php echo \App\Models\Ajuste::formatear_precio($prod->precio_venta); ?></span>
                                                                            <span class="text-danger fw-bold"><?php echo \App\Models\Ajuste::formatear_precio($prod->precio_oferta); ?></span>
                                                                        </div>
                                                                        <a href="<?php echo e(route('web.detalle_producto', $prod->id)); ?>" class="btn-view w-100 rounded-pill">Ver producto</a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Dynamic Categories tabs -->
                                            <?php $__currentLoopData = $menu_categorias ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <div class="tab-pane fade" id="tab-cat-<?php echo e($cat->id); ?>" role="tabpanel">
                                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                                        <h5 class="fw-bold mb-0"><i class="<?php echo e($cat->icono ?? 'bi-tag'); ?> text-primary me-1"></i> <?php echo e($cat->nombre); ?></h5>
                                                        <a href="<?php echo e(route('web.categoria', $cat->slug)); ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">Ver Todo</a>
                                                    </div>
                                                    <div class="row row-cols-4 g-3">
                                                        <?php $__empty_1 = true; $__currentLoopData = $cat->productos ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $prod): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                                            <div class="col">
                                                                <div class="megamenu-product-card shadow-sm border-0 h-100 d-flex flex-column justify-content-between">
                                                                    <div>
                                                                        <img src="<?php echo e($prod->imagenes->first()?->url ?? asset('storage/productos/default.webp')); ?>" alt="<?php echo e($prod->nombre); ?>">
                                                                        <h6 class="mb-1 fw-bold text-truncate"><?php echo e($prod->nombre); ?></h6>
                                                                    </div>
                                                                    <div>
                                                                        <div class="price mb-2">
                                                                            <?php if($prod->precio_oferta): ?>
                                                                                <span class="original-price text-decoration-line-through text-muted small me-1"><?php echo \App\Models\Ajuste::formatear_precio($prod->precio_venta); ?></span>
                                                                                <span class="text-danger fw-bold"><?php echo \App\Models\Ajuste::formatear_precio($prod->precio_oferta); ?></span>
                                                                            <?php else: ?>
                                                                                <span><?php echo \App\Models\Ajuste::formatear_precio($prod->precio_venta); ?></span>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                        <a href="<?php echo e(route('web.detalle_producto', $prod->id)); ?>" class="btn-view w-100 rounded-pill">Ver producto</a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                                            <div class="col-12 text-center py-5 text-muted">
                                                                <i class="bi bi-info-circle display-5 mb-2 d-block"></i>
                                                                <span>No hay productos en esta categoría</span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>

                        <li><a href="<?php echo e(route('web.productos')); ?>" class="<?php echo e(Route::is('web.productos') ? 'active' : ''); ?>">Productos</a></li>
                        
                        <?php if(($productos_ofertas ?? collect())->isNotEmpty()): ?>
                            <li><a href="<?php echo e(route('web.ofertas')); ?>" class="<?php echo e(Route::is('web.ofertas') ? 'active' : ''); ?>">Ofertas</a></li>
                        <?php endif; ?>

                        <!-- Brands Mega Menu -->
                        <li class="dropdown mega-dropdown">
                            <a href="<?php echo e(route('web.marcas')); ?>" class="<?php echo e(Route::is('web.marcas') ? 'active' : ''); ?>"><span>Marcas</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
                            
                            <!-- Mobile view brands -->
                            <ul class="d-xl-none">
                                <?php $__currentLoopData = $menu_marcas ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $marca): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li><a href="<?php echo e(route('web.productos', ['marca' => $marca->slug])); ?>"><?php echo e($marca->nombre); ?></a></li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>

                            <!-- Desktop brands grid -->
                            <div class="mega-menu-container d-none d-xl-block dropdown-menu p-4">
                                <div class="container">
                                    <h5 class="fw-bold mb-3"><i class="bi bi-gpu-card text-primary me-2"></i>Nuestras Marcas de Tecnología</h5>
                                    <div class="row row-cols-4 g-3">
                                        <?php $__currentLoopData = $menu_marcas ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $marca): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <div class="col">
                                                <a href="<?php echo e(route('web.productos', ['marca' => $marca->slug])); ?>" class="brand-card d-flex flex-column align-items-center justify-content-center p-3 text-decoration-none h-100">
                                                    <?php if (isset($component)) { $__componentOriginal3c5517e3a65bd079027085ae592b6aba = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c5517e3a65bd079027085ae592b6aba = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.marca-logo','data' => ['marca' => $marca,'width' => '100%','height' => '38px','class' => 'mb-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('marca-logo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['marca' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($marca),'width' => '100%','height' => '38px','class' => 'mb-2']); ?>
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
                                                    <span class="brand-name fw-bold text-dark fs-5 mb-1"><?php echo e($marca->nombre); ?></span>
                                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill"><?php echo e($marca->productos_count ?? $marca->productos()->count()); ?> productos</span>
                                                </a>
                                            </div>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>
                                </div>
                            </div>
                        </li>

                        <li><a href="<?php echo e(route('web.nosotros')); ?>" class="<?php echo e(Route::is('web.nosotros') ? 'active' : ''); ?>">Nosotros</a></li>
                        <li><a href="<?php echo e(route('web.contacto')); ?>#servicio-tecnico"><i class="bi bi-tools me-1" aria-hidden="true"></i>Servicio técnico</a></li>
                        <li><a href="<?php echo e(route('web.contacto')); ?>" class="<?php echo e(Route::is('web.contacto') ? 'active' : ''); ?>">Contacto</a></li>
                    </ul>
                </nav>
            </div>
        </div>

    </header>

    <main class="main">

        <?php echo $__env->yieldContent('content'); ?>
    </main>

    <footer id="footer" class="footer dark-background">
        <div class="footer-main">
            <div class="container">
                <div class="row gy-4">
                    <div class="col-lg-4 col-md-6">
                        <div class="footer-widget footer-about">
                            <a href="<?php echo e(route('web.index')); ?>" class="logo text-decoration-none d-block mb-3">
                                <?php if($footer_settings && $footer_settings->logo): ?>
                                    <img src="<?php echo e(asset('storage/' . $footer_settings->logo)); ?>" alt="<?php echo e($footer_settings->nombre_empresa); ?>" style="max-height: 45px;">
                                <?php elseif($ajuste && $ajuste->logo): ?>
                                    <img src="<?php echo e(asset('storage/' . $ajuste->logo)); ?>" alt="<?php echo e($footer_settings->nombre_empresa ?? $ajuste->nombre); ?>" style="max-height: 45px;">
                                <?php else: ?>
                                    <span class="sitename fs-3"><?php echo e($footer_settings->nombre_empresa ?? 'Tectron Galy'); ?></span>
                                <?php endif; ?>
                            </a>
                            <?php if($footer_settings && $footer_settings->descripcion): ?>
                                <p><?php echo e($footer_settings->descripcion); ?></p>
                            <?php endif; ?>

                            <?php
                                $facebookLink = $contact_info?->facebook_link;
                                $instagramLink = $contact_info?->instagram_link;
                                $twitterLink = $contact_info?->twitter_link;
                                $youtubeLink = $contact_info?->youtube_link;
                                $tiktokLink = $footer_settings?->tiktok_link;
                                $linkedinLink = $footer_settings?->linkedin_link;
                                $whatsappValue = trim((string) ($contact_info?->whatsapp ?? ''));
                                preg_match('~(?:https?://)?(?:www\.)?wa\.me/(\+?\d+)~i', $whatsappValue, $whatsappMatch);
                                $whatsappNumber = $whatsappMatch[1] ?? preg_replace('/\D+/', '', $whatsappValue);
                                $whatsappLink = $whatsappNumber ? 'https://wa.me/' . $whatsappNumber : null;
                            ?>
                            <?php if(collect([$facebookLink, $instagramLink, $tiktokLink, $twitterLink, $youtubeLink, $linkedinLink, $whatsappLink])->contains(fn ($link) => filled($link) && $link !== '#')): ?>
                                <div class="social-links mt-4">
                                    <h5 class="text-white">Síguenos en Redes</h5>
                                    <div class="social-icons d-flex gap-2">
                                        <?php if(filled($facebookLink) && $facebookLink !== '#'): ?>
                                            <a href="<?php echo e($facebookLink); ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                                        <?php endif; ?>
                                        <?php if(filled($instagramLink) && $instagramLink !== '#'): ?>
                                            <a href="<?php echo e($instagramLink); ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                                        <?php endif; ?>
                                        <?php if(filled($tiktokLink) && $tiktokLink !== '#'): ?>
                                            <a href="<?php echo e($tiktokLink); ?>" target="_blank" rel="noopener noreferrer" aria-label="TikTok"><i class="bi bi-tiktok"></i></a>
                                        <?php endif; ?>
                                        <?php if(filled($twitterLink) && $twitterLink !== '#'): ?>
                                            <a href="<?php echo e($twitterLink); ?>" target="_blank" rel="noopener noreferrer" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>
                                        <?php endif; ?>
                                        <?php if(filled($youtubeLink) && $youtubeLink !== '#'): ?>
                                            <a href="<?php echo e($youtubeLink); ?>" target="_blank" rel="noopener noreferrer" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                                        <?php endif; ?>
                                        <?php if(filled($linkedinLink) && $linkedinLink !== '#'): ?>
                                            <a href="<?php echo e($linkedinLink); ?>" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
                                        <?php endif; ?>
                                        <?php if(filled($whatsappLink)): ?>
                                            <a href="<?php echo e($whatsappLink); ?>" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-6 col-sm-6">
                        <div class="footer-widget">
                            <h4>Enlaces Rápidos</h4>
                            <ul class="footer-links">
                                <?php $__empty_1 = true; $__currentLoopData = $footer_enlaces ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $enlace): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <li><a href="<?php echo e(url($enlace->url)); ?>"><?php echo e($enlace->titulo); ?></a></li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <li><a href="<?php echo e(route('web.productos')); ?>">Ver Catálogo</a></li>
                                    <li><a href="<?php echo e(route('web.ofertas')); ?>">Ofertas Especiales</a></li>
                                    <li><a href="<?php echo e(route('web.nosotros')); ?>">Sobre Nosotros</a></li>
                                    <li><a href="<?php echo e(route('web.contacto')); ?>">Contacto</a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-6 col-sm-6">
                        <div class="footer-widget">
                            <h4>Información Legal</h4>
                            <ul class="footer-links">
                                <li><a href="<?php echo e(url('/terminos-y-condiciones')); ?>">Términos y Condiciones</a></li>
                                <li><a href="<?php echo e(url('/politica-de-privacidad')); ?>">Política de Privacidad</a></li>
                                <li><a href="<?php echo e(url('/politica-de-cookies')); ?>">Política de Cookies</a></li>
                                <li><a href="<?php echo e(url('/cambios-y-devoluciones')); ?>">Cambios y Devoluciones</a></li>
                            </ul>
                        </div>
                    </div>
                               <?php if(($contact_info && ($contact_info->direccion || $contact_info->telefono_principal || $contact_info->email || $contact_info->horario_atencion)) || ($footer_settings && ($footer_settings->app_store_link || $footer_settings->google_play_link))): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="footer-widget">
                                <?php if($contact_info && ($contact_info->direccion || $contact_info->telefono_principal || $contact_info->email || $contact_info->horario_atencion)): ?>
                                    <h4>Información de Contacto</h4>
                                    <div class="footer-contact">
                                    <?php if($contact_info->direccion): ?>
                                        <div class="contact-item d-flex gap-2 align-items-start mb-2">
                                            <i class="bi bi-geo-alt mt-1"></i>
                                            <span><?php echo e($contact_info->direccion); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if($contact_info->telefono_principal): ?>
                                        <div class="contact-item d-flex gap-2 align-items-start mb-2">
                                            <i class="bi bi-telephone mt-1"></i>
                                            <span><?php echo e($contact_info->telefono_principal); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if($contact_info->email): ?>
                                        <div class="contact-item d-flex gap-2 align-items-start mb-2">
                                            <i class="bi bi-envelope mt-1"></i>
                                            <span><?php echo e($contact_info->email); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if($contact_info->horario_atencion): ?>
                                        <div class="contact-item d-flex gap-2 align-items-start mb-2">
                                            <i class="bi bi-clock mt-1"></i>
                                            <span><?php echo nl2br(e($contact_info->horario_atencion)); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if($footer_settings && ($footer_settings->app_store_link || $footer_settings->google_play_link)): ?>
                                    <div class="app-buttons mt-4">
                                        <?php if($footer_settings->app_store_link): ?>
                                            <a href="<?php echo e($footer_settings->app_store_link); ?>" target="_blank" class="app-btn">
                                                <i class="bi bi-apple"></i>
                                                <span>App Store</span>
                                            </a>
                                        <?php endif; ?>
                                        <?php if($footer_settings->google_play_link): ?>
                                            <a href="<?php echo e($footer_settings->google_play_link); ?>" target="_blank" class="app-btn">
                                                <i class="bi bi-google-play"></i>
                                                <span>Google Play</span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="container">
                <div class="row gy-3 align-items-center">
                    <div class="col-lg-6 col-md-12">
                        <div class="copyright">
                            <p><?php echo e($footer_settings->copyright_texto ?? '© Copyright Tectron Galy. All Rights Reserved.'); ?></p>
                        </div>
                        <div class="credits mt-1">
                            Diseñado por <a href="https://bootstrapmade.com/">BootstrapMade</a>
                        </div>
                    </div>

                    <div class="col-lg-6 col-md-12">
                        <div class="d-flex flex-wrap justify-content-lg-end justify-content-center align-items-center gap-4">
                            <div class="payment-methods">
                                <div class="payment-icons">
                                    <span class="badge bg-light text-dark border">Yape</span>
                                    <span class="badge bg-light text-dark border">Plin</span>
                                </div>
                            </div>

                            <div class="legal-links">
                                <a href="<?php echo e(url('/terminos-y-condiciones')); ?>">Términos</a>
                                <a href="<?php echo e(url('/politica-de-privacidad')); ?>">Privacidad</a>
                                <a href="<?php echo e(url('/politica-de-cookies')); ?>">Cookies</a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </footer>

    <!-- Scroll Top -->
    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i
            class="bi bi-arrow-up-short"></i></a>

    <!-- Preloader -->
    <div id="preloader"></div>

    <!-- Vendor JS Files -->
    <script src="<?php echo e(asset('/assets/vendor/bootstrap/js/bootstrap.bundle.min.js')); ?>"></script>
    <script src="<?php echo e(asset('/assets/vendor/php-email-form/validate.js')); ?>"></script>
    <script src="<?php echo e(asset('/assets/vendor/swiper/swiper-bundle.min.js')); ?>"></script>
    <script src="<?php echo e(asset('/assets/vendor/aos/aos.js')); ?>"></script>
    <script src="<?php echo e(asset('/assets/vendor/glightbox/js/glightbox.min.js')); ?>"></script>
    <script src="<?php echo e(asset('/assets/vendor/drift-zoom/Drift.min.js')); ?>"></script>
    <script src="<?php echo e(asset('/assets/vendor/purecounter/purecounter_vanilla.js')); ?>"></script>

    <!-- Main JS File -->
    <script src="<?php echo e(asset('/assets/js/main.js')); ?>"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarLinks = document.querySelectorAll('.mega-menu-sidebar-link');
            sidebarLinks.forEach(link => {
                link.addEventListener('mouseenter', function() {
                    sidebarLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                    
                    const target = this.getAttribute('data-bs-target');
                    if (target) {
                        const hiddenTriggerId = 'btn-' + target.substring(1);
                        const hiddenTrigger = document.getElementById(hiddenTriggerId);
                        if (hiddenTrigger) {
                            hiddenTrigger.click();
                        }
                    }
                });
            });
        });

        // AJAX Wishlist and Cart handlers
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        document.addEventListener('click', function(e) {
            // 1. AJAX Add to Cart
            const btnCart = e.target.closest('.add-to-cart-btn');
            if (btnCart) {
                e.preventDefault();
                const prodId = btnCart.getAttribute('data-id');
                
                fetch('<?php echo e(url("/carrito/agregar")); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        producto_id: prodId,
                        cantidad: 1
                    })
                })
                .then(res => {
                    const contentType = res.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        return res.json().then(data => {
                            if (!res.ok) {
                                if (res.status === 401 || data.code === 401) {
                                    window.location.href = '<?php echo e(route("web.login")); ?>';
                                    return;
                                }
                                return Promise.reject(data);
                            }
                            return data;
                        });
                    } else {
                        if (res.status === 401 || res.redirected) {
                            window.location.href = '<?php echo e(route("web.login")); ?>';
                            return;
                        }
                        throw new Error('El servidor devolvió una respuesta no válida (HTML).');
                    }
                })
                .then(data => {
                    if (!data) return; // redirected
                    if (data.success) {
                        const cartCounter = document.querySelectorAll('.cart-counter');
                        cartCounter.forEach(counter => {
                            counter.textContent = `(${data.contador})`;
                        });
                        
                        Toast.fire({
                            icon: 'success',
                            title: data.mensaje || 'Producto agregado al carrito'
                        });

                        // Dynamic Mini Cart Update using DOMParser
                        fetch(window.location.href)
                            .then(res => res.text())
                            .then(html => {
                                const parser = new DOMParser();
                                const doc = parser.parseFromString(html, 'text/html');
                                const newDropdown = doc.querySelector('.mini-cart-dropdown-wrapper');
                                const currentDropdown = document.querySelector('.mini-cart-dropdown-wrapper');
                                if (newDropdown && currentDropdown) {
                                    currentDropdown.innerHTML = newDropdown.innerHTML;
                                }
                            });
                    } else {
                        Toast.fire({
                            icon: 'error',
                            title: data.message || 'Error al agregar el producto'
                        });
                    }
                })
                .catch(err => {
                    console.error(err);
                    Toast.fire({
                        icon: 'error',
                        title: err.message || 'Ocurrió un error inesperado.'
                    });
                });
            }

            // 2. AJAX Toggle Favorite
            const btnFav = e.target.closest('.toggle-fav-btn');
            if (btnFav) {
                e.preventDefault();
                const prodId = btnFav.getAttribute('data-id');

                fetch('<?php echo e(url("/favoritos/toggle")); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        producto_id: prodId
                    })
                })
                .then(res => {
                    const contentType = res.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        return res.json().then(data => {
                            if (!res.ok) {
                                if (res.status === 401 || data.code === 401) {
                                    window.location.href = '<?php echo e(route("web.login")); ?>';
                                    return;
                                }
                                return Promise.reject(data);
                            }
                            return data;
                        });
                    } else {
                        if (res.status === 401 || res.redirected) {
                            window.location.href = '<?php echo e(route("web.login")); ?>';
                            return;
                        }
                        throw new Error('El servidor devolvió una respuesta no válida (HTML).');
                    }
                })
                .then(data => {
                    if (!data) return; // redirected
                    if (data.success) {
                        const icon = btnFav.querySelector('i');
                        if (data.action === 'added') {
                            icon.classList.remove('bi-heart', 'text-secondary');
                            icon.classList.add('bi-heart-fill', 'text-danger');
                        } else {
                            icon.classList.remove('bi-heart-fill', 'text-danger');
                            icon.classList.add('bi-heart', 'text-secondary');
                            
                            // If we are currently on the favorites page, remove card container
                            if (window.location.pathname.includes('/favoritos')) {
                                const cardCol = btnFav.closest('.col-lg-3, .col-md-6, .col-sm-12');
                                if (cardCol) {
                                    cardCol.remove();
                                    
                                    const favGrid = document.querySelector('.favorites-grid');
                                    if (favGrid && favGrid.querySelectorAll('.product-card').length === 0) {
                                        window.location.reload();
                                    }
                                }
                            }
                        }

                        const favCounter = document.querySelectorAll('.fav-counter');
                        favCounter.forEach(counter => {
                            counter.textContent = `(${data.contador})`;
                        });

                        Toast.fire({
                            icon: 'success',
                            title: data.mensaje
                        });
                    } else {
                        Toast.fire({
                            icon: 'error',
                            title: data.message || 'Error al actualizar favoritos'
                        });
                    }
                })
                .catch(err => {
                    console.error(err);
                    Toast.fire({
                        icon: 'error',
                        title: err.message || 'Ocurrió un error inesperado.'
                    });
                });
            }
        });
    </script>

    <?php if(($mensaje = Session::get('mensaje')) && ($icono = Session::get('icono'))): ?>
        <script>
            Swal.fire({
                position: "center",
                icon: "<?php echo e($icono); ?>",
                title: "<?php echo e($mensaje); ?>",
                showConfirmButton: false,
                timer: 2000
            });
        </script>
    <?php endif; ?>

</body>

</html>
<?php /**PATH E:\ecommerce (4)\ecommerce\resources\views/layouts/web.blade.php ENDPATH**/ ?>