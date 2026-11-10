@extends('layouts.admin')

@section('title', 'Dashboard · ' . ($ajuste->nombre ?? 'TECTRON'))

@section('content')
    @php
        $usuario = auth()->user();
        $rolesUsuario = $usuario->roles->pluck('name')->implode(' · ');
    @endphp

    <section class="ga-hero mb-4">
        <div>
            <span class="ga-hero-eyebrow">
                <i class="bi bi-grid-1x2-fill" aria-hidden="true"></i>
                Panel de administración · {{ now()->translatedFormat('F Y') }}
            </span>
            <h1>Hola, {{ $usuario->name }}</h1>
            <p>Catálogo, pedidos, pagos y la app GALY en un solo lugar. Revisa lo importante y actúa rápido.</p>
            <div class="ga-hero-actions">
                @can('Listado de Pedidos')
                    <a class="btn btn-light btn-sm" href="{{ route('admin.pedidos.index') }}">
                        <i class="bi bi-receipt" aria-hidden="true"></i> Revisar pedidos
                    </a>
                @endcan
                @can('Crear Producto')
                    <a class="btn-ga-hero-ghost" href="{{ route('admin.productos.create') }}">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i> Nuevo producto
                    </a>
                @endcan
                <a class="btn-ga-hero-ghost" href="{{ url('/') }}" target="_blank" rel="noopener">
                    <i class="bi bi-shop" aria-hidden="true"></i> Ver tienda
                </a>
            </div>
        </div>
        <div class="ga-hero-side">
            <span class="ga-hero-role">
                <i class="bi bi-shield-check" aria-hidden="true"></i>
                {{ $rolesUsuario !== '' ? $rolesUsuario : 'Usuario' }}
            </span>
            <span class="ga-hero-date">{{ now()->translatedFormat('l, d \d\e F \d\e Y') }}</span>
        </div>
    </section>

    {{-- Indicadores principales --}}
    <div class="row g-3 mb-1">
        @can('Listado de Productos')
            <div class="col-6 col-xl-3">
                <a class="ga-kpi" href="{{ route('admin.productos.index') }}">
                    <div class="ga-kpi-top">
                        <div>
                            <p class="ga-kpi-label">Productos</p>
                            <p class="ga-kpi-value">{{ number_format($total_productos) }}</p>
                        </div>
                        <span class="ga-kpi-icon"><i class="bi bi-box-seam-fill" aria-hidden="true"></i></span>
                    </div>
                    <span class="ga-kpi-foot">
                        <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                        Administrar catálogo
                    </span>
                </a>
            </div>
        @endcan

        @can('Listado de Pedidos')
            <div class="col-6 col-xl-3">
                <a class="ga-kpi" href="{{ route('admin.pedidos.index') }}">
                    <div class="ga-kpi-top">
                        <div>
                            <p class="ga-kpi-label">Pedidos nuevos</p>
                            <p class="ga-kpi-value">{{ number_format($total_pedidos_nuevos) }}</p>
                        </div>
                        <span class="ga-kpi-icon is-warning"><i class="bi bi-inbox-fill" aria-hidden="true"></i></span>
                    </div>
                    <span class="ga-kpi-foot">
                        <i class="bi bi-clock-history" aria-hidden="true"></i>
                        Pendientes de atender
                    </span>
                </a>
            </div>
        @endcan

        @can('Listado de Usuarios')
            <div class="col-6 col-xl-3">
                <a class="ga-kpi" href="{{ url('/admin/usuarios') }}">
                    <div class="ga-kpi-top">
                        <div>
                            <p class="ga-kpi-label">Usuarios</p>
                            <p class="ga-kpi-value">{{ number_format($total_usuarios) }}</p>
                        </div>
                        <span class="ga-kpi-icon is-info"><i class="bi bi-people-fill" aria-hidden="true"></i></span>
                    </div>
                    <span class="ga-kpi-foot">
                        <i class="bi bi-person-check" aria-hidden="true"></i>
                        Cuentas registradas
                    </span>
                </a>
            </div>
        @endcan

        @can('Listado de Categorías')
            <div class="col-6 col-xl-3">
                <a class="ga-kpi" href="{{ url('/admin/categorias') }}">
                    <div class="ga-kpi-top">
                        <div>
                            <p class="ga-kpi-label">Categorías</p>
                            <p class="ga-kpi-value">{{ number_format($total_categorias) }}</p>
                        </div>
                        <span class="ga-kpi-icon is-purple"><i class="bi bi-tags-fill" aria-hidden="true"></i></span>
                    </div>
                    <span class="ga-kpi-foot">
                        <i class="bi bi-diagram-3" aria-hidden="true"></i>
                        Organización del catálogo
                    </span>
                </a>
            </div>
        @endcan
    </div>

    {{-- Administrador compartido GALY --}}
    @if ($usuario->hasAnyRole(['SUPER ADMIN', 'ADMINISTRADOR']))
        @php
            $galyAccountLinked = $usuario->galy_user_id !== null;
            $isGalyAdmin = $galyAccountLinked && $usuario->galy_es_admin;
            $galyAdminPanelUrl = $isGalyAdmin
                ? route('admin.galy-panel')
                : route('web.login', ['galy' => 'connect']);
        @endphp
        <section class="ga-surface mt-3 mb-4" aria-labelledby="galy-admin-title">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-start gap-3">
                    <span class="ga-kpi-icon is-info flex-shrink-0"><i class="bi bi-phone" aria-hidden="true"></i></span>
                    <div>
                        <div class="ga-eyebrow mb-1">App GALY</div>
                        <h2 id="galy-admin-title" class="h5 mb-1">
                            {{ $isGalyAdmin ? 'Administrador compartido de GALY' : 'Conecta tu administrador con GALY' }}
                        </h2>
                        @if ($isGalyAdmin)
                            <p class="ga-muted mb-0 small">Gestiona productos, pedidos, inventario, banners y clientes compartidos con la app Android.</p>
                        @elseif ($galyAccountLinked)
                            <p class="ga-muted mb-0 small">La cuenta GALY está vinculada, pero no tiene permisos de administrador. Pide al titular que los habilite.</p>
                        @else
                            <p class="ga-muted mb-0 small">Inicia sesión aquí con tu cuenta GALY para vincularla. GALY debe confirmar que eres administrador.</p>
                        @endif
                    </div>
                </div>
                @if ($isGalyAdmin || ! $galyAccountLinked)
                    <a class="btn btn-primary btn-sm flex-shrink-0" href="{{ $galyAdminPanelUrl }}">
                        <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                        {{ $isGalyAdmin ? 'Abrir administrador GALY' : 'Conectar mi cuenta GALY' }}
                    </a>
                @endif
            </div>
        </section>
    @endif

    {{-- Analítica --}}
    <h2 class="ga-section-label">Analítica de la tienda</h2>

    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-7">
            <div class="ga-chart-card">
                <div class="ga-chart-head">
                    <div>
                        <h3>Usuarios registrados</h3>
                        <p>Altas por mes · {{ now()->year }}</p>
                    </div>
                    <span class="badge bg-primary">Últimos 12 meses</span>
                </div>
                <div class="ga-chart-body">
                    <div id="chart"></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-5">
            <div class="ga-chart-card">
                <div class="ga-chart-head">
                    <div>
                        <h3>Ventas por mes</h3>
                        <p>Monto total de pedidos · {{ now()->year }}</p>
                    </div>
                    <span class="badge bg-success">S/</span>
                </div>
                <div class="ga-chart-body">
                    <div id="chart2"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-4">
            <div class="ga-chart-card">
                <div class="ga-chart-head">
                    <div>
                        <h3>Estado de pedidos</h3>
                        <p>Distribución actual</p>
                    </div>
                </div>
                <div class="ga-chart-body">
                    <div id="chart3"></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-4">
            <div class="ga-chart-card">
                <div class="ga-chart-head">
                    <div>
                        <h3>Stock bajo</h3>
                        <p>Productos con 10 unidades o menos</p>
                    </div>
                </div>
                <div class="ga-chart-body">
                    <div id="chart4"></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-4">
            <div class="ga-chart-card">
                <div class="ga-chart-head">
                    <div>
                        <h3>Stock alto</h3>
                        <p>Productos con más de 50 unidades</p>
                    </div>
                </div>
                <div class="ga-chart-body">
                    <div id="chart5"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- App GALY --}}
    @can('Ver dashboard del Admin')
        <h2 class="ga-section-label">App GALY</h2>
        <div class="row g-2 mb-2">
            <div class="col-12 col-sm-6 col-xl-3">
                <a class="ga-quick" href="{{ route('admin.app-galy.index') }}">
                    <span class="ga-quick-icon"><i class="bi bi-phone" aria-hidden="true"></i></span>
                    <span><strong>Resumen app</strong><small>Ventas, stock y utilidad</small></span>
                    <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                </a>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <a class="ga-quick" href="{{ route('admin.app-galy.clientes') }}">
                    <span class="ga-quick-icon"><i class="bi bi-people" aria-hidden="true"></i></span>
                    <span><strong>Clientes app</strong><small>Cuentas, puntos y pedidos</small></span>
                    <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                </a>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <a class="ga-quick" href="{{ route('admin.app-galy.pedidos') }}">
                    <span class="ga-quick-icon"><i class="bi bi-receipt" aria-hidden="true"></i></span>
                    <span><strong>Pedidos de la app</strong><small>Estado, pago y total</small></span>
                    <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                </a>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <a class="ga-quick" href="{{ route('admin.app-galy.comentarios') }}">
                    <span class="ga-quick-icon is-warning"><i class="bi bi-chat-left-text" aria-hidden="true"></i></span>
                    <span><strong>Comentarios</strong><small>Reseñas de clientes</small></span>
                    <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                </a>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <a class="ga-quick" href="{{ route('admin.app-galy.notificaciones') }}">
                    <span class="ga-quick-icon is-purple"><i class="bi bi-bell" aria-hidden="true"></i></span>
                    <span><strong>Notificaciones</strong><small>Anuncios push a la app</small></span>
                    <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                </a>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <a class="ga-quick" href="{{ route('admin.app-galy.administradores') }}">
                    <span class="ga-quick-icon is-info"><i class="bi bi-shield-lock" aria-hidden="true"></i></span>
                    <span><strong>Administradores GALY</strong><small>Rangos y accesos</small></span>
                    <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                </a>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <a class="ga-quick" href="{{ route('admin.app-galy.dispositivos') }}">
                    <span class="ga-quick-icon is-success"><i class="bi bi-phone-vibrate" aria-hidden="true"></i></span>
                    <span><strong>Dispositivos push</strong><small>Teléfonos registrados</small></span>
                    <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                </a>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <a class="ga-quick" href="{{ route('admin.app-galy.index') }}#funciones-app">
                    <span class="ga-quick-icon"><i class="bi bi-grid-3x3-gap" aria-hidden="true"></i></span>
                    <span><strong>Todas las funciones</strong><small>Lo que ve el admin de la app</small></span>
                    <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    @endcan

    {{-- Accesos rápidos --}}
    @if ($usuario->canAny([
        'Crear Producto',
        'Listado de Productos',
        'Listado de Categorías',
        'Listado de Marcas',
        'Listado de Pedidos',
        'Listado de Ofertas',
        'Listado de Banners',
        'Ver Contacto',
        'Ver Soporte Técnico',
        'Ver Reservas',
        'Crear Oferta',
        'Crear Banner',
    ]))
        <h2 class="ga-section-label">Accesos rápidos</h2>
        <div class="row g-2 mb-2">
            @can('Crear Producto')
                <div class="col-12 col-sm-6 col-xl-3">
                    <a class="ga-quick" href="{{ route('admin.productos.create') }}">
                        <span class="ga-quick-icon is-success"><i class="bi bi-plus-lg" aria-hidden="true"></i></span>
                        <span><strong>Nuevo producto</strong><small>Agregar al catálogo</small></span>
                        <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                    </a>
                </div>
            @endcan
            @can('Listado de Productos')
                <div class="col-12 col-sm-6 col-xl-3">
                    <a class="ga-quick" href="{{ route('admin.productos.index') }}">
                        <span class="ga-quick-icon"><i class="bi bi-box-seam" aria-hidden="true"></i></span>
                        <span><strong>Administrar productos</strong><small>Catálogo web y GALY</small></span>
                        <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                    </a>
                </div>
            @endcan
            @can('Listado de Categorías')
                <div class="col-12 col-sm-6 col-xl-3">
                    <a class="ga-quick" href="{{ route('admin.categorias.index') }}">
                        <span class="ga-quick-icon is-purple"><i class="bi bi-tags" aria-hidden="true"></i></span>
                        <span><strong>Administrar categorías</strong><small>Catálogo web y GALY</small></span>
                        <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                    </a>
                </div>
            @endcan
            @can('Listado de Marcas')
                <div class="col-12 col-sm-6 col-xl-3">
                    <a class="ga-quick" href="{{ route('admin.marcas.index') }}">
                        <span class="ga-quick-icon is-info"><i class="bi bi-award" aria-hidden="true"></i></span>
                        <span><strong>Administrar marcas</strong><small>Catálogo web y GALY</small></span>
                        <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                    </a>
                </div>
            @endcan
            @can('Listado de Ofertas')
                <div class="col-12 col-sm-6 col-xl-3">
                    <a class="ga-quick" href="{{ route('admin.ofertas.index') }}">
                        <span class="ga-quick-icon is-warning"><i class="bi bi-percent" aria-hidden="true"></i></span>
                        <span><strong>Administrar ofertas</strong><small>Promociones compartidas</small></span>
                        <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                    </a>
                </div>
            @endcan
            @can('Listado de Banners')
                <div class="col-12 col-sm-6 col-xl-3">
                    <a class="ga-quick" href="{{ route('admin.banners.index') }}">
                        <span class="ga-quick-icon"><i class="bi bi-images" aria-hidden="true"></i></span>
                        <span><strong>Administrar banners</strong><small>Portada web y GALY</small></span>
                        <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                    </a>
                </div>
            @endcan
            @can('Ver Contacto')
                <div class="col-12 col-sm-6 col-xl-3">
                    <a class="ga-quick" href="{{ url('/admin/contacto') }}">
                        <span class="ga-quick-icon is-info"><i class="bi bi-telephone" aria-hidden="true"></i></span>
                        <span><strong>Administrar contacto</strong><small>Datos públicos web y GALY</small></span>
                        <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                    </a>
                </div>
            @endcan
            @can('Ver Soporte Técnico')
                <div class="col-12 col-sm-6 col-xl-3">
                    <a class="ga-quick" href="{{ route('admin.soporte.index') }}">
                        <span class="ga-quick-icon is-danger"><i class="bi bi-tools" aria-hidden="true"></i></span>
                        <span><strong>Soporte técnico</strong><small>Tickets compartidos con GALY</small></span>
                        <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                    </a>
                </div>
            @endcan
            @can('Ver Reservas')
                <div class="col-12 col-sm-6 col-xl-3">
                    <a class="ga-quick" href="{{ route('admin.reservas.index') }}">
                        <span class="ga-quick-icon is-success"><i class="bi bi-bookmark-check" aria-hidden="true"></i></span>
                        <span><strong>Reservas de productos</strong><small>Apartados de GALY y la web</small></span>
                        <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                    </a>
                </div>
            @endcan
            @can('Listado de Pedidos')
                <div class="col-12 col-sm-6 col-xl-3">
                    <a class="ga-quick" href="{{ route('admin.pedidos.index') }}">
                        <span class="ga-quick-icon is-warning"><i class="bi bi-receipt" aria-hidden="true"></i></span>
                        <span><strong>Revisar pedidos</strong><small>Consultar y procesar</small></span>
                        <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                    </a>
                </div>
            @endcan
            @can('Crear Oferta')
                <div class="col-12 col-sm-6 col-xl-3">
                    <a class="ga-quick" href="{{ route('admin.ofertas.create') }}">
                        <span class="ga-quick-icon is-warning"><i class="bi bi-plus-slash-minus" aria-hidden="true"></i></span>
                        <span><strong>Nueva oferta</strong><small>Crear una promoción</small></span>
                        <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                    </a>
                </div>
            @endcan
            @can('Crear Banner')
                <div class="col-12 col-sm-6 col-xl-3">
                    <a class="ga-quick" href="{{ route('admin.banners.create') }}">
                        <span class="ga-quick-icon"><i class="bi bi-image" aria-hidden="true"></i></span>
                        <span><strong>Nuevo banner</strong><small>Actualizar la portada</small></span>
                        <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                    </a>
                </div>
            @endcan
        </div>
    @endif

    {{-- Catálogo y marketing --}}
    <h2 class="ga-section-label">Catálogo y marketing</h2>
    <div class="row g-3 mb-3">
        @can('Listado de Marcas')
            <div class="col-6 col-xl-3">
                <a class="ga-kpi" href="{{ url('/admin/marcas') }}">
                    <div class="ga-kpi-top">
                        <div>
                            <p class="ga-kpi-label">Marcas</p>
                            <p class="ga-kpi-value">{{ number_format($total_marcas) }}</p>
                        </div>
                        <span class="ga-kpi-icon is-info"><i class="bi bi-award-fill" aria-hidden="true"></i></span>
                    </div>
                    <span class="ga-kpi-foot"><i class="bi bi-arrow-up-right" aria-hidden="true"></i> Ver marcas</span>
                </a>
            </div>
        @endcan
        @can('Listado de Ofertas')
            <div class="col-6 col-xl-3">
                <a class="ga-kpi" href="{{ url('/admin/ofertas') }}">
                    <div class="ga-kpi-top">
                        <div>
                            <p class="ga-kpi-label">Ofertas activas</p>
                            <p class="ga-kpi-value">{{ number_format($total_ofertas_activas) }}</p>
                        </div>
                        <span class="ga-kpi-icon is-danger"><i class="bi bi-percent" aria-hidden="true"></i></span>
                    </div>
                    <span class="ga-kpi-foot"><i class="bi bi-arrow-up-right" aria-hidden="true"></i> Ver campañas</span>
                </a>
            </div>
        @endcan
        @can('Listado de Banners')
            <div class="col-6 col-xl-3">
                <a class="ga-kpi" href="{{ url('/admin/banners') }}">
                    <div class="ga-kpi-top">
                        <div>
                            <p class="ga-kpi-label">Banners</p>
                            <p class="ga-kpi-value">{{ number_format($total_banners) }}</p>
                        </div>
                        <span class="ga-kpi-icon is-warning"><i class="bi bi-image-fill" aria-hidden="true"></i></span>
                    </div>
                    <span class="ga-kpi-foot"><i class="bi bi-arrow-up-right" aria-hidden="true"></i> Ver publicados</span>
                </a>
            </div>
        @endcan
        @can('Listado de Carruseles')
            <div class="col-6 col-xl-3">
                <a class="ga-kpi" href="{{ url('/admin/carruseles') }}">
                    <div class="ga-kpi-top">
                        <div>
                            <p class="ga-kpi-label">Carruseles</p>
                            <p class="ga-kpi-value">{{ number_format($total_carruseles) }}</p>
                        </div>
                        <span class="ga-kpi-icon"><i class="bi bi-images" aria-hidden="true"></i></span>
                    </div>
                    <span class="ga-kpi-foot"><i class="bi bi-arrow-up-right" aria-hidden="true"></i> Sliders del home</span>
                </a>
            </div>
        @endcan
        <div class="col-6 col-xl-3">
            <a class="ga-kpi" href="{{ url('/admin/productos') }}">
                <div class="ga-kpi-top">
                    <div>
                        <p class="ga-kpi-label">Destacados</p>
                        <p class="ga-kpi-value">{{ number_format($total_productos_destacados) }}</p>
                    </div>
                    <span class="ga-kpi-icon is-warning"><i class="bi bi-star-fill" aria-hidden="true"></i></span>
                </div>
                <span class="ga-kpi-foot"><i class="bi bi-arrow-up-right" aria-hidden="true"></i> Productos</span>
            </a>
        </div>
        <div class="col-6 col-xl-3">
            <a class="ga-kpi" href="{{ url('/admin/productos') }}">
                <div class="ga-kpi-top">
                    <div>
                        <p class="ga-kpi-label">Nuevos</p>
                        <p class="ga-kpi-value">{{ number_format($total_productos_nuevos) }}</p>
                    </div>
                    <span class="ga-kpi-icon is-purple"><i class="bi bi-clock-history" aria-hidden="true"></i></span>
                </div>
                <span class="ga-kpi-foot"><i class="bi bi-arrow-up-right" aria-hidden="true"></i> Productos</span>
            </a>
        </div>
        <div class="col-6 col-xl-3">
            <a class="ga-kpi" href="{{ url('/admin/productos') }}">
                <div class="ga-kpi-top">
                    <div>
                        <p class="ga-kpi-label">En oferta</p>
                        <p class="ga-kpi-value">{{ number_format($total_productos_en_oferta) }}</p>
                    </div>
                    <span class="ga-kpi-icon is-success"><i class="bi bi-tags-fill" aria-hidden="true"></i></span>
                </div>
                <span class="ga-kpi-foot"><i class="bi bi-arrow-up-right" aria-hidden="true"></i> Productos</span>
            </a>
        </div>
        <div class="col-6 col-xl-3">
            <a class="ga-kpi" href="{{ url('/admin/productos') }}">
                <div class="ga-kpi-top">
                    <div>
                        <p class="ga-kpi-label">Más vendidos</p>
                        <p class="ga-kpi-value">{{ number_format($total_productos_mas_vendidos) }}</p>
                    </div>
                    <span class="ga-kpi-icon is-danger"><i class="bi bi-graph-up-arrow" aria-hidden="true"></i></span>
                </div>
                <span class="ga-kpi-foot"><i class="bi bi-arrow-up-right" aria-hidden="true"></i> Clasificación</span>
            </a>
        </div>
    </div>

    {{-- Operación y equipo --}}
    <h2 class="ga-section-label">Operación y equipo</h2>
    <div class="row g-3">
        @can('Listado de Pedidos')
            <div class="col-12 col-lg-6">
                <div class="ga-surface h-100">
                    <div class="ga-card-head">
                        <div>
                            <h3>Pedidos</h3>
                            <p>Resumen de todos los estados</p>
                        </div>
                        <a class="btn-ga-ghost" href="{{ url('/admin/pedidos') }}">Ver todos</a>
                    </div>
                    <div class="card-body">
                        <ul class="ga-list-reset d-grid gap-2">
                            <li class="d-flex align-items-center justify-content-between gap-3">
                                <span class="d-flex align-items-center gap-2">
                                    <span class="ga-kpi-icon is-warning" style="width:34px;height:34px;flex-basis:34px;font-size:.9rem;"><i class="bi bi-inbox" aria-hidden="true"></i></span>
                                    <span><strong class="d-block small">Nuevos</strong><small class="ga-muted">Pendientes de atender</small></span>
                                </span>
                                <strong class="fs-5">{{ number_format($total_pedidos_nuevos) }}</strong>
                            </li>
                            <li class="d-flex align-items-center justify-content-between gap-3">
                                <span class="d-flex align-items-center gap-2">
                                    <span class="ga-kpi-icon is-info" style="width:34px;height:34px;flex-basis:34px;font-size:.9rem;"><i class="bi bi-send-check" aria-hidden="true"></i></span>
                                    <span><strong class="d-block small">Enviados</strong><small class="ga-muted">En camino al cliente</small></span>
                                </span>
                                <strong class="fs-5">{{ number_format($total_pedidos_enviados) }}</strong>
                            </li>
                            <li class="d-flex align-items-center justify-content-between gap-3">
                                <span class="d-flex align-items-center gap-2">
                                    <span class="ga-kpi-icon" style="width:34px;height:34px;flex-basis:34px;font-size:.9rem;"><i class="bi bi-list-check" aria-hidden="true"></i></span>
                                    <span><strong class="d-block small">Total histórico</strong><small class="ga-muted">Todos los estados</small></span>
                                </span>
                                <strong class="fs-5">{{ number_format($total_pedidos) }}</strong>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        @endcan

        <div class="col-12 col-lg-6">
            <div class="ga-surface h-100">
                <div class="ga-card-head">
                    <div>
                        <h3>Equipo y accesos</h3>
                        <p>Usuarios y roles del panel</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @can('Listado de Roles')
                            <div class="col-6">
                                <a class="ga-quick h-100" href="{{ url('/admin/roles') }}">
                                    <span class="ga-quick-icon is-purple"><i class="bi bi-shield-check" aria-hidden="true"></i></span>
                                    <span><strong>{{ number_format($total_roles) }}</strong><small>Roles registrados</small></span>
                                </a>
                            </div>
                        @endcan
                        @can('Listado de Usuarios')
                            <div class="col-6">
                                <a class="ga-quick h-100" href="{{ url('/admin/usuarios') }}">
                                    <span class="ga-quick-icon is-info"><i class="bi bi-person-badge" aria-hidden="true"></i></span>
                                    <span><strong>{{ number_format($total_usuarios) }}</strong><small>Usuarios registrados</small></span>
                                </a>
                            </div>
                        @endcan
                    </div>
                    <div class="ga-note mt-3">
                        <i class="bi bi-info-circle" aria-hidden="true"></i>
                        <span>Los permisos de cada rol se administran desde <strong>Roles</strong>. Si un usuario no ve una sección, revisa su rol.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const gaDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
            const gaText = gaDark ? '#94a3b8' : '#64748b';
            const gaGrid = gaDark ? '#1e2c47' : '#e7ecf4';
            const gaTooltip = gaDark ? 'dark' : 'light';
            const gaBrand = '#3b5bdb';
            const gaBrandSoft = '#7c9bff';
            const gaWarning = '#f59e0b';
            const gaSuccess = '#0f9d58';
            const gaDanger = '#e11d48';
            const gaSlate = gaDark ? '#475569' : '#cbd5e1';
            const nf = new Intl.NumberFormat('es-PE');

            const baseChart = {
                fontFamily: 'Inter, system-ui, sans-serif',
                toolbar: { show: false },
                zoom: { enabled: false },
                animations: { enabled: true, easing: 'easeinout', speed: 700 }
            };

            const baseGrid = {
                borderColor: gaGrid,
                strokeDashArray: 4,
                padding: { left: 8, right: 8, top: 0, bottom: 0 }
            };

            const baseAxisLabels = {
                style: { colors: gaText, fontSize: '11px', fontFamily: 'Inter, sans-serif' }
            };

            // Usuarios registrados por mes
            const usuariosData = @json(array_values($usuarios_data));

            new ApexCharts(document.querySelector('#chart'), {
                chart: Object.assign({}, baseChart, { type: 'area', height: 320 }),
                colors: [gaBrand],
                series: [{ name: 'Usuarios registrados', data: usuariosData }],
                stroke: { curve: 'smooth', width: 3 },
                fill: {
                    type: 'gradient',
                    gradient: { shadeIntensity: 1, opacityFrom: 0.3, opacityTo: 0.02, stops: [0, 90, 100] }
                },
                markers: { size: 0, strokeWidth: 2, hover: { size: 5 } },
                dataLabels: { enabled: false },
                grid: baseGrid,
                xaxis: {
                    categories: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                    labels: baseAxisLabels,
                    axisBorder: { show: false },
                    axisTicks: { show: false }
                },
                yaxis: {
                    labels: Object.assign({}, baseAxisLabels, {
                        formatter: function (value) { return Math.round(value); }
                    })
                },
                tooltip: {
                    theme: gaTooltip,
                    y: { formatter: function (value) { return nf.format(value) + ' usuarios'; } }
                },
                legend: { show: false }
            }).render();

            // Ventas por mes (monto)
            const ordenesdata = @json(array_values($ordenesdata));

            new ApexCharts(document.querySelector('#chart2'), {
                chart: Object.assign({}, baseChart, { type: 'bar', height: 320 }),
                colors: [gaBrand],
                series: [{ name: 'Ventas', data: ordenesdata }],
                plotOptions: {
                    bar: { borderRadius: 6, columnWidth: '52%', distributed: false }
                },
                dataLabels: { enabled: false },
                fill: {
                    type: 'gradient',
                    gradient: { shade: 'light', type: 'vertical', gradientToColors: [gaBrandSoft], opacityFrom: 1, opacityTo: 1, stops: [0, 100] }
                },
                grid: baseGrid,
                xaxis: {
                    categories: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                    labels: baseAxisLabels,
                    axisBorder: { show: false },
                    axisTicks: { show: false }
                },
                yaxis: {
                    labels: Object.assign({}, baseAxisLabels, {
                        formatter: function (value) {
                            return value >= 1000 ? 'S/ ' + (value / 1000).toFixed(1) + 'k' : 'S/ ' + Math.round(value);
                        }
                    })
                },
                tooltip: {
                    theme: gaTooltip,
                    y: { formatter: function (value) { return 'S/ ' + nf.format(Number(value).toFixed(2)); } }
                },
                legend: { show: false }
            }).render();

            const donutBase = {
                chart: Object.assign({}, baseChart, { type: 'donut', height: 300 }),
                stroke: { width: 0 },
                dataLabels: { enabled: false },
                legend: { position: 'bottom', fontSize: '12px', labels: { colors: gaText } },
                tooltip: { theme: gaTooltip }
            };

            // Estado de pedidos
            new ApexCharts(document.querySelector('#chart3'), Object.assign({}, donutBase, {
                series: [{{ (int) $total_pedidos_nuevos }}, {{ (int) $total_pedidos_enviados }}],
                labels: ['Nuevos', 'Enviados'],
                colors: [gaWarning, gaBrand],
                plotOptions: {
                    pie: {
                        donut: {
                            size: '70%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total',
                                    color: gaText,
                                    fontSize: '12px',
                                    formatter: function (w) {
                                        return w.globals.seriesTotals.reduce(function (a, b) { return a + b; }, 0);
                                    }
                                },
                                value: { fontSize: '24px', fontWeight: 700, color: gaDark ? '#f1f5f9' : '#0f172a' }
                            }
                        }
                    }
                },
                tooltip: {
                    theme: gaTooltip,
                    y: { formatter: function (value) { return value + ' pedidos'; } }
                }
            })).render();

            // Stock bajo
            const porcentajeStockBajo = {{ (float) $porcentajeStockBajo }};
            const porcentajeStockNormal = 100 - porcentajeStockBajo;

            new ApexCharts(document.querySelector('#chart4'), Object.assign({}, donutBase, {
                series: [porcentajeStockBajo, porcentajeStockNormal],
                labels: ['Stock bajo', 'Stock correcto'],
                colors: [gaDanger, gaSuccess],
                plotOptions: {
                    pie: {
                        donut: {
                            size: '70%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Stock bajo',
                                    color: gaText,
                                    fontSize: '12px',
                                    formatter: function () { return porcentajeStockBajo.toFixed(1) + '%'; }
                                },
                                value: { fontSize: '24px', fontWeight: 700, color: gaDark ? '#f1f5f9' : '#0f172a' }
                            }
                        }
                    }
                },
                tooltip: {
                    theme: gaTooltip,
                    y: { formatter: function (value) { return Number(value).toFixed(1) + '%'; } }
                }
            })).render();

            // Stock alto
            const porcentajeStockAlto = {{ (float) $porcentajeStockAlto }};
            const porcentajeRestante = 100 - porcentajeStockAlto;

            new ApexCharts(document.querySelector('#chart5'), Object.assign({}, donutBase, {
                series: [porcentajeStockAlto, porcentajeRestante],
                labels: ['Stock alto', 'Resto del inventario'],
                colors: [gaBrand, gaSlate],
                plotOptions: {
                    pie: {
                        donut: {
                            size: '70%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Stock alto',
                                    color: gaText,
                                    fontSize: '12px',
                                    formatter: function () { return porcentajeStockAlto.toFixed(1) + '%'; }
                                },
                                value: { fontSize: '24px', fontWeight: 700, color: gaDark ? '#f1f5f9' : '#0f172a' }
                            }
                        }
                    }
                },
                tooltip: {
                    theme: gaTooltip,
                    y: { formatter: function (value) { return Number(value).toFixed(1) + '%'; } }
                }
            })).render();
        })();
    </script>
@endsection
