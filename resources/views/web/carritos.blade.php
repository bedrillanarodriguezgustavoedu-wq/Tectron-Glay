@extends('layouts.web')
@section("meta_robots", "noindex, follow")
@section('content')
    @php
        $yapeDisponible = \App\Models\Ajuste::motivoParaOcultarPagoWeb($ajuste, 'yape') === null;
        $plinDisponible = \App\Models\Ajuste::motivoParaOcultarPagoWeb($ajuste, 'plin') === null;
        $bcpDisponible = \App\Models\Ajuste::motivoParaOcultarPagoWeb($ajuste, 'bcp') === null;
        $mercadoPago = app(\App\Services\MercadoPagoService::class);
        // Nadie puede forzar aquí lo que Mercado Pago no aceptó: un `true` en
        // esta línea enseña la tarjeta, el cliente paga mal y el pedido queda
        // pendiente. Solo la credencial verificada la enciende.
        $tarjetaDisponible = (bool) ($ajuste?->web_mercadopago_activa ?? false)
            && $mercadoPago->sePuedeOfrecer();
        // La tarjeta se muestra gris con «Próximamente» en cualquier estado en el
        // que la tienda la quiere y la credencial existe pero todavía no vale:
        // retirada por un rechazo o nunca probada. Sin token no se anuncia nada: no
        // hay ninguna tarjeta que "próximamente" funcione.
        $tarjetaProximamente = (bool) ($ajuste?->web_mercadopago_activa ?? false)
            && $mercadoPago->habilitado() && ! $tarjetaDisponible;
    @endphp
    <style>
        .checkout-stepper .checkout-step {
            color: #7b8492;
            z-index: 0;
        }

        .checkout-stepper .checkout-step:not(:last-child)::after {
            content: "";
            position: absolute;
            height: 2px;
            background: #dee2e6;
            top: 19px;
            left: calc(50% + 25px);
            width: calc(100% - 50px);
            z-index: -1;
        }

        .checkout-stepper .checkout-step-number {
            width: 40px;
            height: 40px;
            color: #6c757d;
            background: #e9ecef;
            font-weight: 700;
            transition: background-color .2s ease, color .2s ease;
        }

        .checkout-stepper .checkout-step-label {
            display: block;
            font-weight: 600;
        }

        .checkout-step-trigger {
            width: 100%;
            padding: 0;
            border: 0;
            background: transparent;
            color: inherit;
            font: inherit;
            cursor: pointer;
        }

        .checkout-step-trigger:focus-visible {
            outline: 3px solid rgba(13, 110, 253, .35);
            outline-offset: 4px;
            border-radius: .5rem;
        }

        .checkout-stepper .checkout-step.is-active,
        .checkout-stepper .checkout-step.is-complete {
            color: #0d6efd;
        }

        .checkout-stepper .checkout-step.is-active .checkout-step-number,
        .checkout-stepper .checkout-step.is-complete .checkout-step-number {
            color: #fff;
            background: #0d6efd;
        }

        .checkout-stepper .checkout-step.is-complete::after {
            background: #0d6efd;
        }

        @media (min-width: 992px) {
            .cart-summary {
                position: sticky;
                top: 1.5rem;
            }
        }

        .checkout-step-panel[hidden] {
            display: none !important;
        }

        .checkout-method-grid {
            display: grid;
            gap: .75rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        @media (min-width: 992px) {
            .checkout-method-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
        }

        .checkout-method-card {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .15rem;
            margin: 0;
            padding: 1.15rem .75rem .9rem;
            text-align: center;
            border: 1.5px solid #e4eaf2;
            border-radius: 1rem;
            background: #fff;
            cursor: pointer;
            transition: border-color .2s ease, background-color .2s ease, box-shadow .2s ease;
        }

        .checkout-method-card--proximamente {
            cursor: default;
            border-style: dashed;
            background: #f8fafc;
            opacity: .78;
        }

        .checkout-method-card--proximamente:hover {
            border-color: #e4eaf2;
            box-shadow: none;
        }

        .checkout-method-card__badge {
            position: absolute;
            top: .55rem;
            right: .55rem;
            font-size: .64rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: #64748b;
            background: #e9eef5;
            border-radius: 999px;
            padding: .12rem .5rem;
        }

        .checkout-method-card .form-check-input {
            position: absolute;
            top: .6rem;
            right: .6rem;
            float: none;
            margin: 0;
        }

        .checkout-method-card:hover {
            border-color: #b9cdf7;
        }

        .checkout-method-card:has(input:checked) {
            border-color: #0d6efd;
            background-color: #f4f8ff;
            box-shadow: 0 .5rem 1.25rem rgba(13, 110, 253, .14);
        }

        .checkout-method-card:has(input:focus-visible) {
            outline: 3px solid rgba(13, 110, 253, .35);
            outline-offset: 3px;
        }

        .checkout-method-card__icon {
            display: grid;
            place-items: center;
            width: 44px;
            height: 44px;
            margin-bottom: .35rem;
            border-radius: .85rem;
            background: var(--brand, #0d6efd);
            color: #fff;
            font-size: 1.3rem;
        }

        /* El logotipo de la marca ya trae su propio color y su forma: dentro del
           cuadro pintado del método se leería mal, así que va sobre blanco. */
        .checkout-method-card__icon--marca {
            background: #fff;
            border: 1px solid #e5e9f2;
        }

        .checkout-method-card__icon--marca img {
            width: 30px;
            height: 30px;
            object-fit: contain;
        }

        .checkout-method-card__title {
            font-weight: 700;
        }

        .checkout-method-card__hint {
            font-size: .75rem;
            color: #6c757d;
        }

        .checkout-account {
            display: grid;
            gap: 1.25rem;
            justify-items: center;
            align-items: start;
        }

        @media (min-width: 768px) {
            .checkout-account {
                grid-template-columns: minmax(0, 280px) minmax(0, 1fr);
                justify-items: start;
            }
        }

        .checkout-account__data {
            width: 100%;
            max-width: 420px;
        }

        .checkout-account__actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            margin-bottom: 1rem;
        }

        .payment-icons [data-payment-badge] {
            opacity: .5;
            transition: opacity .2s ease, background-color .2s ease, border-color .2s ease;
        }

        .payment-icons [data-payment-badge].is-selected {
            opacity: 1;
            background-color: #0d6efd !important;
            border-color: #0d6efd !important;
            color: #fff !important;
        }

        .checkout-qr-preview {
            border: 1px solid #dbe5f3;
            border-radius: 1rem;
            background: linear-gradient(145deg, #f8fbff, #fff);
        }

        .checkout-qr-preview__image {
            width: min(100%, 280px);
            aspect-ratio: 1;
            object-fit: contain;
            padding: .65rem;
            border: 1px solid #edf0f4;
            border-radius: .85rem;
            background: #fff;
        }

        .checkout-qr-preview__amount {
            display: inline-block;
            padding: .4rem .75rem;
            border-radius: 999px;
            background: #eef4ff;
            color: #174ea6;
            font-weight: 700;
        }

        .checkout-qr-preview__image-wrap {
            position: relative;
            display: inline-flex;
            padding: .55rem;
            border: 1px solid #e8edf4;
            border-radius: 1rem;
            background: #fff;
            box-shadow: 0 .5rem 1.5rem rgba(31, 45, 61, .08);
        }

        .checkout-bcp-data {
            max-width: 420px;
            display: grid;
            gap: .5rem;
        }

        .checkout-bcp-data__row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: .65rem .85rem;
            border: 1px solid #e4eaf2;
            border-radius: .75rem;
            background: #fff;
        }

        .checkout-bcp-data__label {
            display: block;
            font-size: .75rem;
            color: #6c757d;
        }

        @media (max-width: 575.98px) {
            .checkout-qr-preview__image {
                width: min(100%, 240px);
            }
        }

        .cart-items,
        #checkout-form .card,
        .cart-summary {
            border: 1px solid #edf0f4;
            border-radius: 1rem;
            box-shadow: 0 .25rem 1rem rgba(31, 45, 61, .06);
        }

        .cart-items {
            padding: 1rem;
        }

        .cart-items .cart-header {
            border-bottom: 1px solid #edf0f4;
            margin-bottom: .75rem;
        }

        .cart-item {
            border-bottom: 1px solid #edf0f4;
            padding: .5rem 0 1rem;
        }

        .cart-item:last-of-type {
            border-bottom: 0;
        }

        .cart-item .product-image img {
            width: 76px;
            height: 76px;
            object-fit: contain;
            border: 1px solid #edf0f4;
            border-radius: .75rem;
            background: #fff;
        }

        .cart-summary {
            padding: 1.25rem;
        }

        @media (max-width: 767.98px) {
            .cart-items,
            .cart-summary {
                padding: 1rem;
            }

            .cart-item .product-info {
                align-items: flex-start !important;
            }

            .cart-item .quantity-selector {
                min-height: 42px;
            }
        }
    </style>
    <!-- Page Title -->
    <div class="page-title light-background">
        <div class="container d-lg-flex justify-content-between align-items-center">
            <h1 class="mb-2 mb-lg-0">Carrito de compras</h1>
            <nav class="breadcrumbs">
                <ol>
                    <li><a href="{{ url('/') }}">Principal</a></li>
                    <li class="current">Carrito</li>
                </ol>
            </nav>
        </div>
    </div><!-- End Page Title -->

    <!-- Cart Section -->
    <section id="cart" class="cart section">

        <div class="container" data-aos="fade-up" data-aos-delay="100">
            @if (session('mensaje'))
                @php
                    $checkoutAlertType = match (session('icono')) {
                        'success' => 'success',
                        'warning' => 'warning',
                        'info' => 'info',
                        default => 'danger',
                    };
                @endphp
                <div class="alert alert-{{ $checkoutAlertType }} alert-dismissible fade show rounded-3 mb-4" role="alert">
                    {{ session('mensaje') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger rounded-3 mb-4" role="alert" aria-live="assertive">
                    <strong>Revisa estos datos antes de continuar:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row">
                <div class="col-lg-8" data-aos="fade-up" data-aos-delay="200">
                    <div class="cart-items">
                        <div class="cart-header d-none d-lg-block">
                            <div class="row align-items-center">
                                <div class="col-lg-6">
                                    <h5>Productos</h5>
                                </div>
                                <div class="col-lg-2 text-center">
                                    <h5>Precios</h5>
                                </div>
                                <div class="col-lg-2 text-center">
                                    <h5>Cantidad</h5>
                                </div>
                                <div class="col-lg-2 text-center">
                                    <h5>SubTotal</h5>
                                </div>
                            </div>
                        </div>
                        @php
                            $subtotal_original_acumulado = 0;
                            $subtotal_acumulado = 0;
                        @endphp

                        @if ($carritos->isNotEmpty())
                            <form action="{{ route('web.carrito.update') }}" method="POST" id="form-actualizar-carrito">
                                @csrf
                                @method('PUT')
                        @endif

                        @forelse ($carritos as $carrito)
                            <!-- Cart Item -->
                            <div class="cart-item">
                                <div class="row align-items-center">

                                    <div class="col-lg-6 col-12 mt-3 mt-lg-0 mb-lg-0 mb-3">
                                        <div class="product-info d-flex align-items-center">
                                            <div class="product-image">
                                                @php
                                                    $imagen = $carrito->producto->imagen_principal_url;
                                                @endphp

                                                <img src="{{ $imagen }}" alt="{{ $carrito->producto->nombre }}"
                                                    class="img-fluid" loading="lazy"
                                                    onerror="this.onerror=null;this.src='{{ asset('storage/productos/default.webp') }}';">
                                            </div>

                                            <div class="product-details">
                                                <h6 class="product-title">{{ $carrito->producto->nombre }}</h6>

                                                <span class="badge {{ $carrito->producto->stock <= 5 ? 'bg-warning text-dark' : 'bg-success' }}">
                                                    {{ $carrito->producto->stock }} disponibles
                                                </span>

                                                <br>

                                                <!-- Delete button -->
                                                <button class="btn btn-outline-danger btn-sm mt-2" type="button"
                                                    onclick="confirmarEliminarItem({{ $carrito->id }})">
                                                    <i class="bi bi-trash"></i> Borrar
                                                </button>

                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-2 col-12 mt-3 mt-lg-0 text-center">
                                        <div class="price-tag">
                                            @if ($carrito->producto->en_oferta && $carrito->producto->precio_oferta > 0)
                                                <span class="text-muted text-decoration-line-through d-block text-xs" style="font-size: 13px;">
                                                    {{ \App\Models\Ajuste::formatear_precio($carrito->producto->precio_venta) }}
                                                </span>
                                                <span class="current-price text-danger fw-bold fs-5">
                                                    {{ \App\Models\Ajuste::formatear_precio($carrito->producto->precio_oferta) }}
                                                </span>
                                                <span class="badge bg-danger rounded-pill px-2 py-0.5 mt-1" style="font-size: 10px;">
                                                    {{ $carrito->producto->descuento }}% OFF
                                                </span>
                                            @else
                                                <span class="current-price fw-bold">
                                                    {{ \App\Models\Ajuste::formatear_precio($carrito->producto->precio_venta) }}
                                                </span>
                                            @endif
                                        </div>


                                    </div>

                                    <div class="col-lg-2 col-12 mt-3 mt-lg-0 text-center">
                                        <div class="quantity-selector d-flex align-items-center justify-content-center">
                                            <button type="button" class="quantity-btn decrease-qty" data-id="{{ $carrito->id }}">
                                                <i class="bi bi-dash"></i>
                                            </button>
                                            <input type="number" class="quantity-input mx-1 text-center"
                                                id="quantity-input-{{ $carrito->id }}"
                                                value="{{ $carrito->cantidad }}" min="1"
                                                max="{{ $carrito->producto->stock }}" name="cantidades[{{ $carrito->id }}]"
                                                style="width: 55px; border: 1px solid #ced4da; border-radius: 4px; padding: 4px;">
                                            <button type="button" class="quantity-btn increase-qty" data-id="{{ $carrito->id }}">
                                                <i class="bi bi-plus"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="col-lg-2 col-12 mt-3 mt-lg-0 text-center">
                                        <div class="item-total">
                                            @php
                                                $precio_original = $carrito->producto->precio_venta;
                                                $precio_actual = ($carrito->producto->en_oferta && $carrito->producto->precio_oferta > 0)
                                                    ? $carrito->producto->precio_oferta
                                                    : $carrito->producto->precio_venta;

                                                $subtotal_original_item = $precio_original * $carrito->cantidad;
                                                $subtotal_item = $precio_actual * $carrito->cantidad;
                                                $ahorro_item = $subtotal_original_item - $subtotal_item;

                                                $subtotal_original_acumulado += $subtotal_original_item;
                                                $subtotal_acumulado += $subtotal_item;
                                            @endphp

                                            <span class="fw-bold fs-5 text-dark">
                                                {{ \App\Models\Ajuste::formatear_precio($subtotal_item) }}
                                            </span>
                                            @if ($ahorro_item > 0)
                                                <small class="text-success d-block fw-semibold" style="font-size: 11px;">
                                                    Ahorras {{ \App\Models\Ajuste::formatear_precio($ahorro_item) }}
                                                </small>
                                            @endif
                                        </div>
                                    </div>

                                </div>
                            </div><!-- End Cart Item -->
                        @empty
                            <div class="text-center py-5">
                                <i class="bi bi-cart-x text-muted display-1"></i>
                                <h3 class="mt-3 fw-bold">Tu carrito está vacío</h3>
                                <p class="text-muted">¡Explora nuestra tienda y añade productos tecnológicos!</p>
                                <a href="{{ url('/') }}" class="btn btn-primary rounded-pill px-4 py-2 mt-2">Ir a la Tienda</a>
                            </div>
                        @endforelse

                        @php
                            $reglasEnvio = (array) config('tienda.envio');
                            $envioGratis = $subtotal_acumulado >= $reglasEnvio['umbral_gratis'];
                            $costoEnvioInicial = $carritos->isNotEmpty() && ! $envioGratis
                                ? $reglasEnvio['costo_estandar']
                                : 0;
                        @endphp

                        @if ($carritos->isNotEmpty())
                            </form>

                            <div class="cart-actions mt-4">
                                <div class="row align-items-center">
                                    <div class="col-lg-6 mb-3 mb-lg-0 text-start">
                                        <button type="submit" form="form-actualizar-carrito" class="btn btn-outline-primary rounded-pill px-4">
                                            <i class="bi bi-check2"></i> Guardar cambios
                                        </button>
                                    </div>
                                    <div class="col-lg-6 text-end">
                                        <form action="{{ url('/carrito/limpiar') }}" method="POST" id="clear-cart-form" class="d-inline">
                                            @csrf
                                            <button type="button" class="btn btn-outline-danger rounded-pill px-4" onclick="confirmarLimpiarCarrito()">
                                                <i class="bi bi-trash"></i> Limpiar carrito
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                                        @if ($carritos->isNotEmpty())
                                            <form action="{{ route('web.pedidos.pago_manual') }}" method="POST" id="checkout-form" class="mt-4" novalidate
                                                data-initial-step="{{ $errors->hasAny(['name', 'dni', 'telefono']) ? 1 : ($errors->hasAny(['direccion_id', 'modalidad_entrega']) ? 2 : ($errors->any() ? 3 : 1)) }}">
                                                @csrf
                                                <input type="hidden" name="total" id="hidden-total" value="{{ $subtotal_acumulado + $costoEnvioInicial }}">
                                                <input type="hidden" name="shipping_option" id="hidden-shipping-option" value="{{ $costoEnvioInicial === 0 ? 'free' : 'standard' }}">

                                                <div class="card border-0 shadow-sm rounded-4">
                                                    <div class="card-body p-3 p-lg-4">
                                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                                                            <div>
                                                                <h2 class="h4 fw-bold mb-1">Finaliza tu compra</h2>
                                                                <p class="text-muted small mb-0">Completa los pasos para confirmar tu pedido.</p>
                                                            </div>
                                                            <span class="badge rounded-pill bg-light text-primary border px-3 py-2">
                                                                Paso <span id="checkout-current-step">1</span> de 3
                                                            </span>
                                                        </div>
                                                        <nav aria-label="Pasos de compra" class="checkout-stepper mb-4">
                                                            <ol class="list-unstyled d-flex align-items-start mb-0">
                                                                @foreach (['Identificación', 'Entrega', 'Pago'] as $stepIndex => $stepLabel)
                                                                    <li class="checkout-step flex-fill text-center {{ $stepIndex < 2 ? 'position-relative' : '' }}"
                                                                        data-checkout-indicator="{{ $stepIndex + 1 }}">
                                                                        <button type="button" class="checkout-step-trigger" data-checkout-go="{{ $stepIndex + 1 }}"
                                                                            aria-label="Ir al paso {{ $stepIndex + 1 }}: {{ $stepLabel }}"
                                                                            aria-controls="checkout-panel-{{ $stepIndex + 1 }}">
                                                                            <span class="checkout-step-number d-flex align-items-center justify-content-center rounded-circle mx-auto mb-2">
                                                                                {{ $stepIndex + 1 }}
                                                                            </span>
                                                                            <span class="checkout-step-label small">{{ $stepLabel }}</span>
                                                                        </button>
                                                                    </li>
                                                                @endforeach
                                                            </ol>
                                                        </nav>

                                                        <section class="checkout-step-panel" id="checkout-panel-1" data-checkout-panel="1" aria-labelledby="checkout-step-1-title">
                                                            <h2 id="checkout-step-1-title" class="h4 fw-bold mb-2">
                                                                <i class="bi bi-person-check me-2 text-primary"></i>Tus datos personales
                                                            </h2>
                                                            <p class="text-muted mb-4">Completa tus datos para registrar correctamente el pedido.</p>
                                                            <div class="alert alert-light border small mb-3" role="note">
                                                                Usaremos estos datos para identificar tu pedido y coordinar la entrega. Tu DNI se guarda cifrado.
                                                            </div>
                                                            <div class="row g-3">
                                                                <div class="col-12">
                                                                    <label for="checkout-customer-name" class="form-label fw-semibold">Nombre completo <span class="text-danger">*</span></label>
                                                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                                                        id="checkout-customer-name" name="name" value="{{ old('name', auth()->user()->name) }}"
                                                                        maxlength="255" autocomplete="name" required>
                                                                    @error('name')
                                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                                    @enderror
                                                                </div>
                                                                <div class="col-12">
                                                                    <label for="checkout-customer-email" class="form-label fw-semibold">Correo de tu cuenta (Gmail/Google)</label>
                                                                    <input type="email" class="form-control bg-light" id="checkout-customer-email"
                                                                        value="{{ auth()->user()->email }}" readonly aria-describedby="checkout-email-help">
                                                                    <div id="checkout-email-help" class="form-text">
                                                                        @if (auth()->user()->google_id)
                                                                            <i class="bi bi-patch-check-fill text-success me-1"></i>Correo verificado con Google.
                                                                        @else
                                                                            @if (filled(config('services.google.client_id')) && filled(config('services.google.client_secret')))
                                                                                <a href="{{ route('auth.google.link') }}">Verifica este correo con Google</a> antes de pagar.
                                                                            @else
                                                                                Google aún no está configurado para verificar el correo.
                                                                            @endif
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label for="checkout-customer-dni" class="form-label fw-semibold">DNI <span class="text-danger">*</span></label>
                                                                    <input type="text" class="form-control @error('dni') is-invalid @enderror"
                                                                        id="checkout-customer-dni" name="dni" value="{{ old('dni', auth()->user()->dni) }}"
                                                                        inputmode="numeric" autocomplete="off" pattern="[0-9]{8}" minlength="8" maxlength="8"
                                                                        aria-describedby="checkout-dni-help" required>
                                                                    <div id="checkout-dni-help" class="form-text">Ingresa los 8 números de tu DNI.</div>
                                                                    @error('dni')
                                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                                    @enderror
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label for="checkout-customer-phone" class="form-label fw-semibold">Teléfono <span class="text-danger">*</span></label>
                                                                    <input type="tel" class="form-control @error('telefono') is-invalid @enderror"
                                                                        id="checkout-customer-phone" name="telefono" value="{{ old('telefono', auth()->user()->telefono) }}"
                                                                        inputmode="tel" autocomplete="tel" pattern="[+]?[0-9\s().-]{7,20}" maxlength="20" required>
                                                                    @error('telefono')
                                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                                    @enderror
                                                                </div>
                                                                <div class="col-12">
                                                                    <div class="form-check border rounded-3 p-3 @error('privacy_consent') border-danger @enderror">
                                                                        <input class="form-check-input" type="checkbox" id="checkout-privacy-consent"
                                                                            name="privacy_consent" value="1" @checked(old('privacy_consent')) required>
                                                                        <label class="form-check-label" for="checkout-privacy-consent">
                                                                            He leído y acepto la
                                                                            <a href="{{ route('web.privacidad') }}" target="_blank" rel="noopener noreferrer">Política de Privacidad</a>.
                                                                            Entiendo que mis datos se usarán para registrar el pedido y coordinar la entrega. <span class="text-danger">*</span>
                                                                        </label>
                                                                        @error('privacy_consent')
                                                                            <div class="text-danger small mt-2">{{ $message }}</div>
                                                                        @enderror
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="d-flex justify-content-end mt-4">
                                                                <button type="button" class="btn btn-primary rounded-pill px-4" data-checkout-next="2">
                                                                    Continuar a la entrega <i class="bi bi-arrow-right ms-1"></i>
                                                                </button>
                                                            </div>
                                                        </section>

                                                        <section class="checkout-step-panel" id="checkout-panel-2" data-checkout-panel="2" aria-labelledby="checkout-step-2-title" hidden>
                                                            <h2 id="checkout-step-2-title" class="h4 fw-bold mb-2">
                                                                <i class="bi bi-truck me-2 text-primary"></i>Dirección y entrega
                                                            </h2>
                                                            <p class="text-muted mb-4">Registra una dirección y elige si deseas envío o recojo en tienda.</p>

                                                            <div class="mb-4">
                                                                <span class="summary-label fw-semibold d-block mb-2">Modalidad de entrega</span>
                                                                <div class="btn-group w-100" role="group" aria-label="Modalidad de entrega">
                                                                    <input type="radio" class="btn-check" name="modalidad_entrega" id="modalidad_envio" value="envio" checked autocomplete="off">
                                                                    <label class="btn btn-outline-primary py-2" for="modalidad_envio">
                                                                        <i class="bi bi-truck me-1"></i> Envío a domicilio
                                                                    </label>
                                                                    <input type="radio" class="btn-check" name="modalidad_entrega" id="modalidad_recojo" value="recojo" autocomplete="off">
                                                                    <label class="btn btn-outline-primary py-2" for="modalidad_recojo">
                                                                        <i class="bi bi-shop me-1"></i> Recojo en tienda
                                                                    </label>
                                                                </div>
                                                            </div>

                                                            <div class="mb-4" id="shipping-address-container">
                                                                <label for="direccion_id_select" class="summary-label fw-semibold mb-2 d-block">Dirección registrada <span class="text-danger">*</span></label>
                                                                <p class="small text-muted mb-2">La dirección queda asociada a tu pedido, incluso si eliges recojo en tienda.</p>
                                                                <div id="no-addresses-alert" class="alert alert-warning py-2 px-3 my-2 {{ $direcciones->isNotEmpty() ? 'd-none' : '' }}" style="font-size: 13px;">
                                                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>Registra y selecciona una dirección antes de continuar.
                                                                </div>
                                                                <select class="form-select {{ $direcciones->isEmpty() ? 'd-none' : '' }}" name="direccion_id" id="direccion_id_select" @required($direcciones->isNotEmpty())>
                                                                    @foreach ($direcciones as $dir)
                                                                        <option value="{{ $dir->id }}" {{ $dir->es_predeterminada ? 'selected' : '' }}>
                                                                            [{{ $dir->alias }}] {{ $dir->direccion_exacta }} ({{ $dir->provincia->nombre }}, {{ $dir->departamento->nombre }})
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 mt-2"
                                                                    data-bs-toggle="modal" data-bs-target="#expressAddressModal">
                                                                    <i class="bi bi-plus-circle me-1"></i>Agregar dirección
                                                                </button>
                                                            </div>

                                                            <div class="mb-3" id="shipping-options-container">
                                                                <span class="summary-label fw-semibold d-block mb-2">Velocidad de entrega</span>
                                                                <div class="form-check border rounded-3 p-3 ps-5 mb-2">
                                                                    <input class="form-check-input shipping-radio" type="radio" name="shipping_selector" id="standard_shipping" value="standard" checked>
                                                                    <label class="form-check-label w-100" for="standard_shipping">
                                                                        <span class="fw-semibold">Estándar</span>
                                                                        <span class="float-end" data-shipping-standard-price>{{ $envioGratis ? 'Gratis' : \App\Models\Ajuste::formatear_precio($reglasEnvio['costo_estandar']) }}</span>
                                                                        <small class="d-block text-muted {{ $envioGratis ? 'd-none' : '' }}" data-shipping-umbral-hint>
                                                                            Se elimina el cargo en pedidos desde {{ \App\Models\Ajuste::formatear_precio($reglasEnvio['umbral_gratis']) }}.
                                                                        </small>
                                                                    </label>
                                                                </div>
                                                                <div class="form-check border rounded-3 p-3 ps-5">
                                                                    <input class="form-check-input shipping-radio" type="radio" name="shipping_selector" id="express_shipping" value="express">
                                                                    <label class="form-check-label w-100" for="express_shipping">
                                                                        <span class="fw-semibold">Urgente</span>
                                                                        <span class="float-end">{{ \App\Models\Ajuste::formatear_precio($reglasEnvio['costo_express']) }}</span>
                                                                        <small class="d-block text-muted">Cuesta lo mismo aunque tu pedido tenga envío gratis.</small>
                                                                    </label>
                                                                </div>
                                                            </div>

                                                            <div class="d-flex justify-content-between gap-2 mt-4">
                                                                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-checkout-previous="1">
                                                                    <i class="bi bi-arrow-left me-1"></i>Volver
                                                                </button>
                                                                <button type="button" class="btn btn-primary rounded-pill px-4" data-checkout-next="3">
                                                                    Continuar al pago <i class="bi bi-arrow-right ms-1"></i>
                                                                </button>
                                                            </div>
                                                        </section>

                                                        <section class="checkout-step-panel" id="checkout-panel-3" data-checkout-panel="3" aria-labelledby="checkout-step-3-title" hidden>
                                                            <h2 id="checkout-step-3-title" class="h4 fw-bold mb-2">
                                                                <i class="bi bi-credit-card me-2 text-primary"></i>Método de pago
                                                            </h2>
                                                            <p class="text-muted mb-4">Toca el método que usarás y te mostramos sus datos. Con Yape, Plin o BCP transfiere siempre el total exacto, verifica el titular y adjunta tu comprobante al crear el pedido. Con tarjeta el pago lo confirma Mercado Pago solo, sin comprobantes.</p>
                                                            <div class="mb-4" id="payment-options-container" role="radiogroup" aria-labelledby="payment-method-label">
                                                                <span class="summary-label fw-semibold d-block mb-2" id="payment-method-label">Elige cómo pagar</span>
                                                                @php $metodoSolicitado = old('metodo_pago'); @endphp
                                                                <div class="checkout-method-grid">
                                                                    @if ($yapeDisponible)
                                                                        <label for="pago_yape" class="checkout-method-card">
                                                                            <input class="form-check-input" type="radio" name="metodo_pago" id="pago_yape" value="yape" @checked($metodoSolicitado === 'yape')>
                                                                            <span class="checkout-method-card__icon checkout-method-card__icon--marca" aria-hidden="true"><img src="{{ asset('assets/img/pagos/yape.png') }}" width="30" height="30" alt=""></span>
                                                                            <span class="checkout-method-card__title">Yape</span>
                                                                            <small class="checkout-method-card__hint">Transferencia manual · Revisión de la tienda</small>
                                                                        </label>
                                                                    @endif
                                                                    @if ($bcpDisponible)
                                                                        <label for="pago_bcp" class="checkout-method-card">
                                                                            <input class="form-check-input" type="radio" name="metodo_pago" id="pago_bcp" value="bcp" @checked($metodoSolicitado === 'bcp')>
                                                                            <span class="checkout-method-card__icon" style="--brand:#d71920" aria-hidden="true"><i class="bi bi-bank"></i></span>
                                                                            <span class="checkout-method-card__title">Transferencia BCP</span>
                                                                            <small class="checkout-method-card__hint">Desde tu app bancaria · Revisión de la tienda</small>
                                                                        </label>
                                                                    @endif
                                                                    @if ($plinDisponible)
                                                                        <label for="pago_plin" class="checkout-method-card">
                                                                            <input class="form-check-input" type="radio" name="metodo_pago" id="pago_plin" value="plin" @checked($metodoSolicitado === 'plin')>
                                                                            <span class="checkout-method-card__icon checkout-method-card__icon--marca" aria-hidden="true"><img src="{{ asset('assets/img/pagos/plin.png') }}" width="30" height="30" alt=""></span>
                                                                            <span class="checkout-method-card__title">Plin</span>
                                                                            <small class="checkout-method-card__hint">Transferencia manual · Revisión de la tienda</small>
                                                                        </label>
                                                                    @endif
                                                                    @if ($tarjetaDisponible)
                                                                        <label for="pago_tarjeta" class="checkout-method-card">
                                                                            <input class="form-check-input" type="radio" name="metodo_pago" id="pago_tarjeta" value="tarjeta" @checked($metodoSolicitado === 'tarjeta')>
                                                                            <span class="checkout-method-card__icon checkout-method-card__icon--marca" aria-hidden="true"><img src="{{ asset('assets/img/pagos/visa.svg') }}" width="31" height="22" alt=""></span>
                                                                            <span class="checkout-method-card__title">Tarjeta Visa</span>
                                                                            <small class="checkout-method-card__hint">Débito o crédito · Confirmación automática</small>
                                                                        </label>
                                                                    @elseif ($tarjetaProximamente)
                                                                        <div class="checkout-method-card checkout-method-card--proximamente" aria-disabled="true">
                                                                            <span class="checkout-method-card__badge">Próximamente</span>
                                                                            <span class="checkout-method-card__icon checkout-method-card__icon--marca" aria-hidden="true"><img src="{{ asset('assets/img/pagos/visa.svg') }}" width="31" height="22" alt=""></span>
                                                                            <span class="checkout-method-card__title">Tarjeta Visa</span>
                                                                            <small class="checkout-method-card__hint">Estamos activando este medio de pago</small>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                                @php $hayMetodoSeleccionado = in_array($metodoSolicitado, ['yape', 'plin', 'bcp', 'tarjeta'], true); @endphp
                                                                @if ($yapeDisponible || $plinDisponible || $bcpDisponible || $tarjetaDisponible)
                                                                    <div class="checkout-qr-preview p-3 p-md-4 mt-3 @if (! $hayMetodoSeleccionado) d-none @endif" id="checkout-qr-preview" aria-live="polite">
                                                                        <div class="checkout-account__actions">
                                                                            <span class="summary-label fw-semibold mb-0">Datos del pago</span>
                                                                            <button type="button" class="btn btn-sm btn-link text-muted p-0" data-checkout-change-method>
                                                                                <i class="bi bi-arrow-left-short me-1" aria-hidden="true"></i>Cambiar método
                                                                            </button>
                                                                        </div>
                                                                        @if ($yapeDisponible)
                                                                            <div data-checkout-qr="yape" @if ($metodoSolicitado !== 'yape') hidden @endif>
                                                                                <div class="checkout-account">
                                                                                    <div class="text-center">
                                                                                        <h3 class="h6 fw-bold mb-2">Escanea el QR de Yape</h3>
                                                                                        <div class="checkout-qr-preview__image-wrap">
                                                                                            <img class="checkout-qr-preview__image" src="{{ asset('storage/'.ltrim($ajuste->web_yape_qr, '/')) }}"
                                                                                                alt="Código QR oficial de Yape de {{ $ajuste->web_yape_titular }}" loading="eager">
                                                                                        </div>
                                                                                        <p class="small text-muted mt-2 mb-0">Abre Yape y escanea este QR.</p>
                                                                                    </div>
                                                                                    <div class="checkout-account__data">
                                                                                        <p class="small text-muted mb-3">Comprueba que el nombre del destinatario coincida antes de pagar.</p>
                                                                                        <div class="checkout-bcp-data">
                                                                                            <div class="checkout-bcp-data__row">
                                                                                                <span class="text-start">
                                                                                                    <span class="checkout-bcp-data__label">Total exacto</span>
                                                                                                    <strong class="checkout-qr-preview__amount"><span data-checkout-qr-total>{{ \App\Models\Ajuste::formatear_precio($subtotal_acumulado + $costoEnvioInicial) }}</span></strong>
                                                                                                </span>
                                                                                            </div>
                                                                                            <div class="checkout-bcp-data__row">
                                                                                                <span class="text-start">
                                                                                                    <span class="checkout-bcp-data__label">Titular</span>
                                                                                                    <strong>{{ $ajuste->web_yape_titular }}</strong>
                                                                                                </span>
                                                                                            </div>
                                                                                            <div class="checkout-bcp-data__row">
                                                                                                <span class="text-start">
                                                                                                    <span class="checkout-bcp-data__label">Número: <strong>{{ $ajuste->web_yape_numero }}</strong></span>
                                                                                                </span>
                                                                                                <button type="button" class="btn btn-sm btn-outline-primary" data-copy-payment-number="{{ $ajuste->web_yape_numero }}" aria-label="Copiar número de Yape">
                                                                                                    <i class="bi bi-copy" aria-hidden="true"></i><span class="visually-hidden">Copiar</span>
                                                                                                </button>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        @endif
                                                                        @if ($plinDisponible)
                                                                            <div data-checkout-qr="plin" @if ($metodoSolicitado !== 'plin') hidden @endif>
                                                                                <div class="checkout-account">
                                                                                    <div class="text-center">
                                                                                        <h3 class="h6 fw-bold mb-2">Escanea el QR de Plin</h3>
                                                                                        <div class="checkout-qr-preview__image-wrap">
                                                                                            <img class="checkout-qr-preview__image" src="{{ asset('storage/'.ltrim($ajuste->web_plin_qr, '/')) }}"
                                                                                                alt="Código QR oficial de Plin de {{ $ajuste->web_plin_titular }}" loading="eager">
                                                                                        </div>
                                                                                        <p class="small text-muted mt-2 mb-0">Abre Plin y escanea este QR.</p>
                                                                                    </div>
                                                                                    <div class="checkout-account__data">
                                                                                        <p class="small text-muted mb-3">Comprueba que el nombre del destinatario coincida antes de pagar.</p>
                                                                                        <div class="checkout-bcp-data">
                                                                                            <div class="checkout-bcp-data__row">
                                                                                                <span class="text-start">
                                                                                                    <span class="checkout-bcp-data__label">Total exacto</span>
                                                                                                    <strong class="checkout-qr-preview__amount"><span data-checkout-qr-total>{{ \App\Models\Ajuste::formatear_precio($subtotal_acumulado + $costoEnvioInicial) }}</span></strong>
                                                                                                </span>
                                                                                            </div>
                                                                                            <div class="checkout-bcp-data__row">
                                                                                                <span class="text-start">
                                                                                                    <span class="checkout-bcp-data__label">Titular</span>
                                                                                                    <strong>{{ $ajuste->web_plin_titular }}</strong>
                                                                                                </span>
                                                                                            </div>
                                                                                            <div class="checkout-bcp-data__row">
                                                                                                <span class="text-start">
                                                                                                    <span class="checkout-bcp-data__label">Número: <strong>{{ $ajuste->web_plin_numero }}</strong></span>
                                                                                                </span>
                                                                                                <button type="button" class="btn btn-sm btn-outline-primary" data-copy-payment-number="{{ $ajuste->web_plin_numero }}" aria-label="Copiar número de Plin">
                                                                                                    <i class="bi bi-copy" aria-hidden="true"></i><span class="visually-hidden">Copiar</span>
                                                                                                </button>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        @endif
                                                                        @if ($bcpDisponible)
                                                                            <div data-checkout-qr="bcp" @if ($metodoSolicitado !== 'bcp') hidden @endif>
                                                                                <div class="checkout-account">
                                                                                    <div class="text-center text-md-start">
                                                                                        <h3 class="h6 fw-bold mb-2">Datos para tu transferencia BCP</h3>
                                                                                        <p class="small text-muted mb-3">Copia la CCI, transfiere el total exacto desde tu app bancaria y verifica que el titular mostrado coincida antes de confirmar.</p>
                                                                                    </div>
                                                                                    <div class="checkout-account__data">
                                                                                        <div class="checkout-bcp-data">
                                                                                            <div class="checkout-bcp-data__row">
                                                                                                <span class="text-start">
                                                                                                    <span class="checkout-bcp-data__label">Total exacto</span>
                                                                                                    <strong class="checkout-qr-preview__amount"><span data-checkout-qr-total>{{ \App\Models\Ajuste::formatear_precio($subtotal_acumulado + $costoEnvioInicial) }}</span></strong>
                                                                                                </span>
                                                                                            </div>
                                                                                            <div class="checkout-bcp-data__row">
                                                                                                <span class="text-start">
                                                                                                    <span class="checkout-bcp-data__label">CCI (código interbancario)</span>
                                                                                                    <strong>{{ $ajuste->web_bcp_cci }}</strong>
                                                                                                </span>
                                                                                                <button type="button" class="btn btn-sm btn-outline-primary" data-copy-payment-number="{{ $ajuste->web_bcp_cci }}" aria-label="Copiar CCI de BCP">
                                                                                                    <i class="bi bi-copy" aria-hidden="true"></i><span class="visually-hidden">Copiar CCI</span>
                                                                                                </button>
                                                                                            </div>
                                                                                            @if (filled($ajuste->web_bcp_cuenta))
                                                                                                <div class="checkout-bcp-data__row">
                                                                                                    <span class="text-start">
                                                                                                        <span class="checkout-bcp-data__label">Cuenta corriente BCP (opcional)</span>
                                                                                                        <strong>{{ $ajuste->web_bcp_cuenta }}</strong>
                                                                                                    </span>
                                                                                                    <button type="button" class="btn btn-sm btn-outline-primary" data-copy-payment-number="{{ $ajuste->web_bcp_cuenta }}" aria-label="Copiar número de cuenta BCP">
                                                                                                        <i class="bi bi-copy" aria-hidden="true"></i><span class="visually-hidden">Copiar cuenta</span>
                                                                                                    </button>
                                                                                                </div>
                                                                                            @endif
                                                                                            <div class="checkout-bcp-data__row">
                                                                                                <span class="text-start">
                                                                                                    <span class="checkout-bcp-data__label">Titular de la cuenta</span>
                                                                                                    <strong>{{ $ajuste->web_bcp_titular }}</strong>
                                                                                                </span>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        @endif
                                                                        @if ($tarjetaDisponible)
                                                                            {{-- Misma forma que la maqueta de «Datos de la tarjeta»: lo que no va
                            dentro son los cuatro campos. Un número escrito en esta página
                            obliga a la tienda a auditar PCI DSS; el dato lo pide Mercado Pago. --}}
                                                                            <div data-checkout-qr="tarjeta" @if ($metodoSolicitado !== 'tarjeta') hidden @endif>
                                                                                <div class="checkout-account">
                                                                                    <div class="text-center text-md-start">
                                                                                        <h3 class="h6 fw-bold mb-2"><i class="bi bi-credit-card me-1" aria-hidden="true"></i>Datos de la tarjeta</h3>
                                                                                        <p class="small text-muted mb-3">Completa el pago en la pantalla segura de Mercado Pago. Aquí solo eliges el método: la tienda no ve ni guarda el número, la fecha ni el CVV de tu tarjeta.</p>
                                                                                        <span class="checkout-qr-preview__amount">Total a pagar: <span data-checkout-qr-total>{{ \App\Models\Ajuste::formatear_precio($subtotal_acumulado + $costoEnvioInicial) }}</span></span>
                                                                                    </div>
                                                                                    <div class="checkout-account__data" style="max-width: 420px;">
                                                                                        <div class="border rounded-3 p-3 mb-2 text-start bg-white">
                                                                                            <span class="checkout-bcp-data__label">Tarjetas aceptadas</span>
                                                                                            <strong class="d-block">Visa (débito o crédito)</strong>
                                                                                            <small class="text-muted">Mercado Pago muestra además otras tarjetas y medios disponibles.</small>
                                                                                        </div>
                                                                                        <div class="border rounded-3 p-3 text-start bg-white">
                                                                                            <span class="checkout-bcp-data__label">Si la tarjeta es rechazada</span>
                                                                                            <strong class="d-block">Tu pedido queda guardado y puedes reintentar</strong>
                                                                                            <small class="text-muted">También puedes elegir Yape, Plin o BCP desde tu pedido.</small>
                                                                                        </div>
                                                                                        <div class="d-flex align-items-center justify-content-between gap-2 mt-3">
                                                                                            <span class="d-inline-flex align-items-center gap-2">
                                                                                                <img src="{{ asset('assets/img/pagos/visa.svg') }}" width="31" height="22" alt="Visa">
                                                                                            </span>
                                                                                            <span class="small text-muted"><i class="bi bi-lock-fill me-1" aria-hidden="true"></i>Pago seguro</span>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        @endif
                                                                        <div class="small mt-2 text-center" id="payment-copy-feedback" role="status" aria-live="polite"></div>
                                                                    </div>
                                                                @endif
                                                                @if (! $yapeDisponible && ! $plinDisponible && ! $bcpDisponible && ! $tarjetaDisponible)
                                                                    <div class="alert alert-warning py-2 px-3 mb-0 small" role="status">
                                                                        Yape, Plin y BCP estarán disponibles cuando la tienda configure los datos de cada método.
                                                                    </div>
                                                                @else
                                                                    <div class="alert alert-info small mb-0 @if ($metodoSolicitado === 'tarjeta') d-none @endif" id="payment-note-manual" role="status">
                                                                        <i class="bi bi-info-circle me-1"></i>El pago no se confirma automáticamente: al crear el pedido adjuntas tu comprobante y la tienda verifica el abono antes de marcarlo como pagado.
                                                                    </div>
                                                                    @if ($tarjetaDisponible)
                                                                        <div class="alert alert-info small mb-0 @if ($metodoSolicitado !== 'tarjeta') d-none @endif" id="payment-note-tarjeta" role="status">
                                                                            <i class="bi bi-credit-card me-1"></i>Al crear el pedido te llevamos a Mercado Pago, que es donde escribes tu tarjeta. La tienda no ve ni guarda sus datos y el pedido queda pagado en el momento.
                                                                        </div>
                                                                    @endif
                                                                @endif
                                                            </div>

                                                            <div class="d-flex justify-content-between gap-2 mt-4">
                                                                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-checkout-previous="2">
                                                                    <i class="bi bi-arrow-left me-1"></i>Volver
                                                                </button>
                                                                @php
                                                                    // Sin JavaScript este botón es el único que ve el cliente: su texto
                                                                    // debe prometer exactamente lo que pasará con el método elegido.
                                                                    $montoCheckout = \App\Models\Ajuste::formatear_precio($subtotal_acumulado + $costoEnvioInicial);
                                                                    $etiquetaInicial = match ($metodoSolicitado) {
                                                                        'tarjeta' => '<i class="bi bi-credit-card me-1"></i>Pagar ' . $montoCheckout . ' con tarjeta',
                                                                        'yape', 'plin', 'bcp' => '<i class="bi bi-arrow-repeat me-1"></i>Crear pedido y subir comprobante',
                                                                        default => '<i class="bi bi-lock me-1"></i>Elige un método de pago',
                                                                    };
                                                                @endphp
                                                                <button type="submit" class="btn btn-primary rounded-pill px-4" id="btn-submit-checkout"
                                                                    @disabled(! $yapeDisponible && ! $plinDisponible && ! $bcpDisponible && ! $tarjetaDisponible)>
                                                                    {!! $etiquetaInicial !!}
                                                                </button>
                                                            </div>

                                                            <p class="small text-muted text-center mt-3 mb-0">
                                                                <i class="bi bi-shield-lock me-1" aria-hidden="true"></i>Tu pedido queda guardado aunque un pago falle; nunca se te cobra dos veces.
                                                                @if ($tarjetaDisponible)
                                                                    <span class="d-inline-flex align-items-center justify-content-center gap-2 mt-2">
                                                                        <img src="{{ asset('assets/img/pagos/visa.svg') }}" width="31" height="22" alt="Visa">
                                                                        <span>Procesado por Mercado Pago</span>
                                                                    </span>
                                                                @endif
                                                            </p>
                                                        </section>
                                                    </div>
                                                </div>
                                            </form>
                                        @endif
                </div>

                <div class="col-lg-4 mt-4 mt-lg-0" data-aos="fade-up" data-aos-delay="300">
                    <div class="cart-summary">
                        <h4 class="summary-title">Resumen del pedido</h4>

                        <div class="border-bottom mb-3 pb-2" style="max-height: 260px; overflow-y: auto;" aria-live="polite" aria-label="Productos del pedido">
                            @foreach ($carritos as $carrito)
                                @php
                                    $precioResumen = $carrito->producto->en_oferta && $carrito->producto->precio_oferta > 0
                                        ? $carrito->producto->precio_oferta
                                        : $carrito->producto->precio_venta;
                                @endphp
                                <div class="d-flex align-items-center gap-2 py-2">
                                    <img src="{{ $carrito->producto->imagen_principal_url }}"
                                        alt="" width="48" height="48"
                                        class="rounded border bg-white" style="object-fit: contain"
                                        onerror="this.onerror=null;this.src='{{ asset('storage/productos/default.webp') }}';">
                                    <div class="flex-grow-1 min-w-0">
                                        <span class="small fw-semibold d-block text-truncate">{{ $carrito->producto->nombre }}</span>
                                        <small class="text-muted"><span data-cart-summary-quantity="{{ $carrito->id }}">{{ $carrito->cantidad }}</span> un.</small>
                                    </div>
                                    <span class="small fw-bold text-nowrap" data-cart-summary-total="{{ $carrito->id }}">
                                        {{ \App\Models\Ajuste::formatear_precio($precioResumen * $carrito->cantidad) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>

                        <div class="summary-item {{ $subtotal_original_acumulado > $subtotal_acumulado ? '' : 'd-none' }}" id="subtotal-original-container">
                            <span class="summary-label">Antes de descuentos</span>
                            <span class="summary-value" id="subtotal-original-display">{{ \App\Models\Ajuste::formatear_precio($subtotal_original_acumulado) }}</span>
                        </div>

                        <div class="summary-item text-danger {{ $subtotal_original_acumulado > $subtotal_acumulado ? '' : 'd-none' }}" id="descuento-container">
                            <span class="summary-label text-danger">Descuento Aplicado</span>
                            <span class="summary-value" id="descuento-display">-{{ \App\Models\Ajuste::formatear_precio($subtotal_original_acumulado - $subtotal_acumulado) }}</span>
                        </div>

                        <div class="summary-item bg-light p-2 rounded-2 my-2">
                            <span class="summary-label fw-bold">Subtotal</span>
                            <span class="summary-value fw-bold text-dark" id="subtotal-display">{{ \App\Models\Ajuste::formatear_precio($subtotal_acumulado) }}</span>
                        </div>

                        <div class="summary-item shipping-item">
                            <span class="summary-label">Costo de Envío</span>
                            <span class="summary-value text-dark fw-medium" id="shipping-display">
                                {{ $costoEnvioInicial === 0 ? 'Gratis' : \App\Models\Ajuste::formatear_precio($costoEnvioInicial) }}
                            </span>
                        </div>
                        <p class="small mb-2 {{ $carritos->isEmpty() ? 'd-none' : ($envioGratis ? 'text-success' : 'text-muted') }}" id="shipping-message" aria-live="polite">
                            @if ($carritos->isNotEmpty())
                                @if ($envioGratis)
                                    <i class="bi bi-check-circle me-1"></i>Tu pedido tiene envío estándar gratis.
                                @else
                                    Te faltan {{ \App\Models\Ajuste::formatear_precio($reglasEnvio['umbral_gratis'] - $subtotal_acumulado) }} para obtener envío gratis.
                                @endif
                            @endif
                        </p>
                        <div class="summary-total border-top pt-3" aria-live="polite">
                            <span class="summary-label">Total a pagar</span>
                            <span class="summary-value" id="total-display">{{ \App\Models\Ajuste::formatear_precio($subtotal_acumulado + $costoEnvioInicial) }}</span>
                        </div>

                        <div class="alert alert-success text-center py-2 px-3 my-3 fw-bold rounded-3 {{ $subtotal_original_acumulado > $subtotal_acumulado ? '' : 'd-none' }}" id="saving-badge" style="font-size: 13px;">
                            <i class="bi bi-gift-fill me-1"></i> ¡Has ahorrado <span id="saving-badge-amount">{{ \App\Models\Ajuste::formatear_precio($subtotal_original_acumulado - $subtotal_acumulado) }}</span>
                        </div>


                        <div class="continue-shopping">
                            <a href="{{ url('/') }}" class="btn btn-link w-100">
                                <i class="bi bi-arrow-left"></i> Continuar comprando
                            </a>
                        </div>

                        <div class="payment-methods">
                            <p class="payment-title">Métodos de pago aceptados</p>
                            <div class="payment-icons">
                                @if ($yapeDisponible)
                                    <span class="badge bg-light text-dark border" data-payment-badge="yape">Yape</span>
                                @endif
                                @if ($bcpDisponible)
                                    <span class="badge bg-light text-dark border" data-payment-badge="bcp">BCP</span>
                                @endif
                                @if ($plinDisponible)
                                    <span class="badge bg-light text-dark border" data-payment-badge="plin">Plin</span>
                                @endif
                                @if ($tarjetaDisponible)
                                    <span class="badge bg-light text-dark border" data-payment-badge="tarjeta">Visa</span>
                                @endif
                                @if (! $yapeDisponible && ! $plinDisponible && ! $bcpDisponible && ! $tarjetaDisponible)
                                    <span class="small text-muted">Aún no configurados</span>
                                @endif
                            </div>
                        </div>

                        <div class="d-flex align-items-start gap-2 border rounded-3 p-3 mt-3 bg-light">
                            <i class="bi bi-shield-check text-primary fs-5" aria-hidden="true"></i>
                            <div>
                                <span class="d-block small fw-bold">Compra segura</span>
                                <span class="d-block small text-muted">Tu información está protegida y solo se usa para procesar tu pedido.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Modal: Register Express Address -->
        <div class="modal fade" id="expressAddressModal" tabindex="-1" aria-labelledby="expressAddressModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content text-start">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="expressAddressModalLabel">Agregar nueva dirección</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="expressAddressForm">
                        @csrf
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="alias_checkout" class="form-label fw-bold">Alias (ej: Casa, Trabajo)</label>
                                    <input type="text" name="alias" id="alias_checkout" class="form-control" placeholder="ej: Casa, Oficina" required>
                                </div>
                                <div class="col-12">
                                    <label for="pais_id_checkout" class="form-label fw-bold">País</label>
                                    <select name="pais_id" id="pais_id_checkout" class="form-select" onchange="loadDepartmentsCheckout()" required>
                                        <option value="">-- Seleccionar país --</option>
                                        @foreach ($paises as $p)
                                            <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="departamento_id_checkout" class="form-label fw-bold">Departamento</label>
                                    <select name="departamento_id" id="departamento_id_checkout" class="form-select" onchange="loadProvinciasCheckout()" required disabled>
                                        <option value="">-- Primero elija país --</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="provincia_id_checkout" class="form-label fw-bold">Provincia</label>
                                    <select name="provincia_id" id="provincia_id_checkout" class="form-select" required disabled>
                                        <option value="">-- Primero elija depto --</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="direccion_exacta_checkout" class="form-label fw-bold">Dirección Exacta</label>
                                    <input type="text" name="direccion_exacta" id="direccion_exacta_checkout" class="form-control" placeholder="ej: Av. Larco 123" required>
                                </div>
                                <div class="col-12">
                                    <label for="referencia_checkout" class="form-label fw-bold">Referencia (opcional)</label>
                                    <input type="text" name="referencia" id="referencia_checkout" class="form-control" placeholder="ej: Portón negro, frente al parque">
                                </div>
                                <div class="col-md-6">
                                    <label for="codigo_postal_checkout" class="form-label fw-bold">Código Postal (opcional)</label>
                                    <input type="text" name="codigo_postal" id="codigo_postal_checkout" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label for="telefono_checkout" class="form-label fw-bold">Teléfono (opcional)</label>
                                    <input type="text" name="telefono" id="telefono_checkout" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary" id="saveExpressAddressBtn">Guardar y Seleccionar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Formularios de eliminación individuales fuera del formulario principal para evitar anidación de forms -->
        @foreach ($carritos as $carrito)
            <form action="{{ route('web.carrito.destroy', $carrito->id) }}" method="post"
                id="delete-form-{{ $carrito->id }}" style="display: none;">
                @csrf
                @method('DELETE')
            </form>
        @endforeach

        <script>
            function loadDepartmentsCheckout() {
                const paisId = document.getElementById('pais_id_checkout').value;
                const deptSelect = document.getElementById('departamento_id_checkout');
                const provSelect = document.getElementById('provincia_id_checkout');

                deptSelect.innerHTML = '<option value="">Cargando...</option>';
                deptSelect.disabled = true;
                provSelect.innerHTML = '<option value="">-- Primero elija depto --</option>';
                provSelect.disabled = true;

                if (!paisId) {
                    deptSelect.innerHTML = '<option value="">-- Primero elija país --</option>';
                    return;
                }

                fetch(`{{ url('/api/paises') }}/${paisId}/departamentos`)
                    .then(response => response.json())
                    .then(data => {
                        deptSelect.innerHTML = '<option value="">-- Seleccionar departamento --</option>';
                        data.forEach(dept => {
                            const option = document.createElement('option');
                            option.value = dept.id;
                            option.textContent = dept.nombre;
                            deptSelect.appendChild(option);
                        });
                        deptSelect.disabled = false;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            }

            function loadProvinciasCheckout() {
                const deptId = document.getElementById('departamento_id_checkout').value;
                const provSelect = document.getElementById('provincia_id_checkout');

                provSelect.innerHTML = '<option value="">Cargando...</option>';
                provSelect.disabled = true;

                if (!deptId) {
                    provSelect.innerHTML = '<option value="">-- Primero elija depto --</option>';
                    return;
                }

                fetch(`{{ url('/api/departamentos') }}/${deptId}/provincias`)
                    .then(response => response.json())
                    .then(data => {
                        provSelect.innerHTML = '<option value="">-- Seleccionar provincia --</option>';
                        data.forEach(prov => {
                            const option = document.createElement('option');
                            option.value = prov.id;
                            option.textContent = prov.nombre;
                            provSelect.appendChild(option);
                        });
                        provSelect.disabled = false;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            }

            // AJAX form submission for checkout address
            document.getElementById('expressAddressForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const saveBtn = document.getElementById('saveExpressAddressBtn');
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';

                const formData = new FormData(this);

                fetch(`{{ route('web.direcciones.storeExpress') }}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
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
                        throw new Error('El servidor devolvió una respuesta no válida (HTML).');
                    }
                })
                .then(res => {
                    if (!res) return;
                    saveBtn.disabled = false;
                    saveBtn.innerText = 'Guardar y Seleccionar';

                    if (res.success) {
                        // Close modal
                        const modalEl = document.getElementById('expressAddressModal');
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        modal.hide();

                        // Reset form
                        document.getElementById('expressAddressForm').reset();
                        document.getElementById('departamento_id_checkout').disabled = true;
                        document.getElementById('provincia_id_checkout').disabled = true;

                        // Add new option to dropdown and select it
                        const select = document.getElementById('direccion_id_select');
                        const opt = document.createElement('option');
                        opt.value = res.direccion.id;
                        opt.textContent = `[${res.direccion.alias}] ${res.direccion.direccion_exacta} (${res.direccion.provincia.nombre}, ${res.direccion.departamento.nombre})`;
                        opt.selected = true;

                        select.appendChild(opt);
                        select.classList.remove('d-none');
                        select.required = true;

                        // Hide warning alert
                        const warning = document.getElementById('no-addresses-alert');
                        if (warning) warning.classList.add('d-none');

                        Swal.fire({
                            title: '¡Dirección registrada!',
                            text: 'La dirección se ha agregado a tu perfil y seleccionado para este pedido.',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire('Error', res.message || 'No se pudo registrar la dirección.', 'error');
                    }
                })
                .catch(err => {
                    saveBtn.disabled = false;
                    saveBtn.innerText = 'Guardar y Seleccionar';
                    console.error(err);
                    Swal.fire('Error', err.message || 'Hubo un problema al procesar la solicitud.', 'error');
                });
            });

            // Recalculations and quantity actions
            function confirmarEliminarItem(id) {
                Swal.fire({
                    title: '¿Desea eliminar este producto del carrito?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('delete-form-' + id).submit();
                    }
                });
            }

            function confirmarLimpiarCarrito() {
                Swal.fire({
                    title: '¿Desea vaciar el carrito?',
                    text: "Esta acción eliminará todos los productos agregados.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, vaciar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('clear-cart-form').submit();
                    }
                });
            }

            document.addEventListener('DOMContentLoaded', function() {
                const checkoutForm = document.getElementById('checkout-form');
                const checkoutPanels = document.querySelectorAll('[data-checkout-panel]');
                const checkoutIndicators = document.querySelectorAll('[data-checkout-indicator]');
                const currentStepDisplay = document.getElementById('checkout-current-step');
                const noAddressesAlert = document.getElementById('no-addresses-alert');
                let activeCheckoutStep = Number(checkoutForm?.dataset.initialStep || 1);

                function showCheckoutStep(step) {
                    activeCheckoutStep = step;
                    if (currentStepDisplay) currentStepDisplay.textContent = step;
                    checkoutPanels.forEach(panel => {
                        panel.hidden = Number(panel.dataset.checkoutPanel) !== step;
                    });
                    checkoutIndicators.forEach(indicator => {
                        const indicatorStep = Number(indicator.dataset.checkoutIndicator);
                        indicator.classList.toggle('is-active', indicatorStep === step);
                        indicator.classList.toggle('is-complete', indicatorStep < step);
                        const trigger = indicator.querySelector('[data-checkout-go]');
                        if (indicatorStep === step) {
                            trigger?.setAttribute('aria-current', 'step');
                        } else {
                            trigger?.removeAttribute('aria-current');
                        }
                    });

                    const heading = document.getElementById(`checkout-step-${step}-title`);
                    if (heading && window.matchMedia('(max-width: 991px)').matches) {
                        heading.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }

                function navigateToCheckoutStep(targetStep) {
                    if (!Number.isInteger(targetStep) || targetStep < 1 || targetStep > checkoutPanels.length) return;
                    if (targetStep <= activeCheckoutStep) {
                        showCheckoutStep(targetStep);
                        return;
                    }

                    if (activeCheckoutStep <= 1 && targetStep > 1) {
                        const personalFields = [
                            document.getElementById('checkout-customer-name'),
                            document.getElementById('checkout-customer-dni'),
                            document.getElementById('checkout-customer-phone'),
                            document.getElementById('checkout-privacy-consent'),
                        ];
                        const firstInvalidField = personalFields.find(field => field && !field.checkValidity());
                        if (firstInvalidField) {
                            showCheckoutStep(1);
                            firstInvalidField.reportValidity();
                            firstInvalidField.focus();
                            return;
                        }
                    }

                    if (targetStep > 2 && (!addressSelect || !addressSelect.value)) {
                        showCheckoutStep(2);
                        noAddressesAlert?.classList.remove('d-none');
                        noAddressesAlert?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        return;
                    }

                    noAddressesAlert?.classList.add('d-none');
                    showCheckoutStep(targetStep);
                }

                document.querySelectorAll('[data-checkout-go]').forEach(button => {
                    button.addEventListener('click', () => {
                        navigateToCheckoutStep(Number(button.dataset.checkoutGo));
                    });
                });

                document.querySelectorAll('[data-checkout-next]').forEach(button => {
                    button.addEventListener('click', () => {
                        navigateToCheckoutStep(Number(button.dataset.checkoutNext));
                    });
                });

                document.querySelectorAll('[data-checkout-previous]').forEach(button => {
                    button.addEventListener('click', () => {
                        showCheckoutStep(Number(button.dataset.checkoutPrevious));
                    });
                });

                showCheckoutStep(activeCheckoutStep);

                let quantityUpdateTimer;
                function saveQuantityChange() {
                    window.clearTimeout(quantityUpdateTimer);
                    quantityUpdateTimer = window.setTimeout(() => {
                        document.getElementById('form-actualizar-carrito')?.requestSubmit();
                    }, 250);
                }

                // Handle increase qty click
                document.querySelectorAll('.increase-qty').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const id = this.getAttribute('data-id');
                        const input = document.getElementById('quantity-input-' + id);
                        if (input) {
                            const current = parseInt(input.value) || 1;
                            const max = parseInt(input.getAttribute('max')) || 999;
                            if (current < max) {
                                input.value = current + 1;
                                saveQuantityChange();
                            }
                        }
                    });
                });

                // Handle decrease qty click
                document.querySelectorAll('.decrease-qty').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const id = this.getAttribute('data-id');
                        const input = document.getElementById('quantity-input-' + id);
                        if (input) {
                            const current = parseInt(input.value) || 1;
                            if (current > 1) {
                                input.value = current - 1;
                                saveQuantityChange();
                            }
                        }
                    });
                });

                document.querySelectorAll('.quantity-input').forEach(input => {
                    input.addEventListener('change', () => {
                        const min = parseInt(input.min, 10) || 1;
                        const max = parseInt(input.max, 10) || 999;
                        const quantity = parseInt(input.value, 10);
                        input.value = Math.min(max, Math.max(min, Number.isNaN(quantity) ? min : quantity));
                        saveQuantityChange();
                    });
                });

                // Pricing parameters
                let subtotalAcumulado = parseFloat("{{ $subtotal_acumulado }}");
                const divisaSimbolo = "{{ $ajuste->moneda_simbolo ?? 'S/.' }}";
                const divisaPosicion = "{{ $ajuste->moneda_posicion ?? 'antes' }}";
                const divisaDecimales = parseInt("{{ $ajuste->moneda_decimales ?? 2 }}");

                function formatearPrecioJS(monto) {
                    const [entero, decimales] = parseFloat(monto).toFixed(divisaDecimales).split('.');
                    const miles = entero.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                    const formateado = decimales ? miles + '.' + decimales : miles;
                    if (divisaPosicion === 'antes') {
                        return divisaSimbolo + ' ' + formateado;
                    } else {
                        return formateado + ' ' + divisaSimbolo;
                    }
                }

                const shippingRadios = document.querySelectorAll('.shipping-radio');
                const deliveryRadios = document.querySelectorAll('input[name="modalidad_entrega"]');
                const hiddenTotalInput = document.getElementById('hidden-total');
                const hiddenShippingOption = document.getElementById('hidden-shipping-option');
                const shippingDisplay = document.getElementById('shipping-display');
                const shippingMessage = document.getElementById('shipping-message');
                const totalDisplay = document.getElementById('total-display');
                const shippingOptionsContainer = document.getElementById('shipping-options-container');
                const shippingAddressContainer = document.getElementById('shipping-address-container');
                const addressSelect = document.getElementById('direccion_id_select');

                const standardRadio = document.getElementById('standard_shipping');
                const expressRadio = document.getElementById('express_shipping');
                const standardPriceLabel = document.querySelector('[data-shipping-standard-price]');
                const umbralHint = document.querySelector('[data-shipping-umbral-hint]');
                const reglasEnvio = @json($reglasEnvio);

                const paymentOptions = document.querySelectorAll('input[name="metodo_pago"]');
                const btnSubmitCheckout = document.getElementById('btn-submit-checkout');
                const checkoutQrPreview = document.getElementById('checkout-qr-preview');
                const checkoutQrPanels = document.querySelectorAll('[data-checkout-qr]');
                const checkoutQrTotals = document.querySelectorAll('[data-checkout-qr-total]');
                const paymentCopyFeedback = document.getElementById('payment-copy-feedback');

                const mensajeCopiado = (texto, variante = 'text-muted') => {
                    if (!paymentCopyFeedback) return;

                    paymentCopyFeedback.textContent = texto;
                    paymentCopyFeedback.className = `small mt-2 text-center ${variante}`;
                    window.clearTimeout(paymentCopyFeedback.dataset.tiempo);
                    paymentCopyFeedback.dataset.tiempo = window.setTimeout(() => {
                        paymentCopyFeedback.textContent = '';
                        paymentCopyFeedback.className = 'small mt-2 text-center';
                    }, 4000);
                };

                const marcarCopiado = (button) => {
                    const icono = button.querySelector('i');
                    const etiqueta = button.querySelector('.visually-hidden');
                    if (!icono || button.dataset.copiado === '1') return;

                    button.dataset.copiado = '1';
                    icono.className = 'bi bi-check-lg';
                    button.classList.remove('btn-outline-primary');
                    button.classList.add('btn-primary');
                    if (etiqueta) etiqueta.textContent = 'Copiado';

                    window.setTimeout(() => {
                        icono.className = 'bi bi-copy';
                        button.classList.remove('btn-primary');
                        button.classList.add('btn-outline-primary');
                        if (etiqueta) etiqueta.textContent = 'Copiar';
                        button.dataset.copiado = '0';
                    }, 1800);
                };

                document.querySelectorAll('[data-copy-payment-number]').forEach(button => {
                    button.addEventListener('click', async () => {
                        if (!navigator.clipboard || !window.isSecureContext) {
                            mensajeCopiado('Tu navegador no permite copiar automáticamente: selecciona el dato y cópialo a mano.', 'small mt-2 text-center text-warning');
                            return;
                        }

                        try {
                            await navigator.clipboard.writeText(button.dataset.copyPaymentNumber);
                            marcarCopiado(button);
                            mensajeCopiado('Copiado. Verifica el titular antes de confirmar la transferencia.', 'small mt-2 text-center text-success');
                        } catch (error) {
                            mensajeCopiado('No se pudo copiar. Selecciona el dato y cópialo manualmente.', 'small mt-2 text-center text-warning');
                        }
                    });
                });

                function updatePaymentOptionAction() {
                    const paymentMethod = document.querySelector('input[name="metodo_pago"]:checked')?.value;
                    if (checkoutQrPreview) {
                        checkoutQrPreview.classList.toggle('d-none', !paymentMethod);
                        checkoutQrPanels.forEach(panel => {
                            panel.hidden = panel.dataset.checkoutQr !== paymentMethod;
                        });
                    }

                    document.querySelectorAll('[data-payment-badge]').forEach(badge => {
                        badge.classList.toggle('is-selected', badge.dataset.paymentBadge === paymentMethod);
                    });

                    const noteManual = document.getElementById('payment-note-manual');
                    const noteTarjeta = document.getElementById('payment-note-tarjeta');
                    if (noteManual) {
                        noteManual.classList.toggle('d-none', paymentMethod === 'tarjeta');
                    }
                    if (noteTarjeta) {
                        noteTarjeta.classList.toggle('d-none', paymentMethod !== 'tarjeta');
                    }

                    if (!btnSubmitCheckout) return;

                    // El importe del botón es el que realmente se enviará: hidden-total
                    // lo escribe recalculateCart() justo antes de llegar aquí.
                    const montoBoton = parseFloat(hiddenTotalInput?.value || '0') || 0;
                    let etiqueta = '<i class="bi bi-exclamation-circle me-1"></i> Elige un método de pago';
                    if (paymentMethod === 'tarjeta') {
                        etiqueta = `<i class="bi bi-credit-card me-1"></i> Pagar ${formatearPrecioJS(montoBoton)} con tarjeta`;
                    } else if (paymentMethod) {
                        etiqueta = '<i class="bi bi-arrow-repeat me-1"></i> Crear pedido y subir comprobante';
                    }

                    btnSubmitCheckout.disabled = paymentOptions.length === 0 || !paymentMethod;
                    btnSubmitCheckout.innerHTML = etiqueta;
                    btnSubmitCheckout.className = 'btn btn-primary rounded-pill px-4';
                }

                function recalculateCart() {
                    if (!checkoutForm) return;

                    let shippingCost = 0;
                    let selectedOption = 'standard';
                    const envioEstandarGratis = subtotalAcumulado >= reglasEnvio.umbral_gratis;

                    let deliveryMode = 'envio';
                    deliveryRadios.forEach(radio => {
                        if (radio.checked) {
                            deliveryMode = radio.value;
                        }
                    });

                    if (deliveryMode === 'recojo') {
                        if (shippingOptionsContainer) shippingOptionsContainer.style.display = 'none';
                        if (shippingAddressContainer) shippingAddressContainer.style.display = 'block';
                        shippingCost = 0;
                        selectedOption = 'pickup';
                    } else {
                        if (shippingOptionsContainer) shippingOptionsContainer.style.display = 'block';
                        if (shippingAddressContainer) shippingAddressContainer.style.display = 'block';

                        if (standardPriceLabel) {
                            standardPriceLabel.textContent = envioEstandarGratis
                                ? 'Gratis'
                                : formatearPrecioJS(reglasEnvio.costo_estandar);
                        }
                        if (umbralHint) umbralHint.classList.toggle('d-none', envioEstandarGratis);

                        if (expressRadio && expressRadio.checked) {
                            shippingCost = parseFloat(reglasEnvio.costo_express);
                            selectedOption = 'express';
                        } else {
                            if (standardRadio) standardRadio.checked = true;
                            shippingCost = envioEstandarGratis ? 0 : parseFloat(reglasEnvio.costo_estandar);
                            selectedOption = envioEstandarGratis ? 'free' : 'standard';
                        }
                    }

                    if (shippingDisplay) {
                        shippingDisplay.innerText = shippingCost > 0 ? formatearPrecioJS(shippingCost) : 'Gratis';
                    }

                    if (shippingMessage) {
                        shippingMessage.classList.remove('text-success', 'text-muted', 'text-info');

                        if (deliveryMode === 'recojo') {
                            shippingMessage.textContent = 'Sin costo de envío al recoger en tienda.';
                            shippingMessage.classList.add('text-info');
                        } else if (selectedOption === 'express') {
                            shippingMessage.textContent = envioEstandarGratis
                                ? 'Elegiste envío urgente: tu pedido paga solo el urgente, el estándar ya era gratis.'
                                : 'Envío urgente para que llegue antes.';
                            shippingMessage.classList.add('text-muted');
                        } else if (envioEstandarGratis) {
                            shippingMessage.textContent = 'Tu pedido tiene envío estándar gratis.';
                            shippingMessage.classList.add('text-success');
                        } else {
                            shippingMessage.textContent = `Te faltan ${formatearPrecioJS(reglasEnvio.umbral_gratis - subtotalAcumulado)} para obtener envío gratis.`;
                            shippingMessage.classList.add('text-muted');
                        }
                    }

                    const total = subtotalAcumulado + shippingCost;
                    if (totalDisplay) {
                        totalDisplay.innerText = formatearPrecioJS(total);
                    }
                    checkoutQrTotals.forEach(amount => {
                        amount.textContent = formatearPrecioJS(total);
                    });

                    if (hiddenTotalInput) hiddenTotalInput.value = total.toFixed(2);
                    if (hiddenShippingOption) hiddenShippingOption.value = selectedOption;

                    updatePaymentOptionAction();
                }

                if (shippingRadios.length > 0) {
                    shippingRadios.forEach(radio => {
                        radio.addEventListener('change', recalculateCart);
                    });
                }

                if (deliveryRadios.length > 0) {
                    deliveryRadios.forEach(radio => {
                        radio.addEventListener('change', recalculateCart);
                    });
                }

                paymentOptions.forEach(radio => {
                    radio.addEventListener('change', updatePaymentOptionAction);
                });

                document.querySelectorAll('[data-checkout-change-method]').forEach(button => {
                    button.addEventListener('click', () => {
                        const selected = document.querySelector('input[name="metodo_pago"]:checked');
                        if (selected) {
                            selected.checked = false;
                            updatePaymentOptionAction();
                        }
                        paymentOptions[0]?.focus();
                        document.getElementById('payment-method-label')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    });
                });

                recalculateCart();

                if (checkoutForm && btnSubmitCheckout) {
                    checkoutForm.addEventListener('submit', function() {
                        if (!document.querySelector('input[name="metodo_pago"]:checked')) {
                            return;
                        }
                        btnSubmitCheckout.disabled = true;
                        btnSubmitCheckout.setAttribute('aria-busy', 'true');
                        btnSubmitCheckout.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Procesando pago...';
                    });
                }

                // AJAX Form Submit for updating cart quantities
                const formActualizar = document.getElementById('form-actualizar-carrito');
                if (formActualizar) {
                    let cartUpdateInProgress = false;
                    const updateCartButton = document.querySelector('[form="form-actualizar-carrito"]');
                    const updateCartButtonLabel = updateCartButton?.innerHTML;

                    formActualizar.addEventListener('submit', function(e) {
                        e.preventDefault();
                        if (cartUpdateInProgress) return;

                        cartUpdateInProgress = true;
                        if (updateCartButton) {
                            updateCartButton.disabled = true;
                            updateCartButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Guardando...';
                        }
                        document.querySelectorAll('.quantity-input, .quantity-btn').forEach(control => {
                            control.disabled = true;
                        });

                        const formData = new FormData(this);

                        fetch(this.action, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: formData
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
                                if (res.status === 401 || res.status === 419 || res.redirected) {
                                    window.location.href = '{{ route("web.login") }}';
                                    return;
                                }
                                throw new Error('El servidor devolvió una respuesta no válida (HTML).');
                            }
                        })
                        .then(data => {
                            if (!data) return;
                            if (data.success) {
                                Toast.fire({
                                    icon: 'success',
                                    title: data.mensaje || 'Carrito actualizado correctamente'
                                });

                                // Update item quantities, totals, and savings in the DOM
                                data.items.forEach(item => {
                                    const input = document.getElementById('quantity-input-' + item.id);
                                    if (input) {
                                        input.value = item.cantidad;
                                        const summaryQuantity = document.querySelector(`[data-cart-summary-quantity="${item.id}"]`);
                                        const summaryTotal = document.querySelector(`[data-cart-summary-total="${item.id}"]`);
                                        if (summaryQuantity) summaryQuantity.textContent = item.cantidad;
                                        if (summaryTotal) summaryTotal.textContent = item.subtotal_item_formateado;

                                        const row = input.closest('.row');
                                        if (row) {
                                            const itemTotalDisplay = row.querySelector('.item-total');
                                            if (itemTotalDisplay) {
                                                let html = `<span class="fw-bold fs-5 text-dark">${item.subtotal_item_formateado}</span>`;
                                                if (item.ahorro_item > 0) {
                                                    html += `<small class="text-success d-block fw-semibold" style="font-size: 11px;">Ahorras ${item.ahorro_item_formateado}</small>`;
                                                }
                                                itemTotalDisplay.innerHTML = html;
                                            }
                                        }
                                    }
                                });

                                // Update cart counters
                                const cartCounters = document.querySelectorAll('.cart-counter');
                                cartCounters.forEach(c => c.textContent = `(${data.contador})`);

                                // Update summary subtotals
                                const subtotalOriginalDisplay = document.getElementById('subtotal-original-display');
                                const subtotalOriginalContainer = document.getElementById('subtotal-original-container');
                                if (subtotalOriginalDisplay) {
                                    subtotalOriginalDisplay.textContent = data.subtotal_original_acumulado_formateado;
                                }

                                const subtotalDisplay = document.getElementById('subtotal-display');
                                if (subtotalDisplay) {
                                    subtotalDisplay.textContent = data.subtotal_acumulado_formateado;
                                }

                                // Update discount container and saving badge
                                const discountContainer = document.getElementById('descuento-container');
                                const discountDisplay = document.getElementById('descuento-display');
                                const savingBadge = document.getElementById('saving-badge');
                                const savingBadgeAmount = document.getElementById('saving-badge-amount');
                                if (subtotalOriginalContainer) {
                                    subtotalOriginalContainer.classList.toggle(
                                        'd-none',
                                        data.subtotal_original_acumulado <= data.subtotal_acumulado
                                    );
                                }

                                const diff = data.subtotal_original_acumulado - data.subtotal_acumulado;
                                if (diff > 0) {
                                    if (discountContainer) {
                                        discountContainer.classList.remove('d-none');
                                    }
                                    if (discountDisplay) {
                                        const diffFormateada = formatearPrecioJS(diff);
                                        discountDisplay.textContent = '-' + diffFormateada;
                                    }
                                    if (savingBadge) {
                                        savingBadge.classList.remove('d-none');
                                    }
                                    if (savingBadgeAmount) {
                                        savingBadgeAmount.textContent = formatearPrecioJS(diff);
                                    }
                                } else {
                                    if (discountContainer) {
                                        discountContainer.classList.add('d-none');
                                    }
                                    if (savingBadge) {
                                        savingBadge.classList.add('d-none');
                                    }
                                }

                                // Update the JS global variable
                                subtotalAcumulado = data.subtotal_acumulado;

                                // Call recalculateCart to update standard/express/free shipping and total to pay
                                recalculateCart();

                                // Update mini cart dropdown dynamically
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
                                    title: data.mensaje || 'Error al actualizar el carrito.'
                                });
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            Toast.fire({
                                icon: 'error',
                                title: err.mensaje || err.message || 'Ocurrió un error al actualizar el carrito.'
                            });
                        })
                        .finally(() => {
                            cartUpdateInProgress = false;
                            if (updateCartButton) {
                                updateCartButton.disabled = false;
                                updateCartButton.innerHTML = updateCartButtonLabel;
                            }
                            document.querySelectorAll('.quantity-input, .quantity-btn').forEach(control => {
                                control.disabled = false;
                            });
                        });
                    });
                }
            });
        </script>
    </section><!-- /Cart Section -->
@endsection
