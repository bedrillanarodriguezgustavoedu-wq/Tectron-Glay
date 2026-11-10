@php
    $ajuste = \App\Models\Ajuste::first() ?? new \App\Models\Ajuste();
@endphp
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>@hasSection('title')@yield('title') â€” {{ $ajuste->nombre }}@else{{ $ajuste->nombre }} Â· {{ $ajuste->descripcion }}@endif</title>
    <meta name="description" content="@yield('meta_descripcion', $ajuste->descripcion)">
    <meta name="keywords" content="">
    <meta name="robots" content="@yield('meta_robots', 'index, follow')">

    <!-- Favicons -->
    <link href="{{ asset('storage/' . $ajuste->logo) }}" rel="icon">
    <link href="{{ asset('storage/' . $ajuste->logo) }}" rel="apple-touch-icon">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Roboto:ital,wght@0,300;0,400;0,500;0,700;1,400&family=Montserrat:wght@500;600;700;800&family=Poppins:wght@500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="{{ asset('/assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('/assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    <link href="{{ asset('/assets/vendor/swiper/swiper-bundle.min.css') }}" rel="stylesheet">
    <link href="{{ asset('/assets/vendor/aos/aos.css') }}" rel="stylesheet">
    <link href="{{ asset('/assets/vendor/glightbox/css/glightbox.min.css') }}" rel="stylesheet">
    <link href="{{ asset('/assets/vendor/drift-zoom/drift-basic.css') }}" rel="stylesheet">



    <!-- Main CSS File -->
    <link href="{{ asset('/assets/css/main.css') }}" rel="stylesheet">

    <!-- GALY Theme -->
    <link href="{{ asset('/assets/css/galy.css') }}?v=4" rel="stylesheet">

    <meta name="theme-color" content="#0b1220">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* Dynamic Theme Colors from Admin Settings */
        :root {
            --color-primary: {{ $ajuste->color_primario ?? '#0d6efd' }};
            --color-secondary: {{ $ajuste->color_secundario ?? '#6c757d' }};
            --color-accent: {{ $ajuste->color_acento ?? '#0dcaf0' }};
            --color-text: {{ $ajuste->color_textos_principales ?? '#212529' }};
            --color-header-bg: {{ $ajuste->color_encabezado ?? '#ffffff' }};
            --color-footer-bg: {{ $ajuste->color_pie_pagina ?? '#212529' }};
            --color-card-bg: {{ $ajuste->color_tarjetas ?? '#ffffff' }};
            --color-badge-bg: {{ $ajuste->color_badges ?? '#0d6efd' }};
            --color-offer-bg: {{ $ajuste->color_etiquetas_ofertas ?? '#dc3545' }};
            --color-btn-cart-bg: {{ $ajuste->color_botones_carrito ?? '#0d6efd' }};
            --color-btn-primary-bg: {{ $ajuste->color_botones_principales ?? '#0d6efd' }};
            
            --color-nav-bg: rgba(255, 255, 255, 0.92);
            --color-glass-border: rgba(220, 224, 230, 0.5);
            --color-shadow-soft: 0 10px 30px rgba(0, 0, 0, 0.04);
        }

        body {
            color: var(--color-text) !important;
        }

        a {
            color: {{ $ajuste->color_enlaces ?? '#0d6efd' }};
        }

        .header {
            background-color: var(--color-header-bg) !important;
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-bottom: 1px solid var(--color-glass-border);
            transition: all 0.3s ease;
        }

        /* PersonalizaciÃ³n del MenÃº de NavegaciÃ³n */
        .header .header-nav {
            background-color: {{ $ajuste->menu_bg_color ?? '#ffffff' }} !important;
            @if(isset($ajuste->menu_opacity))
                opacity: {{ $ajuste->menu_opacity }} !important;
            @endif
        }

        .navmenu a {
            color: {{ $ajuste->menu_text_color ?? '#475569' }} !important;
        }

        .navmenu a i {
            color: {{ $ajuste->menu_icon_color ?? '#475569' }} !important;
        }

        .navmenu a:hover {
            color: {{ $ajuste->menu_hover_color ?? '#0d6efd' }} !important;
            background: {{ $ajuste->menu_hover_bg_color ?? '#f1f5f9' }} !important;
        }

        .navmenu a:hover i {
            color: {{ $ajuste->menu_hover_color ?? '#0d6efd' }} !important;
        }

        .navmenu a.active, .navmenu .active {
            color: {{ $ajuste->menu_active_color ?? '#0d6efd' }} !important;
            background: {{ $ajuste->menu_hover_bg_color ?? '#f1f5f9' }} !important;
            @if(isset($ajuste->menu_active_border_color))
                border-bottom: 2px solid {{ $ajuste->menu_active_border_color }} !important;
                border-radius: 8px 8px 0 0 !important;
            @endif
        }

        .navmenu a.active i, .navmenu .active i {
            color: {{ $ajuste->menu_active_color ?? '#0d6efd' }} !important;
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

        @php
            $gTelefono = trim((string) ($contact_info->telefono_principal ?? ''));
            $gWhatsappRaw = trim((string) ($contact_info->whatsapp ?? ''));
            preg_match('~(?:https?://)?(?:www\.)?wa\.me/(\+?\d+)~i', $gWhatsappRaw, $gWaMatch);
            $gWhatsappNumber = $gWaMatch[1] ?? preg_replace('/\D+/', '', $gWhatsappRaw);
            $gWhatsappLink = $gWhatsappNumber ? 'https://wa.me/' . $gWhatsappNumber : null;
            $gTelefonoLink = $gTelefono ? preg_replace('/[^\d\+]/', '', $gTelefono) : null;
        @endphp

        <!-- Barra superior -->
        <div class="g-topbar d-none d-sm-block">
            <div class="container-xl">
                <ul class="g-topbar-list">
                    <li class="g-topbar-item"><i class="bi bi-truck"></i> EnvÃ­o a todo el PerÃº</li>
                    <li class="g-topbar-item"><i class="bi bi-shield-check"></i> Compra 100% segura</li>
                    <li class="g-topbar-item"><i class="bi bi-arrow-repeat"></i> Cambios sin complicaciones</li>
                </ul>
                <ul class="g-topbar-links">
                    @if($gWhatsappLink)
                        <li class="g-topbar-item">
                            <a href="{{ $gWhatsappLink }}" target="_blank" rel="noopener noreferrer">
                                <i class="bi bi-whatsapp"></i> {{ $gWhatsappNumber }}
                            </a>
                        </li>
                    @elseif($gTelefono)
                        <li class="g-topbar-item">
                            <a href="tel:{{ $gTelefonoLink }}"><i class="bi bi-telephone"></i> {{ $gTelefono }}</a>
                        </li>
                    @endif
                    <li class="g-topbar-item"><a href="{{ route('web.contacto') }}"><i class="bi bi-headset"></i> Ayuda</a></li>
                    <li class="g-topbar-item"><a href="{{ url('/dashboard') }}"><i class="bi bi-box-seam"></i> Mis pedidos</a></li>
                    <li class="g-topbar-item">
                        <span class="g-topbar-highlight"><i class="bi bi-lightning-charge-fill"></i> Ofertas todos los dÃ­as</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Main Header -->
        <div class="main-header py-3">
            <div class="container-fluid container-xl">
                <div class="row align-items-center g-3">
                    
                    <!-- Logo -->
                    <div class="col-6 col-md-3 order-1">
                        <a href="{{ route('web.index') }}" class="logo d-flex align-items-center text-decoration-none gap-2">
                            @if($ajuste && $ajuste->logo)
                                <img src="{{ asset('storage/' . $ajuste->logo) }}" alt="{{ $ajuste->nombre }}" style="max-height: 40px;">
                            @endif
                            <h1 class="sitename mb-0 fs-4 fw-bold text-dark">{{ $ajuste->nombre ?? 'Tienda' }}</h1>
                        </a>
                    </div>

                    <!-- Search Form with Autocomplete -->
                    <div class="col-12 col-md-6 order-3 order-md-2">
                        <form class="search-form position-relative" method="GET" action="{{ route('web.buscar_producto') }}">
                            <div class="input-group search-container">
                                <input type="text" id="search-input" autocomplete="off" class="form-control search-input" value="{{ $query ?? '' }}" name="producto" placeholder="Busca laptops, componentes, marcas...">
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
                            @if(Auth::check())
                                <button class="header-action-btn" data-bs-toggle="dropdown">
                                    <i class="bi bi-person-circle"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow border-0 glass-dropdown py-2 mt-2" style="width: 240px;">
                                    <li class="px-3 py-2 border-bottom">
                                        <div class="fw-bold text-truncate">{{ Auth::user()->name }}</div>
                                        <div class="small text-muted text-truncate">{{ Auth::user()->email }}</div>
                                    </li>
                                    <li><a class="dropdown-item py-2" href="{{ url('/dashboard') }}"><i class="bi bi-person me-2"></i> Mi Perfil</a></li>
                                    <li><a class="dropdown-item py-2" href="{{ url('/dashboard') }}"><i class="bi bi-bag-check me-2"></i> Mis Pedidos</a></li>
                                    <li><a class="dropdown-item py-2" href="{{ route('web.favoritos.index') }}"><i class="bi bi-heart me-2"></i> Favoritos</a></li>
                                    <li><a class="dropdown-item py-2" href="{{ route('web.reservas.index') }}"><i class="bi bi-bookmark-check me-2"></i> Mis reservas</a></li>
                                    <li><a class="dropdown-item py-2" href="{{ url('/ajustes') }}"><i class="bi bi-gear me-2"></i> Ajustes</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li class="px-3">
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger w-100 rounded-pill"><i class="bi bi-box-arrow-right me-1"></i> Cerrar SesiÃ³n</button>
                                        </form>
                                    </li>
                                </ul>
                            @else
                                <button class="header-action-btn" data-bs-toggle="dropdown">
                                    <i class="bi bi-person"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow border-0 glass-dropdown p-3 mt-2" style="width: 220px;">
                                    <li class="text-center mb-2 small text-muted">Bienvenido</li>
                                    <li><a href="{{ route('web.login') }}" class="btn btn-primary btn-sm w-100 mb-2 rounded-pill">Iniciar SesiÃ³n</a></li>
                                    <li><a href="{{ route('web.registro') }}" class="btn btn-outline-primary btn-sm w-100 rounded-pill">Registrarse</a></li>
                                </ul>
                            @endif
                        </div>

                        <!-- Wishlist link -->
                        <a href="{{ route('web.favoritos.index') }}" class="header-action-btn g-header-fav" aria-label="Mis favoritos" title="Mis favoritos">
                            <i class="bi bi-heart"></i>
                            <span class="fav-counter g-count {{ ($cantidad_favoritos ?? 0) > 0 ? '' : 'is-zero' }}">{{ $cantidad_favoritos ?? 0 }}</span>
                        </a>

                        <!-- Mini Cart Dropdown -->
                        <div class="dropdown mini-cart-dropdown-wrapper">
                            <a href="{{ route('web.carrito.index') }}" class="header-action-btn g-header-cart" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-label="Mi carrito" title="Mi carrito">
                                <i class="bi bi-cart"></i>
                                <span class="cart-counter g-count {{ ($carrito_cantidad ?? 0) > 0 ? '' : 'is-zero' }}">{{ $carrito_cantidad ?? 0 }}</span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 glass-dropdown py-3 mt-2" style="width: 320px;">
                                <div class="px-3 pb-2 border-bottom d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 fw-bold">Mi Carrito</h6>
                                    <span class="badge bg-primary rounded-pill">{{ $carrito_cantidad ?? 0 }} Ã­tems</span>
                                </div>
                                <div class="mini-cart-items px-3 py-2 overflow-y-auto" style="max-height: 250px;">
                                    @forelse($carrito_items ?? [] as $item)
                                        @if($item->producto)
                                            <div class="d-flex align-items-center mb-3">
                                                <img src="{{ $item->producto->imagenes->first()?->url ?? asset('storage/productos/default.webp') }}" class="img-thumbnail me-2" style="width: 50px; height: 50px; object-fit: cover;">
                                                <div class="flex-grow-1 min-w-0">
                                                    <h6 class="mb-0 text-truncate text-small fw-semibold">{{ $item->producto->nombre }}</h6>
                                                    <div class="text-muted small">{{ $item->cantidad }} x {!! \App\Models\Ajuste::formatear_precio($item->producto->precio_oferta ?? $item->producto->precio_venta) !!}</div>
                                                </div>
                                                <form method="POST" action="{{ route('web.carrito.destroy', $item->id) }}" class="ms-2">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn text-danger p-0 border-0 bg-transparent"><i class="bi bi-trash"></i></button>
                                                </form>
                                            </div>
                                        @endif
                                    @empty
                                        <div class="text-center py-4 text-muted">
                                            <i class="bi bi-cart-x display-6 mb-2 d-block text-secondary"></i>
                                            <span>Tu carrito estÃ¡ vacÃ­o</span>
                                        </div>
                                    @endforelse
                                </div>
                                @if(($carrito_cantidad ?? 0) > 0)
                                    <div class="px-3 pt-3 border-top">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span class="text-muted">Subtotal:</span>
                                            <strong class="fs-5 text-primary">{!! \App\Models\Ajuste::formatear_precio($carrito_subtotal ?? 0) !!}</strong>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <a href="{{ route('web.carrito.index') }}" class="btn btn-outline-primary btn-sm w-100 rounded-pill">Ver Carrito</a>
                                            </div>
                                            <div class="col-6">
                                                <a href="{{ route('web.carrito.index') }}" class="btn btn-primary btn-sm w-100 rounded-pill">Comprar</a>
                                            </div>
                                        </div>
                                    </div>
                                @endif
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
                        <li><a href="{{ route('web.index') }}" class="{{ Route::is('web.index') ? 'active' : '' }}">Inicio</a></li>
                        
                        <!-- Categories MegaMenu -->
                        <li class="dropdown mega-dropdown">
                            <a href="#"><span>CategorÃ­as</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
                            
                            <!-- Mobile Categories list -->
                            <ul class="d-xl-none">
                                <li><a href="{{ route('web.productos') }}"><i class="bi bi-star-fill text-warning me-2"></i> Productos Destacados</a></li>
                                <li><a href="{{ route('web.productos') }}"><i class="bi bi-clock-fill text-primary me-2"></i> Nuevos Productos</a></li>
                                @if(($productos_ofertas ?? collect())->isNotEmpty())
                                    <li><a href="{{ route('web.ofertas') }}"><i class="bi bi-percent text-success me-2"></i> Ofertas</a></li>
                                @endif
                                <li class="dropdown"><a href="#"><span>Ver Por CategorÃ­a</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
                                    <ul>
                                        @foreach($menu_categorias ?? [] as $cat)
                                            <li><a href="{{ route('web.categoria', $cat->slug) }}"><i class="{{ $cat->icono ?? 'bi-tag' }} me-2"></i> {{ $cat->nombre }}</a></li>
                                        @endforeach
                                    </ul>
                                </li>
                            </ul>

                            <!-- Desktop MegaMenu View -->
                            <div class="mega-menu-container d-none d-xl-block dropdown-menu p-4">
                                <div class="row">
                                    
                                    <!-- Sidebar link controllers -->
                                    <div class="col-md-3 mega-menu-sidebar d-flex flex-column gap-1">
                                        <a href="{{ route('web.productos') }}" class="mega-menu-sidebar-link text-danger active" data-bs-target="#tab-destacados">
                                            <i class="bi bi-star-fill"></i> Productos Destacados
                                        </a>
                                        <a href="{{ route('web.productos') }}" class="mega-menu-sidebar-link text-primary" data-bs-target="#tab-nuevos">
                                            <i class="bi bi-clock-fill"></i> Nuevos Productos
                                        </a>
                                        @if(($productos_ofertas ?? collect())->isNotEmpty())
                                            <a href="{{ route('web.ofertas') }}" class="mega-menu-sidebar-link text-success" data-bs-target="#tab-ofertas">
                                                <i class="bi bi-percent"></i> Ofertas
                                            </a>
                                        @endif
                                        <hr class="my-2 text-muted">
                                        @foreach($menu_categorias ?? [] as $cat)
                                            <a href="{{ route('web.categoria', $cat->slug) }}" class="mega-menu-sidebar-link" data-bs-target="#tab-cat-{{ $cat->id }}">
                                                <i class="{{ $cat->icono ?? 'bi-tag' }}"></i> {{ $cat->nombre }}
                                            </a>
                                        @endforeach
                                    </div>

                                    <!-- Content container panel -->
                                    <div class="col-md-9 p-3 bg-light rounded-4 overflow-hidden">
                                        
                                        <!-- Hidden standard buttons for tab triggers -->
                                        <div class="d-none">
                                            <button id="btn-tab-destacados" data-bs-toggle="tab" data-bs-target="#tab-destacados" type="button"></button>
                                            <button id="btn-tab-nuevos" data-bs-toggle="tab" data-bs-target="#tab-nuevos" type="button"></button>
                                            @if(($productos_ofertas ?? collect())->isNotEmpty())
                                                <button id="btn-tab-ofertas" data-bs-toggle="tab" data-bs-target="#tab-ofertas" type="button"></button>
                                            @endif
                                            @foreach($menu_categorias ?? [] as $cat)
                                                <button id="btn-tab-cat-{{ $cat->id }}" data-bs-toggle="tab" data-bs-target="#tab-cat-{{ $cat->id }}" type="button"></button>
                                            @endforeach
                                        </div>

                                        <div class="tab-content" id="megaMenuTabContent">
                                            
                                            <!-- Destacados tab -->
                                            <div class="tab-pane fade show active" id="tab-destacados" role="tabpanel">
                                                <h5 class="fw-bold mb-3"><i class="bi bi-star-fill text-warning me-1"></i> Productos Destacados</h5>
                                                <div class="row row-cols-4 g-3">
                                                    @foreach($productos_destacados ?? [] as $prod)
                                                        <div class="col">
                                                            <div class="megamenu-product-card shadow-sm border-0 h-100 d-flex flex-column justify-content-between">
                                                                <div>
                                                                    <img src="{{ $prod->imagenes->first()?->url ?? asset('storage/productos/default.webp') }}" alt="{{ $prod->nombre }}">
                                                                    <h6 class="mb-1 fw-bold">{{ $prod->nombre }}</h6>
                                                                </div>
                                                                <div>
                                                                    <div class="price mb-2">
                                                                        @if($prod->precio_oferta)
                                                                            <span class="original-price text-decoration-line-through text-muted small me-1">{!! \App\Models\Ajuste::formatear_precio($prod->precio_venta) !!}</span>
                                                                            <span class="text-danger fw-bold">{!! \App\Models\Ajuste::formatear_precio($prod->precio_oferta) !!}</span>
                                                                        @else
                                                                            <span>{!! \App\Models\Ajuste::formatear_precio($prod->precio_venta) !!}</span>
                                                                        @endif
                                                                    </div>
                                                                    <a href="{{ route('web.detalle_producto', $prod->id) }}" class="btn-view w-100 rounded-pill">Ver producto</a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>

                                            <!-- Nuevos tab -->
                                            <div class="tab-pane fade" id="tab-nuevos" role="tabpanel">
                                                <h5 class="fw-bold mb-3"><i class="bi bi-clock-fill text-primary me-1"></i> Nuevos Productos</h5>
                                                <div class="row row-cols-4 g-3">
                                                    @foreach($productos_nuevos ?? [] as $prod)
                                                        <div class="col">
                                                            <div class="megamenu-product-card shadow-sm border-0 h-100 d-flex flex-column justify-content-between">
                                                                <div>
                                                                    <img src="{{ $prod->imagenes->first()?->url ?? asset('storage/productos/default.webp') }}" alt="{{ $prod->nombre }}">
                                                                    <h6 class="mb-1 fw-bold">{{ $prod->nombre }}</h6>
                                                                </div>
                                                                <div>
                                                                    <div class="price mb-2">
                                                                        @if($prod->precio_oferta)
                                                                            <span class="original-price text-decoration-line-through text-muted small me-1">{!! \App\Models\Ajuste::formatear_precio($prod->precio_venta) !!}</span>
                                                                            <span class="text-danger fw-bold">{!! \App\Models\Ajuste::formatear_precio($prod->precio_oferta) !!}</span>
                                                                        @else
                                                                            <span>{!! \App\Models\Ajuste::formatear_precio($prod->precio_venta) !!}</span>
                                                                        @endif
                                                                    </div>
                                                                    <a href="{{ route('web.detalle_producto', $prod->id) }}" class="btn-view w-100 rounded-pill">Ver producto</a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>

                                            <!-- Ofertas tab -->
                                            @if(($productos_ofertas ?? collect())->isNotEmpty())
                                                <div class="tab-pane fade" id="tab-ofertas" role="tabpanel">
                                                    <h5 class="fw-bold mb-3"><i class="bi bi-percent text-success me-1"></i> Ofertas Especiales</h5>
                                                    <div class="row row-cols-4 g-3">
                                                        @foreach($productos_ofertas ?? [] as $prod)
                                                            <div class="col">
                                                                <div class="megamenu-product-card shadow-sm border-0 h-100 d-flex flex-column justify-content-between">
                                                                    <div>
                                                                        <img src="{{ $prod->imagenes->first()?->url ?? asset('storage/productos/default.webp') }}" alt="{{ $prod->nombre }}">
                                                                        <h6 class="mb-1 fw-bold">{{ $prod->nombre }}</h6>
                                                                    </div>
                                                                    <div>
                                                                        <div class="price mb-2">
                                                                            <span class="original-price text-decoration-line-through text-muted small me-1">{!! \App\Models\Ajuste::formatear_precio($prod->precio_venta) !!}</span>
                                                                            <span class="text-danger fw-bold">{!! \App\Models\Ajuste::formatear_precio($prod->precio_oferta) !!}</span>
                                                                        </div>
                                                                        <a href="{{ route('web.detalle_producto', $prod->id) }}" class="btn-view w-100 rounded-pill">Ver producto</a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif

                                            <!-- Dynamic Categories tabs -->
                                            @foreach($menu_categorias ?? [] as $cat)
                                                <div class="tab-pane fade" id="tab-cat-{{ $cat->id }}" role="tabpanel">
                                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                                        <h5 class="fw-bold mb-0"><i class="{{ $cat->icono ?? 'bi-tag' }} text-primary me-1"></i> {{ $cat->nombre }}</h5>
                                                        <a href="{{ route('web.categoria', $cat->slug) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">Ver Todo</a>
                                                    </div>
                                                    <div class="row row-cols-4 g-3">
                                                        @forelse($cat->productos ?? [] as $prod)
                                                            <div class="col">
                                                                <div class="megamenu-product-card shadow-sm border-0 h-100 d-flex flex-column justify-content-between">
                                                                    <div>
                                                                        <img src="{{ $prod->imagenes->first()?->url ?? asset('storage/productos/default.webp') }}" alt="{{ $prod->nombre }}">
                                                                        <h6 class="mb-1 fw-bold text-truncate">{{ $prod->nombre }}</h6>
                                                                    </div>
                                                                    <div>
                                                                        <div class="price mb-2">
                                                                            @if($prod->precio_oferta)
                                                                                <span class="original-price text-decoration-line-through text-muted small me-1">{!! \App\Models\Ajuste::formatear_precio($prod->precio_venta) !!}</span>
                                                                                <span class="text-danger fw-bold">{!! \App\Models\Ajuste::formatear_precio($prod->precio_oferta) !!}</span>
                                                                            @else
                                                                                <span>{!! \App\Models\Ajuste::formatear_precio($prod->precio_venta) !!}</span>
                                                                            @endif
                                                                        </div>
                                                                        <a href="{{ route('web.detalle_producto', $prod->id) }}" class="btn-view w-100 rounded-pill">Ver producto</a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @empty
                                                            <div class="col-12 text-center py-5 text-muted">
                                                                <i class="bi bi-info-circle display-5 mb-2 d-block"></i>
                                                                <span>No hay productos en esta categorÃ­a</span>
                                                            </div>
                                                        @endforelse
                                                    </div>
                                                </div>
                                            @endforeach

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>

                        <li><a href="{{ route('web.productos') }}" class="{{ Route::is('web.productos') ? 'active' : '' }}">Productos</a></li>
                        
                        @if(($productos_ofertas ?? collect())->isNotEmpty())
                            <li><a href="{{ route('web.ofertas') }}" class="{{ Route::is('web.ofertas') ? 'active' : '' }}">Ofertas</a></li>
                        @endif

                        <!-- Brands Mega Menu -->
                        <li class="dropdown mega-dropdown">
                            <a href="{{ route('web.marcas') }}" class="{{ Route::is('web.marcas') ? 'active' : '' }}"><span>Marcas</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
                            
                            <!-- Mobile view brands -->
                            <ul class="d-xl-none">
                                @foreach($menu_marcas ?? [] as $marca)
                                    <li><a href="{{ route('web.productos', ['marca' => $marca->slug]) }}">{{ $marca->nombre }}</a></li>
                                @endforeach
                            </ul>

                            <!-- Desktop brands grid -->
                            <div class="mega-menu-container d-none d-xl-block dropdown-menu p-4">
                                <div class="container">
                                    <h5 class="fw-bold mb-3"><i class="bi bi-gpu-card text-primary me-2"></i>Nuestras Marcas de TecnologÃ­a</h5>
                                    <div class="row row-cols-4 g-3">
                                        @foreach($menu_marcas ?? [] as $marca)
                                            <div class="col">
                                                <a href="{{ route('web.productos', ['marca' => $marca->slug]) }}" class="brand-card d-flex flex-column align-items-center justify-content-center p-3 text-decoration-none h-100">
                                                    <x-marca-logo :marca="$marca" width="100%" height="38px" class="mb-2" />
                                                    <span class="brand-name fw-bold text-dark fs-5 mb-1">{{ $marca->nombre }}</span>
                                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill">{{ $marca->productos_count ?? $marca->productos()->count() }} productos</span>
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </li>

                        <li><a href="{{ route('web.asesores') }}" class="{{ Route::is('web.asesores') ? 'active' : '' }}"><i class="bi bi-people me-1" aria-hidden="true"></i>Asesores</a></li>
                        <li><a href="{{ route('web.nosotros') }}" class="{{ Route::is('web.nosotros') ? 'active' : '' }}">Nosotros</a></li>
                        <li><a href="{{ route('web.contacto') }}#servicio-tecnico"><i class="bi bi-tools me-1" aria-hidden="true"></i>Servicio tÃ©cnico</a></li>
                        <li><a href="{{ route('web.contacto') }}" class="{{ Route::is('web.contacto') ? 'active' : '' }}">Contacto</a></li>
                    </ul>
                </nav>
            </div>
        </div>

    </header>

    <main class="main">

        @yield('content')
    </main>

    <footer id="footer" class="footer dark-background">
        <div class="footer-main">
            <div class="container">
                <div class="row gy-4">
                    <div class="col-lg-4 col-md-6">
                        <div class="footer-widget footer-about">
                            <a href="{{ route('web.index') }}" class="logo text-decoration-none d-block mb-3">
                                @if($footer_settings && $footer_settings->logo)
                                    <img src="{{ asset('storage/' . $footer_settings->logo) }}" alt="{{ $footer_settings->nombre_empresa }}" style="max-height: 45px;">
                                @elseif($ajuste && $ajuste->logo)
                                    <img src="{{ asset('storage/' . $ajuste->logo) }}" alt="{{ $footer_settings->nombre_empresa ?? $ajuste->nombre }}" style="max-height: 45px;">
                                @else
                                    <span class="sitename fs-3">{{ $footer_settings->nombre_empresa ?? 'GALY' }}</span>
                                @endif
                            </a>
                            @if($footer_settings && $footer_settings->descripcion)
                                <p>{{ $footer_settings->descripcion }}</p>
                            @endif

                            @php
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
                            @endphp
                            @if(collect([$facebookLink, $instagramLink, $tiktokLink, $twitterLink, $youtubeLink, $linkedinLink, $whatsappLink])->contains(fn ($link) => filled($link) && $link !== '#'))
                                <div class="social-links mt-4">
                                    <h5 class="text-white">SÃ­guenos en Redes</h5>
                                    <div class="social-icons d-flex gap-2">
                                        @if(filled($facebookLink) && $facebookLink !== '#')
                                            <a href="{{ $facebookLink }}" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                                        @endif
                                        @if(filled($instagramLink) && $instagramLink !== '#')
                                            <a href="{{ $instagramLink }}" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                                        @endif
                                        @if(filled($tiktokLink) && $tiktokLink !== '#')
                                            <a href="{{ $tiktokLink }}" target="_blank" rel="noopener noreferrer" aria-label="TikTok"><i class="bi bi-tiktok"></i></a>
                                        @endif
                                        @if(filled($twitterLink) && $twitterLink !== '#')
                                            <a href="{{ $twitterLink }}" target="_blank" rel="noopener noreferrer" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>
                                        @endif
                                        @if(filled($youtubeLink) && $youtubeLink !== '#')
                                            <a href="{{ $youtubeLink }}" target="_blank" rel="noopener noreferrer" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                                        @endif
                                        @if(filled($linkedinLink) && $linkedinLink !== '#')
                                            <a href="{{ $linkedinLink }}" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
                                        @endif
                                        @if(filled($whatsappLink))
                                            <a href="{{ $whatsappLink }}" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-6 col-sm-6">
                        <div class="footer-widget">
                            <h4>Enlaces RÃ¡pidos</h4>
                            <ul class="footer-links">
                                @forelse($footer_enlaces ?? [] as $enlace)
                                    <li><a href="{{ url($enlace->url) }}">{{ $enlace->titulo }}</a></li>
                                @empty
                                    <li><a href="{{ route('web.productos') }}">Ver CatÃ¡logo</a></li>
                                    <li><a href="{{ route('web.ofertas') }}">Ofertas Especiales</a></li>
                                    <li><a href="{{ route('web.nosotros') }}">Sobre Nosotros</a></li>
                                    <li><a href="{{ route('web.contacto') }}">Contacto</a></li>
                                @endforelse
                            </ul>
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-6 col-sm-6">
                        <div class="footer-widget">
                            <h4>InformaciÃ³n Legal</h4>
                            <ul class="footer-links">
                                <li><a href="{{ url('/terminos-y-condiciones') }}">TÃ©rminos y Condiciones</a></li>
                                <li><a href="{{ url('/politica-de-privacidad') }}">PolÃ­tica de Privacidad</a></li>
                                <li><a href="{{ url('/politica-de-cookies') }}">PolÃ­tica de Cookies</a></li>
                                <li><a href="{{ url('/cambios-y-devoluciones') }}">Cambios y Devoluciones</a></li>
                            </ul>
                        </div>
                    </div>
                               @if(($contact_info && ($contact_info->direccion || $contact_info->telefono_principal || $contact_info->email || $contact_info->horario_atencion)) || ($footer_settings && ($footer_settings->app_store_link || $footer_settings->google_play_link)))
                        <div class="col-lg-4 col-md-6">
                            <div class="footer-widget">
                                @if($contact_info && ($contact_info->direccion || $contact_info->telefono_principal || $contact_info->email || $contact_info->horario_atencion))
                                    <h4>InformaciÃ³n de Contacto</h4>
                                    <div class="footer-contact">
                                    @if($contact_info->direccion)
                                        <div class="contact-item d-flex gap-2 align-items-start mb-2">
                                            <i class="bi bi-geo-alt mt-1"></i>
                                            <span>{{ $contact_info->direccion }}</span>
                                        </div>
                                    @endif
                                    @if($contact_info->telefono_principal)
                                        <div class="contact-item d-flex gap-2 align-items-start mb-2">
                                            <i class="bi bi-telephone mt-1"></i>
                                            <span>{{ $contact_info->telefono_principal }}</span>
                                        </div>
                                    @endif
                                    @if($contact_info->email)
                                        <div class="contact-item d-flex gap-2 align-items-start mb-2">
                                            <i class="bi bi-envelope mt-1"></i>
                                            <span>{{ $contact_info->email }}</span>
                                        </div>
                                    @endif
                                    @if($contact_info->horario_atencion)
                                        <div class="contact-item d-flex gap-2 align-items-start mb-2">
                                            <i class="bi bi-clock mt-1"></i>
                                            <span>{!! nl2br(e($contact_info->horario_atencion)) !!}</span>
                                        </div>
                                    @endif
                                    </div>
                                @endif

                                @if($footer_settings && ($footer_settings->app_store_link || $footer_settings->google_play_link))
                                    <div class="app-buttons mt-4">
                                        @if($footer_settings->app_store_link)
                                            <a href="{{ $footer_settings->app_store_link }}" target="_blank" class="app-btn">
                                                <i class="bi bi-apple"></i>
                                                <span>App Store</span>
                                            </a>
                                        @endif
                                        @if($footer_settings->google_play_link)
                                            <a href="{{ $footer_settings->google_play_link }}" target="_blank" class="app-btn">
                                                <i class="bi bi-google-play"></i>
                                                <span>Google Play</span>
                                            </a>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="container">
                <div class="row gy-3 align-items-center">
                    <div class="col-lg-6 col-md-12">
                        <div class="copyright">
                            <p>{{ $footer_settings->copyright_texto ?? 'Â© Copyright GALY. All Rights Reserved.' }}</p>
                        </div>
                        <div class="credits mt-1">
                            DiseÃ±ado por <a href="https://bootstrapmade.com/">BootstrapMade</a>
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
                                <a href="{{ url('/terminos-y-condiciones') }}">TÃ©rminos</a>
                                <a href="{{ url('/politica-de-privacidad') }}">Privacidad</a>
                                <a href="{{ url('/pago-seguro') }}">Pago seguro</a>
                                <a href="{{ url('/politica-de-cookies') }}">Cookies</a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </footer>

    <!-- NavegaciÃ³n inferior (mÃ³vil) -->
    <nav class="g-dock" aria-label="Accesos rÃ¡pidos">
        <ul class="g-dock-list">
            <li>
                <a href="{{ route('web.index') }}" class="g-dock-link">
                    <i class="bi bi-house-door" aria-hidden="true"></i><span>Inicio</span>
                </a>
            </li>
            <li>
                <a href="{{ route('web.productos') }}" class="g-dock-link">
                    <i class="bi bi-grid" aria-hidden="true"></i><span>CategorÃ­as</span>
                </a>
            </li>
            <li>
                <button type="button" class="g-dock-link" data-g-search aria-label="Buscar">
                    <i class="bi bi-search" aria-hidden="true"></i><span>Buscar</span>
                </button>
            </li>
            <li>
                <a href="{{ route('web.favoritos.index') }}" class="g-dock-link">
                    <i class="bi bi-heart" aria-hidden="true"></i><span>Favoritos</span>
                    <span class="fav-counter g-dock-badge {{ ($cantidad_favoritos ?? 0) > 0 ? '' : 'is-zero' }}">{{ $cantidad_favoritos ?? 0 }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('web.carrito.index') }}" class="g-dock-link">
                    <i class="bi bi-bag" aria-hidden="true"></i><span>Carrito</span>
                    <span class="cart-counter g-dock-badge {{ ($carrito_cantidad ?? 0) > 0 ? '' : 'is-zero' }}">{{ $carrito_cantidad ?? 0 }}</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Scroll Top -->
    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i
            class="bi bi-arrow-up-short"></i></a>

    <!-- Preloader -->
    <div id="preloader"></div>

    <!-- Vendor JS Files -->
    <script src="{{ asset('/assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('/assets/vendor/php-email-form/validate.js') }}"></script>
    <script src="{{ asset('/assets/vendor/swiper/swiper-bundle.min.js') }}"></script>
    <script src="{{ asset('/assets/vendor/aos/aos.js') }}"></script>
    <script src="{{ asset('/assets/vendor/glightbox/js/glightbox.min.js') }}"></script>
    <script src="{{ asset('/assets/vendor/drift-zoom/Drift.min.js') }}"></script>
    <script src="{{ asset('/assets/vendor/purecounter/purecounter_vanilla.js') }}"></script>

    <!-- Main JS File -->
    <script src="{{ asset('/assets/js/main.js') }}"></script>
    <script src="{{ asset('/assets/js/galy.js') }}?v=1"></script>

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
                
                fetch('{{ url("/carrito/agregar") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
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
                                    window.location.href = '{{ route("web.login") }}';
                                    return;
                                }
                                return Promise.reject(data);
                            }
                            return data;
                        });
                    } else {
                        if (res.status === 401 || res.redirected) {
                            window.location.href = '{{ route("web.login") }}';
                            return;
                        }
                        throw new Error('El servidor devolviÃ³ una respuesta no vÃ¡lida (HTML).');
                    }
                })
                .then(data => {
                    if (!data) return; // redirected
                    if (data.success) {
                        const cartCounter = document.querySelectorAll('.cart-counter');
                        cartCounter.forEach(counter => {
                            counter.textContent = data.contador;
                            counter.classList.toggle('is-zero', !data.contador);
                        });
                        document.dispatchEvent(new CustomEvent('galy:counters'));
                        
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
                        title: err.message || 'OcurriÃ³ un error inesperado.'
                    });
                });
            }

            // 2. AJAX Toggle Favorite
            const btnFav = e.target.closest('.toggle-fav-btn');
            if (btnFav) {
                e.preventDefault();
                const prodId = btnFav.getAttribute('data-id');

                fetch('{{ url("/favoritos/toggle") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
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
                                    window.location.href = '{{ route("web.login") }}';
                                    return;
                                }
                                return Promise.reject(data);
                            }
                            return data;
                        });
                    } else {
                        if (res.status === 401 || res.redirected) {
                            window.location.href = '{{ route("web.login") }}';
                            return;
                        }
                        throw new Error('El servidor devolviÃ³ una respuesta no vÃ¡lida (HTML).');
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
                            counter.textContent = data.contador;
                            counter.classList.toggle('is-zero', !data.contador);
                        });
                        document.dispatchEvent(new CustomEvent('galy:counters'));

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
                        title: err.message || 'OcurriÃ³ un error inesperado.'
                    });
                });
            }
        });
    </script>

    @if (($mensaje = Session::get('mensaje')) && ($icono = Session::get('icono')))
        <script>
            Swal.fire({
                position: "center",
                icon: "{{ $icono }}",
                title: "{{ $mensaje }}",
                showConfirmButton: false,
                timer: 2000
            });
        </script>
    @endif

</body>

</html>

