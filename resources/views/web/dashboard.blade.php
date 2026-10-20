@extends('layouts.web')
@section("meta_robots", "noindex, follow")
@section('content')
    <style>
        /* Custom modern stepper styling */
        .custom-stepper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            margin: 30px 0;
            padding: 0 10px;
        }
        .custom-stepper::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 10%;
            right: 10%;
            height: 4px;
            background-color: #e5e7eb;
            z-index: 0;
        }
        .custom-step {
            text-align: center;
            position: relative;
            z-index: 1;
            flex: 1;
        }
        .custom-step-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #ffffff;
            border: 3px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: bold;
            color: #9ca3af;
            transition: all 0.3s ease;
        }
        .custom-step.completed .custom-step-icon {
            background-color: #198754;
            border-color: #198754;
            color: #ffffff;
        }
        .custom-step.active .custom-step-icon {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: #ffffff;
            box-shadow: 0 0 0 5px rgba(13, 110, 253, 0.2);
            transform: scale(1.1);
        }
        .custom-step-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #9ca3af;
        }
        .custom-step.completed .custom-step-label {
            color: #198754;
        }
        .custom-step.active .custom-step-label {
            color: #0d6efd;
        }
        
        /* Log timeline styling */
        .timeline-history {
            margin-top: 30px;
            padding: 20px;
            background: #f8fafc;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
        }
        .timeline-history-title {
            font-size: 1rem;
            font-weight: bold;
            color: #334155;
            margin-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 8px;
        }
        .timeline-history-item {
            display: flex;
            margin-bottom: 20px;
            position: relative;
        }
        .timeline-history-item:not(:last-child)::before {
            content: '';
            position: absolute;
            left: 17px;
            top: 30px;
            bottom: -20px;
            width: 2px;
            background-color: #cbd5e1;
        }
        .timeline-history-dot {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: #e2e8f0;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            z-index: 1;
            margin-right: 15px;
            flex-shrink: 0;
        }
        .timeline-history-dot.active {
            background-color: #0d6efd;
            color: #ffffff;
        }
        .timeline-history-content {
            flex-grow: 1;
            background: #ffffff;
            padding: 12px 15px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }
        .timeline-history-time {
            font-size: 0.75rem;
            color: #94a3b8;
            display: block;
            margin-top: 4px;
        }
        .timeline-history-note {
            font-size: 0.85rem;
            color: #475569;
            margin-top: 6px;
            background: #f1f5f9;
            padding: 8px;
            border-radius: 4px;
            display: inline-block;
            width: 100%;
        }
        .account .account-intro {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding: 1.25rem 1.5rem;
            border: 1px solid rgba(0, 0, 0, .06);
            border-radius: 16px;
            background: linear-gradient(120deg, rgba(244, 67, 54, .07), rgba(255, 255, 255, .9));
        }
        .account .account-intro h2 { margin-bottom: .35rem; font-size: 1.35rem; }
        .account .account-intro p { margin: 0; color: #64748b; }
        .account .account-intro__count {
            min-width: 6.5rem;
            padding: .65rem 1rem;
            border-radius: 12px;
            background: #fff;
            text-align: center;
            box-shadow: 0 4px 14px rgba(15, 23, 42, .05);
        }
        .account .account-intro__count strong { display: block; font-size: 1.35rem; line-height: 1.2; }
        .account .account-intro__count span { color: #64748b; font-size: .76rem; }
        .account .profile-menu .account-actions { display: grid; gap: .55rem; margin-top: 1rem; }
        .account .profile-menu .account-actions .btn { justify-content: flex-start; }
        .account .profile-menu .user-avatar .account-avatar-fallback { display: grid; place-items: center; }
        .account .orders-empty {
            padding: clamp(2rem, 6vw, 4rem) 1.25rem;
            border: 1px dashed rgba(0, 0, 0, .16);
            border-radius: 16px;
            text-align: center;
        }
        .account .orders-empty__icon {
            display: inline-grid;
            width: 4rem;
            height: 4rem;
            margin-bottom: 1rem;
            place-items: center;
            border-radius: 50%;
            background: rgba(244, 67, 54, .08);
            color: var(--accent-color);
            font-size: 1.7rem;
        }
        .account .content-area .orders-grid .order-card { min-width: 0; }
        .account .content-area .orders-grid .order-card .order-header { gap: .75rem; align-items: flex-start; }
        .account .content-area .orders-grid .order-card .order-header .order-id .value { overflow-wrap: anywhere; }
        .account .content-area .orders-grid .order-card .order-header .order-date { text-align: right; }
        .account .content-area .orders-grid .order-card .order-info .info-row > span:last-child { text-align: right; }
        .account .content-area .orders-grid .order-card .order-footer button:focus-visible,
        .account .profile-menu .account-actions .btn:focus-visible {
            outline: 3px solid rgba(13, 110, 253, .45);
            outline-offset: 2px;
        }
        .account .orders-pagination {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            margin-top: 1.25rem;
        }
        .account .orders-pagination p { margin: 0; color: #64748b; font-size: .875rem; }
        @media (max-width: 575.98px) {
            .account .account-intro { padding: 1rem; }
            .account .account-intro__count { width: 100%; text-align: left; }
            .account .content-area .orders-grid .order-card { padding: 1rem; }
            .account .content-area .orders-grid .order-card .order-header { flex-direction: column; }
            .account .content-area .orders-grid .order-card .order-header .order-date { text-align: left; }
            .account .content-area .orders-grid .order-card .order-footer { flex-direction: column; }
        }
    </style>
    <!-- Page Title -->
    <div class="page-title light-background">
        <div class="container d-lg-flex justify-content-between align-items-center">
            <h1 class="mb-2 mb-lg-0">Cuenta</h1>
            <nav class="breadcrumbs">
                <ol>
                    <li><a href="{{ url('/') }}">Principal</a></li>
                    <li class="current">Cuenta</li>
                </ol>
            </nav>
        </div>
    </div><!-- End Page Title -->

    <!-- Account Section -->
    <section id="account" class="account section">

        <div class="container" data-aos="fade-up" data-aos-delay="100">

            <!-- Mobile Menu Toggle -->
            <div class="mobile-menu d-lg-none mb-4">
                <button class="mobile-menu-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#profileMenu">
                    <i class="bi bi-grid"></i>
                    <span>Menú de cuenta</span>
                </button>
            </div>

            <div class="row g-4">
                <!-- Profile Menu -->
                <div class="col-lg-3">
                    <div class="profile-menu collapse d-lg-block" id="profileMenu">
                        <!-- User Info -->
                        <div class="user-info" data-aos="fade-right">
                            <div class="user-avatar">
                                @if ($ajuste?->logo)
                                    <img src="{{ asset('storage/' . $ajuste->logo) }}" alt="Logo de {{ $ajuste->nombre ?? 'la tienda' }}" loading="lazy">
                                @else
                                    <span class="account-avatar-fallback h-100 w-100 rounded-circle bg-light text-primary fs-2 fw-bold" aria-hidden="true">
                                        {{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                                    </span>
                                @endif
                                <span class="status-badge" aria-hidden="true"><i class="bi bi-shield-check"></i></span>
                            </div>
                            <h4>{{ Auth::user()->name }}</h4>
                            <div class="user-status">
                                <i class="bi bi-award"></i>
                                <span>{{ session('google_customer_mode') ? 'Cliente · sesión de compra' : (Auth::user()->roles()->pluck('name')->implode(', ') ?: 'Cliente') }}</span>
                            </div>
                            <p class="small text-muted mt-2 mb-0 text-break">{{ Auth::user()->email }}</p>
                        </div>

                        <!-- Navigation Menu -->
                        <nav class="menu-nav">
                            <ul class="nav flex-column">
                                <li class="nav-item">
                                    <a class="nav-link active" href="#orders" aria-current="page">
                                        <i class="bi bi-box-seam"></i>
                                        <span>Pedidos</span>
                                        <span class="badge">{{ $total_pedidos }}</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        <div class="account-actions">
                            <a href="{{ route('web.ajustes') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-person-gear me-2" aria-hidden="true"></i> Mi cuenta
                            </a>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                                    <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i> Cerrar sesión
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Content Area -->
                <div class="col-lg-9">
                    <div class="content-area">
                        <div class="tab-content">
                            <!-- Orders Tab -->
                            <div class="tab-pane fade show active" id="orders">
                                <div class="account-intro" data-aos="fade-up">
                                    <div>
                                        <h2>Hola, {{ Auth::user()->name }}</h2>
                                        <p>Consulta el estado y los detalles de tus compras.</p>
                                    </div>
                                    <div class="account-intro__count" aria-label="{{ $total_pedidos }} pedidos en total">
                                        <strong>{{ number_format($total_pedidos) }}</strong>
                                        <span>{{ $total_pedidos === 1 ? 'pedido realizado' : 'pedidos realizados' }}</span>
                                    </div>
                                </div>

                                <div class="orders-grid">
                                    @forelse ($pedidos as $pedido)
                                        @php
                                            $unidades = (int) $pedido->detalles->sum('cantidad');
                                            $estadoActual = $pedido->estado_orden;
                                            $estadoClass = match (mb_strtolower($estadoActual)) {
                                                'enviado', 'en transporte' => 'shipped',
                                                'entregado', 'listo para su recojo' => 'delivered',
                                                'cancelado' => 'cancelled',
                                                default => 'processing',
                                            };
                                            $flujo = $pedido->getFlujoEstados();
                                            $current_index = array_search($estadoActual, $flujo, true);
                                            $current_index = $current_index === false ? -1 : $current_index;
                                        @endphp
                                        <article class="order-card" data-aos="fade-up">
                                            <div class="order-header">
                                                <div class="order-id">
                                                    <span class="label">Número de pedido:</span>
                                                    <span class="value">#ORD-{{ $pedido->id }}</span>
                                                </div>
                                                <time class="order-date" datetime="{{ $pedido->fecha_registro?->toIso8601String() }}">
                                                    <i class="bi bi-calendar3 me-1" aria-hidden="true"></i>
                                                    {{ $pedido->fecha_registro?->format('d/m/Y H:i') ?? 'Fecha no disponible' }}
                                                </time>
                                            </div>
                                            <div class="order-content">
                                                <div class="product-grid">
                                                    @foreach ($pedido->detalles as $detalle)
                                                        @if ($detalle->producto)
                                                            <img src="{{ $detalle->producto->imagen_principal_url }}"
                                                                alt="{{ $detalle->producto->nombre }}" loading="lazy">
                                                        @endif
                                                    @endforeach
                                                    @if ($pedido->detalles->isEmpty() || $pedido->detalles->every(fn ($detalle) => ! $detalle->producto))
                                                        <span class="small text-muted">No hay imágenes de productos disponibles.</span>
                                                    @endif
                                                </div>
                                                <div class="order-info">
                                                    <div class="info-row">
                                                        <span>Estado de la orden</span>
                                                        <span class="status {{ $estadoClass }}">{{ $estadoActual }}</span>
                                                    </div>
                                                    <div class="info-row">
                                                        <span>Productos</span>
                                                        <span>{{ $unidades }} {{ $unidades === 1 ? 'unidad' : 'unidades' }}</span>
                                                    </div>
                                                    <div class="info-row">
                                                        <span>Total</span>
                                                         <span
                                                             class="price">{{ \App\Models\Ajuste::formatear_precio($pedido->total) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="order-footer">
                                                <button type="button" class="btn-track" data-bs-toggle="collapse"
                                                    data-bs-target="#tracking{{ $pedido->id }}"
                                                    aria-expanded="false" aria-controls="tracking{{ $pedido->id }}">
                                                    <i class="bi bi-geo-alt me-1" aria-hidden="true"></i> Seguimiento
                                                </button>
                                                <button type="button" class="btn-details" data-bs-toggle="collapse"
                                                    data-bs-target="#details{{ $pedido->id }}"
                                                    aria-expanded="false" aria-controls="details{{ $pedido->id }}">
                                                    <i class="bi bi-receipt me-1" aria-hidden="true"></i> Ver detalles
                                                </button>
                                            </div>

                                            <!-- Order Tracking -->
                                            <div class="collapse tracking-info" id="tracking{{ $pedido->id }}" aria-label="Seguimiento del pedido {{ $pedido->id }}">
                                                <div class="card border-0 shadow-sm mt-3 mb-4 bg-light">
                                                    <div class="card-body">
                                                        <h6 class="fw-bold mb-3 text-dark">
                                                            <i class="bi bi-geo-alt-fill text-primary"></i> Modalidad de entrega: {{ $pedido->getModalidadTexto() }}
                                                        </h6>
                                                        <div class="custom-stepper flex-column flex-md-row">
                                                            @foreach ($flujo as $idx => $f_estado)
                                                                @php
                                                                    $is_completed = $current_index >= 0 && $idx <= $current_index;
                                                                    $is_active = $idx == $current_index;
                                                                    $step_class = $is_active ? 'active' : ($is_completed ? 'completed' : '');
                                                                @endphp
                                                                <div class="custom-step {{ $step_class }} mb-3 mb-md-0">
                                                                    <div class="custom-step-icon">
                                                                        @if ($is_completed && !$is_active)
                                                                            <i class="bi bi-check-lg"></i>
                                                                        @else
                                                                            {{ $idx + 1 }}
                                                                        @endif
                                                                    </div>
                                                                    <div class="custom-step-label">{{ $f_estado }}</div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="timeline-history mt-3">
                                                    <div class="timeline-history-title">
                                                        <i class="bi bi-clock-history me-1" aria-hidden="true"></i> Historial de cambios
                                                    </div>
                                                    @forelse ($pedido->historial as $h)
                                                        <div class="timeline-history-item">
                                                            <div class="timeline-history-dot {{ $h->estado_nuevo === $pedido->estado_orden ? 'active' : '' }}">
                                                                @if ($h->estado_nuevo === $pedido->estado_orden)
                                                                    <i class="bi bi-geo-alt-fill"></i>
                                                                @else
                                                                    <i class="bi bi-circle"></i>
                                                                @endif
                                                            </div>
                                                            <div class="timeline-history-content">
                                                                <h6 class="fw-bold mb-0 text-dark">
                                                                    {{ $h->estado_nuevo }}
                                                                </h6>
                                                                <span class="timeline-history-time">
                                                                    <i class="bi bi-calendar3 me-1"></i> {{ date('d/m/Y', strtotime($h->fecha)) }} - 
                                                                    <i class="bi bi-clock me-1"></i> {{ date('H:i', strtotime($h->hora)) }}
                                                                </span>
                                                                @if ($h->nota)
                                                                    <div class="timeline-history-note">
                                                                        {{ $h->nota }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @empty
                                                        <p class="text-muted text-center py-2">No hay registros de seguimiento detallado.</p>
                                                    @endforelse
                                                </div>
                                            </div>

                                            <!-- Order Details -->
                                            <div class="collapse order-details" id="details{{ $pedido->id }}" aria-label="Detalles del pedido {{ $pedido->id }}">
                                                <div class="details-content">
                                                    <div class="detail-section">
                                                        <h5>Información del pedido</h5>
                                                    </div>

                                                    <div class="detail-section">
                                                        <h5>Productos: {{ $unidades }}</h5>
                                                        <div class="order-items">
                                                            @foreach ($pedido->detalles as $detalle)
                                                                <div class="item">
                                                                    @if ($detalle->producto)
                                                                        <img src="{{ $detalle->producto->imagen_principal_url }}"
                                                                            alt="{{ $detalle->producto->nombre }}" loading="lazy">
                                                                    @else
                                                                        <div class="d-grid bg-light text-muted rounded place-items-center" style="width:64px;height:64px" aria-hidden="true">
                                                                            <i class="bi bi-box-seam"></i>
                                                                        </div>
                                                                    @endif
                                                                    <div class="item-info">
                                                                        <h6>{{ $detalle->producto?->nombre ?? 'Producto no disponible' }}</h6>
                                                                        <div class="item-meta">
                                                                            @if ($detalle->producto?->descripcion_corta)
                                                                                <span class="sku">{{ $detalle->producto->descripcion_corta }}</span>
                                                                                <br>
                                                                            @endif
                                                                            <span class="qty">Cantidad:
                                                                                {{ $detalle->cantidad }}</span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="item-price">{{ \App\Models\Ajuste::formatear_precio($detalle->precio) }}</div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>

                                                    <div class="detail-section">
                                                        <h5>Detalles del precio</h5>
                                                        <div class="price-breakdown">
                                                            <div class="price-row total">
                                                                 <span>Total</span>
                                                                 <span>{{ \App\Models\Ajuste::formatear_precio($pedido->total) }}</span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="detail-section">
                                                        <h5>Tipo de entrega</h5>
                                                        <div class="address-info mb-3">
                                                            <p class="fw-bold text-primary mb-0">
                                                                {{ $pedido->getModalidadTexto() }}
                                                            </p>
                                                        </div>
                                                        @if ($pedido->direccion_envio)
                                                            <h5>Dirección registrada</h5>
                                                            <div class="address-info">
                                                                <p>{{ $pedido->direccion_envio }}</p>
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <div class="detail-section d-flex flex-wrap align-items-center gap-3">
                                                        <a href="{{ route('web.pedidos.confirmacion', $pedido->id) }}"
                                                            class="btn btn-primary btn-sm rounded-pill px-3">
                                                            Abrir pedido y completar el pago
                                                        </a>
                                                        <span class="small text-muted">Pago: {{ $pedido->getEstadoPagoTexto() }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </article>
                                    @empty
                                        <div class="orders-empty" role="status">
                                            <span class="orders-empty__icon" aria-hidden="true"><i class="bi bi-bag"></i></span>
                                            <h3 class="h5">Aún no tienes pedidos</h3>
                                            <p class="text-muted mb-3">Cuando realices una compra, aquí podrás consultar su estado y sus detalles.</p>
                                            <a href="{{ route('web.productos') }}" class="btn btn-primary">
                                                <i class="bi bi-shop me-1" aria-hidden="true"></i> Explorar productos
                                            </a>
                                        </div>
                                    @endforelse
                                </div>

                                @if ($pedidos->hasPages())
                                    <nav class="orders-pagination" aria-label="Paginación de pedidos">
                                        <p>Mostrando {{ $pedidos->firstItem() }}–{{ $pedidos->lastItem() }} de {{ number_format($pedidos->total()) }} pedidos</p>
                                        {{ $pedidos->links('pagination::bootstrap-5') }}
                                    </nav>
                                @endif

                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>

    </section><!-- /Account Section -->
@endsection
