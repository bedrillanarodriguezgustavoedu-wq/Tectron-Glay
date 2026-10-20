@extends('layouts.admin')

@section('content')
    <style>
        .dashboard-page {
            --dashboard-ink: var(--bs-heading-color, #17233c);
            --dashboard-muted: var(--bs-secondary-color, #73809a);
            --dashboard-border: var(--bs-border-color, #e8edf5);
            color: var(--dashboard-ink);
        }

        .dashboard-hero {
            position: relative;
            overflow: hidden;
            isolation: isolate;
            padding: clamp(1.5rem, 3vw, 2.5rem);
            border-radius: 1.25rem;
            color: #fff;
            background: linear-gradient(120deg, #172554 0%, #2749a5 58%, #5378e8 100%);
            box-shadow: 0 18px 42px rgba(36, 67, 145, .2);
        }

        .dashboard-hero::after {
            position: absolute;
            z-index: -1;
            top: -100px;
            right: -45px;
            width: 290px;
            height: 290px;
            border: 1px solid rgba(255, 255, 255, .16);
            border-radius: 50%;
            box-shadow: 0 0 0 34px rgba(255, 255, 255, .045), 0 0 0 72px rgba(255, 255, 255, .035);
            content: "";
        }

        .dashboard-hero .dashboard-eyebrow {
            color: rgba(255, 255, 255, .72);
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .13em;
            text-transform: uppercase;
        }

        .dashboard-hero h1 {
            font-size: clamp(1.55rem, 3vw, 2.25rem);
            font-weight: 750;
            letter-spacing: -.04em;
        }

        .dashboard-role {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .55rem .8rem;
            border: 1px solid rgba(255, 255, 255, .22);
            border-radius: 999px;
            color: #fff;
            background: rgba(255, 255, 255, .1);
            font-size: .8rem;
            font-weight: 600;
        }

        .dashboard-section-label {
            margin: 1.65rem 0 .85rem;
            color: var(--dashboard-muted);
            font-size: .72rem;
            font-weight: 750;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .dashboard-action {
            display: flex;
            min-height: 76px;
            align-items: center;
            gap: .85rem;
            padding: .9rem 1rem;
            border: 1px solid var(--dashboard-border);
            border-radius: .9rem;
            color: var(--dashboard-ink);
            background: var(--bs-card-bg, var(--bs-body-bg, #fff));
            text-decoration: none;
            transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
        }

        .dashboard-action:hover {
            border-color: rgba(67, 94, 190, .38);
            box-shadow: 0 10px 24px rgba(24, 42, 78, .08);
            color: var(--dashboard-ink);
            transform: translateY(-2px);
        }

        .dashboard-action:focus-visible {
            outline: 3px solid rgba(67, 94, 190, .35);
            outline-offset: 3px;
        }

        .dashboard-action-icon {
            display: grid;
            width: 42px;
            height: 42px;
            flex: 0 0 42px;
            place-items: center;
            border-radius: .75rem;
            color: #3652a5;
            background: rgba(67, 94, 190, .1);
            font-size: 1.1rem;
        }

        .dashboard-action small {
            display: block;
            margin-top: .15rem;
            color: var(--dashboard-muted);
        }

        .dashboard-action > .bi-arrow-up-right {
            margin-left: auto;
            color: var(--dashboard-muted);
        }

        .stat-card {
            position: relative;
            overflow: hidden;
            border: 1px solid var(--dashboard-border) !important;
            border-radius: 1rem;
            background: var(--bs-card-bg, var(--bs-body-bg, #fff));
            transition: box-shadow .2s ease, transform .2s ease, border-color .2s ease;
        }

        .stat-card:hover {
            border-color: rgba(67, 94, 190, .3) !important;
            box-shadow: 0 16px 32px rgba(24, 42, 78, .1) !important;
            transform: translateY(-4px);
        }

        .stat-card:focus-visible {
            outline: 3px solid rgba(67, 94, 190, .35);
            outline-offset: 3px;
        }

        .stat-icon-wrap {
            flex: 0 0 46px;
            width: 46px;
            height: 46px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: .85rem !important;
            background: rgba(67, 94, 190, .1);
        }

        .stat-card .card-body {
            min-height: 112px;
            padding: 1.1rem;
        }

        .stat-card h5 {
            margin-top: .25rem !important;
            color: var(--dashboard-ink);
            font-size: 1.55rem;
            font-weight: 750 !important;
            letter-spacing: -.04em;
        }

        .stat-card p {
            font-size: .78rem !important;
            font-weight: 600;
        }

        .dashboard-chart-card {
            height: 100%;
            border: 1px solid var(--dashboard-border);
            border-radius: 1rem;
            background: var(--bs-card-bg, var(--bs-body-bg, #fff));
            box-shadow: 0 8px 24px rgba(24, 42, 78, .045);
        }

        .dashboard-chart-card .card-body {
            min-width: 0;
            padding: clamp(.85rem, 2vw, 1.4rem);
        }

        .galy-admin-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            padding: clamp(1.25rem, 2.5vw, 1.75rem);
            border: 1px solid rgba(67, 94, 190, .16);
            border-radius: 1rem;
            background: linear-gradient(115deg, rgba(67, 94, 190, .09), rgba(83, 120, 232, .035));
        }

        .galy-admin-card h2 {
            margin: 0 0 .4rem;
            color: #17233c;
            font-size: 1.1rem;
            font-weight: 700;
        }

        .galy-admin-card p {
            margin: 0;
            color: var(--dashboard-muted);
        }

        .galy-admin-card .galy-admin-eyebrow {
            margin-bottom: .35rem;
            color: #3652a5;
            font-size: .7rem;
            font-weight: 750;
            letter-spacing: .12em;
        }

        html[data-bs-theme="dark"] .galy-admin-card {
            border-color: rgba(142, 165, 255, .24);
            background: linear-gradient(115deg, rgba(67, 94, 190, .2), rgba(83, 120, 232, .09));
        }

        html[data-bs-theme="dark"] .galy-admin-card .galy-admin-eyebrow {
            color: #a8bbff;
        }

        html[data-bs-theme="dark"] .galy-admin-card h2 {
            color: #f8fafc;
        }

        @media (max-width: 575.98px) {
            .dashboard-hero {
                border-radius: 1rem;
            }

            .galy-admin-card {
                align-items: stretch;
                flex-direction: column;
                gap: 1rem;
            }

            .stat-card .card-body {
                min-height: 105px;
                gap: .65rem !important;
                padding: .85rem;
            }

            .stat-icon-wrap {
                flex-basis: 38px;
                width: 38px;
                height: 38px;
            }

            .stat-card h5 {
                font-size: 1.3rem;
            }
        }
    </style>

    <main class="dashboard-page">
        <header class="dashboard-hero mb-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <div class="dashboard-eyebrow mb-2">Administración web · App GALY</div>
                    <h1 class="mb-2">¡Hola, {{ auth()->user()->name }}!</h1>
                    <p class="mb-0 text-white-50">Gestiona tu tienda y accede al administrador compartido de GALY.</p>
                </div>
                <div class="d-flex flex-column align-items-start align-items-sm-end gap-2">
                    <span class="dashboard-role"><i class="bi bi-shield-check"></i>{{ Auth::user()->roles->pluck('name')->implode(', ') }}</span>
                    <span class="small text-white-50">{{ now()->format('d/m/Y') }}</span>
                </div>
            </div>
        </header>

        @if (auth()->user()->hasAnyRole(['SUPER ADMIN', 'ADMINISTRADOR']))
            @php
                $galyAccountLinked = auth()->user()->galy_user_id !== null;
                $isGalyAdmin = $galyAccountLinked && auth()->user()->galy_es_admin;
                $hasGalyAdminToken = $isGalyAdmin && filled(session('galy_admin_token'));
                $galyAdminPanelUrl = $hasGalyAdminToken
                    ? route('admin.galy-panel')
                    : route('web.login', ['galy' => $isGalyAdmin ? 'renew' : 'connect']);
            @endphp
            <section class="galy-admin-card mb-4" aria-labelledby="galy-admin-title">
                <div>
                    <div class="galy-admin-eyebrow">APP GALY</div>
                    <h2 id="galy-admin-title">
                        {{ $isGalyAdmin ? 'Administrador compartido de GALY' : 'Conecta tu administrador con GALY' }}
                    </h2>
                    @if ($isGalyAdmin)
                        <p>Gestiona los productos, pedidos, inventario, banners y clientes compartidos con la app GALY.</p>
                    @elseif ($galyAccountLinked)
                        <p>La cuenta GALY está vinculada, pero no tiene permisos de administrador en GALY. Pide al titular que habilite esos permisos.</p>
                    @else
                        <p>Vuelve a iniciar sesión aquí con el correo y la contraseña de tu cuenta GALY. Así se vinculará por correo; GALY debe confirmar que es administradora.</p>
                    @endif
                </div>
                @if ($isGalyAdmin || ! $galyAccountLinked)
                    <a class="btn btn-primary d-inline-flex align-items-center justify-content-center gap-2 flex-shrink-0"
                        href="{{ $galyAdminPanelUrl }}">
                        <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                        {{ $hasGalyAdminToken ? 'Abrir administrador GALY' : ($isGalyAdmin ? 'Renovar sesión GALY' : 'Conectar mi cuenta GALY') }}
                    </a>
                @endif
            </section>
        @endif

        @if (auth()->user()->canAny([
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
            <section aria-labelledby="dashboard-actions-title">
                <h2 id="dashboard-actions-title" class="dashboard-section-label">Accesos rápidos · funciones compartidas</h2>
                <div class="row g-2">
                    @can('Crear Producto')
                        <div class="col-12 col-sm-6 col-xl-3">
                            <a class="dashboard-action" href="{{ route('admin.productos.create') }}">
                                <span class="dashboard-action-icon"><i class="bi bi-plus-lg" aria-hidden="true"></i></span>
                                <span><strong>Nuevo producto</strong><small>Agregar al catálogo</small></span>
                                <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                            </a>
                        </div>
                    @endcan
                    @can('Listado de Productos')
                        <div class="col-12 col-sm-6 col-xl-3">
                            <a class="dashboard-action" href="{{ route('admin.productos.index') }}">
                                <span class="dashboard-action-icon"><i class="bi bi-box-seam" aria-hidden="true"></i></span>
                                <span><strong>Administrar productos</strong><small>Catálogo web y GALY</small></span>
                                <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                            </a>
                        </div>
                    @endcan
                    @can('Listado de Categorías')
                        <div class="col-12 col-sm-6 col-xl-3">
                            <a class="dashboard-action" href="{{ route('admin.categorias.index') }}">
                                <span class="dashboard-action-icon"><i class="bi bi-tags" aria-hidden="true"></i></span>
                                <span><strong>Administrar categorías</strong><small>Catálogo web y GALY</small></span>
                                <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                            </a>
                        </div>
                    @endcan
                    @can('Listado de Marcas')
                        <div class="col-12 col-sm-6 col-xl-3">
                            <a class="dashboard-action" href="{{ route('admin.marcas.index') }}">
                                <span class="dashboard-action-icon"><i class="bi bi-award" aria-hidden="true"></i></span>
                                <span><strong>Administrar marcas</strong><small>Catálogo web y GALY</small></span>
                                <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                            </a>
                        </div>
                    @endcan
                    @can('Listado de Ofertas')
                        <div class="col-12 col-sm-6 col-xl-3">
                            <a class="dashboard-action" href="{{ route('admin.ofertas.index') }}">
                                <span class="dashboard-action-icon"><i class="bi bi-percent" aria-hidden="true"></i></span>
                                <span><strong>Administrar ofertas</strong><small>Promociones compartidas</small></span>
                                <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                            </a>
                        </div>
                    @endcan
                    @can('Listado de Banners')
                        <div class="col-12 col-sm-6 col-xl-3">
                            <a class="dashboard-action" href="{{ route('admin.banners.index') }}">
                                <span class="dashboard-action-icon"><i class="bi bi-images" aria-hidden="true"></i></span>
                                <span><strong>Administrar banners</strong><small>Portada web y GALY</small></span>
                                <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                            </a>
                        </div>
                    @endcan
                    @can('Ver Contacto')
                        <div class="col-12 col-sm-6 col-xl-3">
                            <a class="dashboard-action" href="{{ url('/admin/contacto') }}">
                                <span class="dashboard-action-icon"><i class="bi bi-telephone" aria-hidden="true"></i></span>
                                <span><strong>Administrar contacto</strong><small>Datos públicos web y GALY</small></span>
                                <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                            </a>
                        </div>
                    @endcan
                    @can('Ver Soporte Técnico')
                        <div class="col-12 col-sm-6 col-xl-3">
                            <a class="dashboard-action" href="{{ route('admin.soporte.index') }}">
                                <span class="dashboard-action-icon"><i class="bi bi-tools" aria-hidden="true"></i></span>
                                <span><strong>Soporte técnico</strong><small>Tickets compartidos con GALY</small></span>
                                <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                            </a>
                        </div>
                    @endcan
                    @can('Ver Reservas')
                        <div class="col-12 col-sm-6 col-xl-3">
                            <a class="dashboard-action" href="{{ route('admin.reservas.index') }}">
                                <span class="dashboard-action-icon"><i class="bi bi-bookmark-check" aria-hidden="true"></i></span>
                                <span><strong>Reservas de productos</strong><small>Apartados de GALY y la web</small></span>
                                <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                            </a>
                        </div>
                    @endcan
                    @can('Listado de Pedidos')
                        <div class="col-12 col-sm-6 col-xl-3">
                            <a class="dashboard-action" href="{{ route('admin.pedidos.index') }}">
                                <span class="dashboard-action-icon"><i class="bi bi-box-seam" aria-hidden="true"></i></span>
                                <span><strong>Revisar pedidos</strong><small>Consultar y procesar</small></span>
                                <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                            </a>
                        </div>
                    @endcan
                    @can('Crear Oferta')
                        <div class="col-12 col-sm-6 col-xl-3">
                            <a class="dashboard-action" href="{{ route('admin.ofertas.create') }}">
                                <span class="dashboard-action-icon"><i class="bi bi-percent" aria-hidden="true"></i></span>
                                <span><strong>Nueva oferta</strong><small>Crear una promoción</small></span>
                                <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                            </a>
                        </div>
                    @endcan
                    @can('Crear Banner')
                        <div class="col-12 col-sm-6 col-xl-3">
                            <a class="dashboard-action" href="{{ route('admin.banners.create') }}">
                                <span class="dashboard-action-icon"><i class="bi bi-image" aria-hidden="true"></i></span>
                                <span><strong>Nuevo banner</strong><small>Actualizar la portada</small></span>
                                <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                            </a>
                        </div>
                    @endcan
                </div>
            </section>
        @endif

    {{-- Sección: sistema --}}
    <p class="dashboard-section-label">
        Sistema
    </p>

    <div class="row g-3 mb-2">

        @can('Listado de Roles')
            <div class="col-6 col-md-3">
                <a href="{{ url('/admin/roles') }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm stat-card">
                        <div class="card-body d-flex gap-3 align-items-start">
                            <div class="stat-icon-wrap  rounded-2 p-2">
                                <i class="bi bi-shield-check fs-5 text-primary"></i>
                            </div>
                            <div>
                                <p class="text-muted mb-1" style="font-size:12px">Roles registrados</p>
                                <h5 class="mb-0 fw-medium">{{ $total_roles }}</h5>
                                <small class="text-muted">roles</small>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        @endcan

        @can('Listado de Usuarios')
            <div class="col-6 col-md-3">
                <a href="{{ url('/admin/usuarios') }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm stat-card">
                        <div class="card-body d-flex gap-3 align-items-start">
                            <div class="stat-icon-wrap  bg-opacity-10 rounded-2 p-2">
                                <i class="bi bi-person-add fs-5 text-purple"></i>
                            </div>
                            <div>
                                <p class="text-muted mb-1" style="font-size:12px">Usuarios registrados</p>
                                <h5 class="mb-0 fw-medium">{{ $total_usuarios }}</h5>
                                <small class="text-muted">usuarios</small>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        @endcan

        @can('Listado de Categorías')
            <div class="col-6 col-md-3">
                <a href="{{ url('/admin/categorias') }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm stat-card">
                        <div class="card-body d-flex gap-3 align-items-start">
                            <div class="stat-icon-wrap  rounded-2 p-2">
                                <i class="bi bi-tags fs-5 text-success"></i>
                            </div>
                            <div>
                                <p class="text-muted mb-1" style="font-size:12px">Categorías</p>
                                <h5 class="mb-0 fw-medium">{{ $total_categorias }}</h5>
                                <small class="text-muted">categorías</small>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        @endcan

        @can('Listado de Productos')
            <div class="col-6 col-md-3">
                <a href="{{ url('/admin/productos') }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm stat-card">
                        <div class="card-body d-flex gap-3 align-items-start">
                            <div class="stat-icon-wrap  rounded-2 p-2">
                                <i class="bi bi-box-seam-fill fs-5 text-danger"></i>
                            </div>
                            <div>
                                <p class="text-muted mb-1" style="font-size:12px">Productos registrados</p>
                                <h5 class="mb-0 fw-medium">{{ $total_productos }}</h5>
                                <small class="text-muted">productos</small>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        @endcan

    </div>

    <hr class="my-4 opacity-25">

    {{-- Sección: pedidos --}}
    @can('Listado de Pedidos')
        <p class="dashboard-section-label">
            Pedidos
        </p>

        <div class="row g-3">

            <div class="col-6 col-md-4">
                <a href="{{ url('/admin/pedidos') }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm stat-card">
                        <div class="card-body d-flex gap-3 align-items-start">
                            <div class="stat-icon-wrap  rounded-2 p-2">
                                <i class="bi bi-arrow-down-square-fill fs-5 text-primary"></i>
                            </div>
                            <div>
                                <p class="text-muted mb-1" style="font-size:12px">Pedidos nuevos</p>
                                <h5 class="mb-0 fw-medium">{{ $total_pedidos_nuevos }}</h5>
                                <small class="text-muted">pendientes de atender</small>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-4">
                <a href="{{ url('/admin/pedidos') }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm stat-card">
                        <div class="card-body d-flex gap-3 align-items-start">
                            <div class="stat-icon-wrap  rounded-2 p-2">
                                <i class="bi bi-send-check fs-5 text-purple"></i>
                            </div>
                            <div>
                                <p class="text-muted mb-1" style="font-size:12px">Pedidos enviados</p>
                                <h5 class="mb-0 fw-medium">{{ $total_pedidos_enviados }}</h5>
                                <small class="text-muted">en camino</small>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-4">
                <a href="{{ url('/admin/pedidos') }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm stat-card">
                        <div class="card-body d-flex gap-3 align-items-start">
                            <div class="stat-icon-wrap  rounded-2 p-2">
                                <i class="bi bi-list-check fs-5 text-warning"></i>
                            </div>
                            <div>
                                <p class="text-muted mb-1" style="font-size:12px">Total de pedidos</p>
                                <h5 class="mb-0 fw-medium">{{ $total_pedidos }}</h5>
                                <small class="text-muted">todos los estados</small>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

        </div>
    @endcan

    <hr class="my-4 opacity-25">

    {{-- Sección: catálogo, contenido y marketing --}}
    <p class="dashboard-section-label">
        Catálogo, Contenido y Marketing
    </p>

    <div class="row g-3 mb-4">

        @can('Listado de Marcas')
            <div class="col-6 col-md-3">
                <a href="{{ url('/admin/marcas') }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm stat-card">
                        <div class="card-body d-flex gap-3 align-items-start">
                            <div class="stat-icon-wrap bg-opacity-10 rounded-2 p-2" style="background-color: rgba(13, 202, 240, 0.1)">
                                <i class="bi bi-award fs-5 text-info"></i>
                            </div>
                            <div>
                                <p class="text-muted mb-1" style="font-size:12px">Marcas registradas</p>
                                <h5 class="mb-0 fw-medium">{{ $total_marcas }}</h5>
                                <small class="text-muted">marcas</small>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        @endcan

        @can('Listado de Ofertas')
            <div class="col-6 col-md-3">
                <a href="{{ url('/admin/ofertas') }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm stat-card">
                        <div class="card-body d-flex gap-3 align-items-start">
                            <div class="stat-icon-wrap bg-opacity-10 rounded-2 p-2" style="background-color: rgba(220, 53, 69, 0.1)">
                                <i class="bi bi-percent fs-5 text-danger"></i>
                            </div>
                            <div>
                                <p class="text-muted mb-1" style="font-size:12px">Ofertas activas</p>
                                <h5 class="mb-0 fw-medium">{{ $total_ofertas_activas }}</h5>
                                <small class="text-muted">campañas activas</small>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        @endcan

        @can('Listado de Banners')
            <div class="col-6 col-md-3">
                <a href="{{ url('/admin/banners') }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm stat-card">
                        <div class="card-body d-flex gap-3 align-items-start">
                            <div class="stat-icon-wrap bg-opacity-10 rounded-2 p-2" style="background-color: rgba(255, 193, 7, 0.1)">
                                <i class="bi bi-image fs-5 text-warning"></i>
                            </div>
                            <div>
                                <p class="text-muted mb-1" style="font-size:12px">Banners promocionales</p>
                                <h5 class="mb-0 fw-medium">{{ $total_banners }}</h5>
                                <small class="text-muted">publicados</small>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        @endcan

        @can('Listado de Carruseles')
            <div class="col-6 col-md-3">
                <a href="{{ url('/admin/carruseles') }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm stat-card">
                        <div class="card-body d-flex gap-3 align-items-start">
                            <div class="stat-icon-wrap bg-opacity-10 rounded-2 p-2" style="background-color: rgba(13, 110, 253, 0.1)">
                                <i class="bi bi-images fs-5 text-primary"></i>
                            </div>
                            <div>
                                <p class="text-muted mb-1" style="font-size:12px">Sliders del Carrusel</p>
                                <h5 class="mb-0 fw-medium">{{ $total_carruseles }}</h5>
                                <small class="text-muted">en el Home</small>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        @endcan

        <div class="col-6 col-md-3">
            <a href="{{ url('/admin/productos') }}" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm stat-card">
                    <div class="card-body d-flex gap-3 align-items-start">
                        <div class="stat-icon-wrap bg-opacity-10 rounded-2 p-2" style="background-color: rgba(255, 193, 7, 0.1)">
                            <i class="bi bi-star-fill fs-5 text-warning"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1" style="font-size:12px">Destacados</p>
                            <h5 class="mb-0 fw-medium">{{ $total_productos_destacados }}</h5>
                            <small class="text-muted">productos</small>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-6 col-md-3">
            <a href="{{ url('/admin/productos') }}" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm stat-card">
                    <div class="card-body d-flex gap-3 align-items-start">
                        <div class="stat-icon-wrap bg-opacity-10 rounded-2 p-2" style="background-color: rgba(111, 66, 193, 0.1)">
                            <i class="bi bi-clock-history fs-5 text-purple" style="color: #6f42c1 !important"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1" style="font-size:12px">Nuevos</p>
                            <h5 class="mb-0 fw-medium">{{ $total_productos_nuevos }}</h5>
                            <small class="text-muted">productos</small>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-6 col-md-3">
            <a href="{{ url('/admin/productos') }}" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm stat-card">
                    <div class="card-body d-flex gap-3 align-items-start">
                        <div class="stat-icon-wrap bg-opacity-10 rounded-2 p-2" style="background-color: rgba(25, 135, 84, 0.1)">
                            <i class="bi bi-tags-fill fs-5 text-success"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1" style="font-size:12px">En Oferta</p>
                            <h5 class="mb-0 fw-medium">{{ $total_productos_en_oferta }}</h5>
                            <small class="text-muted">productos</small>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-6 col-md-3">
            <a href="{{ url('/admin/productos') }}" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm stat-card">
                    <div class="card-body d-flex gap-3 align-items-start">
                        <div class="stat-icon-wrap bg-opacity-10 rounded-2 p-2" style="background-color: rgba(220, 53, 69, 0.1)">
                            <i class="bi bi-graph-up-arrow fs-5 text-danger"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1" style="font-size:12px">Más Vendidos</p>
                            <h5 class="mb-0 fw-medium">{{ $total_productos_mas_vendidos }}</h5>
                            <small class="text-muted">clasificación</small>
                        </div>
                    </div>
                </div>
            </a>
        </div>

    </div>
    <section class="mt-4" aria-label="Estadísticas de la tienda">
    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-6">
            <div class="card dashboard-chart-card">
                <div class="card-body">
                    <div id="chart"></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card dashboard-chart-card">
                <div class="card-body">
                    <div id="chart2"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="row g-3">
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card dashboard-chart-card">
                <div class="card-body">
                    <div id="chart3"></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card dashboard-chart-card">
                <div class="card-body">
                    <div id="chart4"></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card dashboard-chart-card">
                <div class="card-body">
                    <div id="chart5"></div>
                </div>
                </section>
                </main>
            </div>
        </div>
    </div>
    <script>
        //usuarios registrados por mes
        const usuariosData = @json(array_values($usuarios_data));

        var options = {
            chart: {
                type: 'area',
                height: 350,
                toolbar: {
                    show: false
                },
                zoom: {
                    enabled: false
                },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 1000
                }
            },

            colors: ['#435ebe'],

            series: [{
                name: 'Usuarios registrados',
                data: usuariosData
            }],

            stroke: {
                curve: 'smooth',
                width: 4
            },

            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.35,
                    opacityTo: 0.03,
                    stops: [0, 90, 100]
                }
            },

            markers: {
                size: 5,
                strokeWidth: 2,
                hover: {
                    size: 8
                }
            },

            dataLabels: {
                enabled: false
            },

            grid: {
                borderColor: '#e9ecef',
                strokeDashArray: 4,
                padding: {
                    left: 10,
                    right: 10
                }
            },

            xaxis: {
                categories: [
                    "Ene", "Feb", "Mar", "Abr",
                    "May", "Jun", "Jul", "Ago",
                    "Sep", "Oct", "Nov", "Dic"
                ],
                labels: {
                    style: {
                        fontSize: '12px'
                    }
                },
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false
                }
            },

            yaxis: {
                labels: {
                    style: {
                        fontSize: '12px'
                    }
                }
            },

            tooltip: {
                theme: 'light',
                y: {
                    formatter: function(val) {
                        return val + ' usuarios';
                    }
                }
            },

            legend: {
                position: 'top',
                horizontalAlign: 'right',
                fontSize: '14px'
            },

            title: {
                text: 'Usuarios Registrados por Mes',
                align: 'left',
                style: {
                    fontSize: '18px',
                    fontWeight: 600,
                    color: '#343a40'
                }
            },

            subtitle: {
                text: 'Estadísticas del año actual',
                align: 'left',
                style: {
                    fontSize: '13px',
                    color: '#6c757d'
                }
            }
        };

        var chart = new ApexCharts(
            document.querySelector("#chart"),
            options
        );

        chart.render();


        //pedidos por mes
        const ordenesdata = @json(array_values($ordenesdata));

        var options2 = {
            chart: {
                type: 'bar',
                height: 350,
                toolbar: {
                    show: false
                },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 1000
                }
            },

            series: [{
                name: 'Pedidos',
                data: ordenesdata
            }],

            colors: ['#435ebe'],

            plotOptions: {
                bar: {
                    borderRadius: 8,
                    columnWidth: '55%',
                    distributed: false
                }
            },

            dataLabels: {
                enabled: true,
                offsetY: -20,
                style: {
                    fontSize: '12px',
                    fontWeight: '600'
                }
            },

            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'light',
                    type: 'vertical',
                    shadeIntensity: 0.5,
                    gradientToColors: ['#6c8cff'],
                    opacityFrom: 1,
                    opacityTo: 1,
                    stops: [0, 100]
                }
            },

            grid: {
                borderColor: '#e9ecef',
                strokeDashArray: 4
            },

            xaxis: {
                categories: [
                    "Ene", "Feb", "Mar", "Abr",
                    "May", "Jun", "Jul", "Ago",
                    "Sep", "Oct", "Nov", "Dic"
                ],
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false
                },
                labels: {
                    style: {
                        fontSize: '12px'
                    }
                }
            },

            yaxis: {
                labels: {
                    style: {
                        fontSize: '12px'
                    }
                }
            },

            tooltip: {
                theme: 'light',
                y: {
                    formatter: function(val) {
                        return val + ' pedidos';
                    }
                }
            },

            title: {
                text: 'Pedidos por Mes',
                align: 'left',
                style: {
                    fontSize: '18px',
                    fontWeight: 600
                }
            },

            subtitle: {
                text: 'Resumen anual de pedidos',
                align: 'left'
            },

            legend: {
                show: false
            }
        };

        var chart2 = new ApexCharts(
            document.querySelector("#chart2"),
            options2
        );

        chart2.render();


        //estado de pedidos
        var options3 = {
            series: [
                {{ $total_pedidos_nuevos }},
                {{ $total_pedidos_enviados }}
            ],

            chart: {
                type: 'donut',
                height: 350,
                toolbar: {
                    show: false
                }
            },

            labels: [
                'Pedidos Nuevos',
                'Pedidos Enviados'
            ],

            colors: [
                '#ff6b6b',
                '#435ebe'
            ],

            stroke: {
                width: 0
            },

            plotOptions: {
                pie: {
                    donut: {
                        size: '70%',
                        labels: {
                            show: true,

                            total: {
                                show: true,
                                label: 'Total',
                                fontSize: '18px',
                                fontWeight: 600,
                                formatter: function(w) {
                                    return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                }
                            }
                        }
                    }
                }
            },

            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    return val.toFixed(1) + "%";
                }
            },

            legend: {
                position: 'bottom',
                fontSize: '14px'
            },

            tooltip: {
                y: {
                    formatter: function(val) {
                        return val + " pedidos";
                    }
                }
            },

            title: {
                text: 'Estado de Pedidos',
                align: 'left',
                style: {
                    fontSize: '18px',
                    fontWeight: 600
                }
            },

            subtitle: {
                text: 'Distribución actual',
                align: 'left'
            },

            responsive: [{
                breakpoint: 768,
                options: {
                    chart: {
                        height: 300
                    }
                }
            }]
        };

        var chart3 = new ApexCharts(
            document.querySelector("#chart3"),
            options3
        );

        chart3.render();

        //stok bajo
        const porcentajeStockBajo = {{ $porcentajeStockBajo }};
        const porcentajeStockNormal = 100 - porcentajeStockBajo;

        var options4 = {

            series: [
                porcentajeStockBajo,
                porcentajeStockNormal
            ],

            chart: {
                type: 'donut',
                height: 350,
                toolbar: {
                    show: false
                }
            },

            labels: [
                'Stock Bajo',
                'Stock Correcto'
            ],

            colors: [
                '#dc3545',
                '#198754'
            ],

            stroke: {
                width: 0
            },

            plotOptions: {
                pie: {
                    donut: {
                        size: '70%',
                        labels: {
                            show: true,

                            total: {
                                show: true,
                                label: 'Stock Bajo',
                                fontSize: '18px',
                                fontWeight: 600,

                                formatter: function() {
                                    return porcentajeStockBajo + '%';
                                }
                            }
                        }
                    }
                }
            },

            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    return val.toFixed(1) + '%';
                }
            },

            legend: {
                position: 'bottom',
                fontSize: '14px'
            },

            tooltip: {
                y: {
                    formatter: function(val) {
                        return val + '%';
                    }
                }
            },

            title: {
                text: 'Estado del Inventario',
                align: 'left',
                style: {
                    fontSize: '18px',
                    fontWeight: 600
                }
            },

            subtitle: {
                text: 'Porcentaje de productos con stock bajo',
                align: 'left'
            },

            responsive: [{
                breakpoint: 768,
                options: {
                    chart: {
                        height: 300
                    }
                }
            }]
        };

        var chart4 = new ApexCharts(
            document.querySelector("#chart4"),
            options4
        );

        chart4.render();

        //stock alto
        const porcentajeStockAlto = {{ $porcentajeStockAlto }};
        const porcentajeRestante = 100 - porcentajeStockAlto;

        var options5 = {

            series: [
                porcentajeStockAlto,
                porcentajeRestante
            ],

            chart: {
                type: 'donut',
                height: 350,
                toolbar: {
                    show: false
                }
            },

            labels: [
                'Stock Alto',
                'Resto del Inventario'
            ],

            colors: [
                '#435ebe', // principal
                '#adb5bd' // secundario visible
            ],

            stroke: {
                width: 0
            },

            plotOptions: {
                pie: {
                    donut: {
                        size: '70%',
                        labels: {
                            show: true,

                            total: {
                                show: true,
                                label: 'Stock Alto',
                                fontSize: '18px',
                                fontWeight: 600,

                                formatter: function() {
                                    return porcentajeStockAlto + '%';
                                }
                            }
                        }
                    }
                }
            },

            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    return val.toFixed(1) + '%';
                }
            },

            legend: {
                position: 'bottom',
                fontSize: '14px'
            },

            tooltip: {
                y: {
                    formatter: function(val) {
                        return val + '%';
                    }
                }
            },

            title: {
                text: 'Productos con Stock Alto',
                align: 'left',
                style: {
                    fontSize: '18px',
                    fontWeight: 600
                }
            },

            subtitle: {
                text: 'Productos con exceso de inventario',
                align: 'left'
            },

            responsive: [{
                breakpoint: 768,
                options: {
                    chart: {
                        height: 300
                    }
                }
            }]
        };

        var chart5 = new ApexCharts(
            document.querySelector("#chart5"),
            options5
        );

        chart5.render();
    </script>
@endsection
