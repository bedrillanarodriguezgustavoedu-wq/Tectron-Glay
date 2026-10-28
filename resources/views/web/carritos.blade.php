@extends('layouts.web')
@section("meta_robots", "noindex, follow")
@section('content')
    @php
        $yapeDisponible = \App\Models\Ajuste::motivoParaOcultarPagoWeb($ajuste, 'yape') === null;
        $plinDisponible = \App\Models\Ajuste::motivoParaOcultarPagoWeb($ajuste, 'plin') === null;
        // Decisión del dueño (2026-10-21): la transferencia BCP ya no se ofrece en
        // el carrito. Los datos siguen en Ajustes y el candado
        // `motivoParaOcultarPagoWeb` queda intacto, así que volver a ofrecerla es
        // cambiar este `false`; los pedidos ya pagados por BCP se siguen viendo en
        // el panel.
        $bcpDisponible = false;
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
            position: relative;
            color: #7b8492;
            z-index: 0;
        }

        .checkout-stepper .checkout-step:not(:last-child)::after {
            content: "";
            position: absolute;
            top: 19px;
            left: calc(50% + 25px);
            width: calc(100% - 50px);
            border-top: 2px dashed #ced4da;
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

        /* El paso recorrido se pinta de verde y cambia el número por una palomita.
           El número sigue en el DOM, a tamaño cero, para que un lector de pantalla
           todavía pueda decir «paso 1» y no solo «paso, completado». */
        .checkout-stepper .checkout-step.is-complete {
            color: #198754;
        }

        .checkout-stepper .checkout-step.is-complete .checkout-step-number {
            color: #fff;
            background: #198754;
            font-size: 0;
        }

        .checkout-stepper .checkout-step.is-complete .checkout-step-number::after {
            content: "\2713";
            font-size: 1.15rem;
            line-height: 1;
        }

        /* El paso en curso es el único que se ve en rojo fuerte: es donde está el cliente. */
        .checkout-stepper .checkout-step.is-active {
            color: #dc3545;
        }

        .checkout-stepper .checkout-step.is-active .checkout-step-number {
            color: #fff;
            background: #dc3545;
        }

        .checkout-stepper .checkout-step.is-complete::after {
            border-top-color: #198754;
        }

        .checkout-stepper .checkout-step:not(.is-active):not(.is-complete) .checkout-step-number {
            color: #adb5bd;
            background: #f1f3f5;
        }

        /* Un paso verde se puede volver a tocar para corregir datos; el actual no,
           porque ya lo estás viendo. */
        .checkout-stepper .checkout-step.is-complete .checkout-step-trigger:hover .checkout-step-number {
            background: #157347;
        }

        .checkout-stepper .checkout-step.is-complete .checkout-step-trigger:hover .checkout-step-label {
            text-decoration: underline;
        }

        .checkout-stepper .checkout-step.is-active .checkout-step-trigger {
            cursor: default;
        }

        /* El tema fuerza `color: var(--color-primary) !important` sobre cualquier
           .btn-outline-primary (layouts/web.blade.php:128), así que la opción
           seleccionada quedaba con letra azul sobre fondo azul: el cliente veía un
           bloque vacío y no sabía qué modalidad había elegido. */
        .btn-check:checked + .btn-outline-primary {
            background-color: var(--color-primary, #0d6efd) !important;
            border-color: var(--color-primary, #0d6efd) !important;
            color: #fff !important;
        }

        .punto-recojo-card {
            background: var(--bs-primary-bg-subtle, #eef6ff);
            border: 1px solid var(--color-primary, #0d6efd);
            border-radius: .75rem;
        }

        .punto-recojo-opcion {
            background: #fff;
            border: 1px solid var(--bs-border-color, #dee2e6);
            border-radius: .75rem;
            padding: .75rem;
            cursor: pointer;
        }

        .btn-check:checked + .punto-recojo-opcion {
            background: var(--bs-primary-bg-subtle, #eef6ff);
            border-color: var(--color-primary, #0d6efd);
        }

        .btn-check:focus-visible + .punto-recojo-opcion {
            outline: 2px solid var(--color-primary, #0d6efd);
            outline-offset: 2px;
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

        /* Elselector de medios de pago va en lista, una fila por método:
           el botón de radio a la izquierda, el logo en medio y arriba el nombre
           con su subtítulo. Se apila aunque quepa espacio: así se compara de un vistazo. */
        .checkout-method-grid {
            display: grid;
            gap: .6rem;
            grid-template-columns: 1fr;
        }

        .checkout-method-card {
            position: relative;
            display: grid;
            grid-template-columns: auto auto minmax(0, 1fr);
            column-gap: .85rem;
            row-gap: .1rem;
            align-items: center;
            margin: 0;
            padding: .85rem 1rem;
            text-align: start;
            border: 1.5px solid #e4eaf2;
            border-radius: .9rem;
            background: #fff;
            cursor: pointer;
            transition: border-color .2s ease, background-color .2s ease, box-shadow .2s ease;
        }

        .checkout-method-card--proximamente {
            cursor: default;
            border-style: dashed;
            background: #f8fafc;
            opacity: .78;
            grid-template-columns: auto minmax(0, 1fr);
        }

        .checkout-method-card--proximamente:hover {
            border-color: #e4eaf2;
            box-shadow: none;
        }

        .checkout-method-card__badge {
            position: absolute;
            top: 50%;
            right: 1rem;
            transform: translateY(-50%);
            font-size: .64rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: #64748b;
            background: #e9eef5;
            border-radius: 999px;
            padding: .12rem .5rem;
        }

        .checkout-method-card__badge--rapido {
            color: #0a5c36;
            background: #e3f6ec;
        }

        /* Cualquier etiqueta del lado derecho necesita aire: si no, se come el
           subtítulo del método en pantallas angostas. */
        .checkout-method-card:has(.checkout-method-card__badge) {
            padding-right: 8.5rem;
        }

        .checkout-method-card .form-check-input {
            grid-column: 1;
            grid-row: 1 / span 2;
            float: none;
            margin: 0;
            width: 1.2rem;
            height: 1.2rem;
        }

        .checkout-method-card:hover {
            border-color: #b9cdf7;
        }

        .checkout-method-card:has(input:checked) {
            border-color: #0d6efd;
            background-color: #f4f8ff;
            box-shadow: inset .3rem 0 0 #0d6efd, 0 .5rem 1.25rem rgba(13, 110, 253, .14);
        }

        .checkout-method-card:has(input:focus-visible) {
            outline: 3px solid rgba(13, 110, 253, .35);
            outline-offset: 3px;
        }

        .checkout-method-card__icon {
            display: grid;
            place-items: center;
            grid-column: 2;
            grid-row: 1 / span 2;
            width: 44px;
            height: 44px;
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
            grid-column: 3;
            grid-row: 1;
            font-weight: 700;
        }

        .checkout-method-card__hint {
            grid-column: 3;
            grid-row: 2;
            font-size: .75rem;
            color: #6c757d;
        }

        /* La fila bloqueada no tiene radio: el logo pasa al primer espacio. */
        .checkout-method-card--proximamente .checkout-method-card__icon {
            grid-column: 1;
        }

        .checkout-method-card--proximamente .checkout-method-card__title,
        .checkout-method-card--proximamente .checkout-method-card__hint {
            grid-column: 2;
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

        /* El panel de datos es el que decide si el QR va al lado: se consulta su
           propio ancho porque el carrito se angosta antes que la ventana. */
        .checkout-qr-preview {
            container-type: inline-size;
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

        /* El QR sube como foto de pantalla: el cuadro entero se ve grande pero el
           código adentro es chico, así que agrandarlo no es un adorno. */
        /* El tema ya fija `button:not(:disabled) { cursor: pointer }`, así que
           sin el :not() esta regla empataría y perdería por orden de carga. */
        button.checkout-qr-preview__image-wrap:not(:disabled) {
            cursor: zoom-in;
            line-height: 0;
        }

        button.checkout-qr-preview__image-wrap:focus-visible {
            outline: 2px solid var(--color-primary, #0d6efd);
            outline-offset: 2px;
        }

        /* El QR real sube como foto de pantalla vertical: si se fuerza un cuadro
           1:1, la foto se encoge para caber y el código se ve más chico que la
           miniatura de abajo. Se deja que use el alto disponible. */
        .qr-zoom__image {
            width: auto;
            height: auto;
            max-width: min(100%, 460px);
            max-height: 78vh;
            padding: .75rem;
            border: 1px solid #edf0f4;
            border-radius: .85rem;
            background: #fff;
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

        /* El QR sube como foto de pantalla: si la columna se deja en `auto`, el
           navegador la mide por el ancho natural de la foto (900px) y le roba
           todo el sitio al texto, que queda a una letra por línea. */
        .checkout-quickpay {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.25rem;
            align-items: center;
        }

        /* 600px es lo mínimo para que el texto respire al lado del QR de 280px;
           por debajo, el QR sube arriba y todo queda a una columna. */
        @container (min-width: 600px) {
            .checkout-quickpay {
                grid-template-columns: minmax(0, 1fr) minmax(0, 280px);
            }
        }

        @container (max-width: 599.98px) {
            .checkout-quickpay__qr,
            .checkout-quickpay__aside {
                order: -1;
            }
        }

        .checkout-quickpay__titulo {
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--color-primary, #0d6efd);
            margin: 0;
        }

        .checkout-quickpay__guia {
            font-size: .9rem;
            color: #495057;
            margin: .35rem 0 0;
        }

        .checkout-quickpay__divide {
            display: flex;
            align-items: center;
            gap: .6rem;
            margin: .95rem 0;
            font-size: .78rem;
            color: #6c757d;
        }

        .checkout-quickpay__divide::before,
        .checkout-quickpay__divide::after {
            content: "";
            flex: 1;
            height: 1px;
            background: #e4eaf2;
        }

        .checkout-quickpay__aviso {
            display: flex;
            gap: .5rem;
            align-items: flex-start;
            padding: .6rem .75rem;
            margin: 0 0 .9rem;
            border: 1px solid #ffe9b3;
            border-radius: .75rem;
            background: #fff8e6;
            color: #7a5b00;
            font-size: .8rem;
        }

        .checkout-quickpay__qr {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .5rem;
            min-width: 0;
        }

        .checkout-quickpay__qr .checkout-qr-preview__image-wrap {
            max-width: 100%;
        }

        /* Dentro del panel de pago las filas aprovechan todo el ancho: el tope de
           420px era para la maqueta vieja de una sola columna. */
        .checkout-quickpay .checkout-bcp-data {
            max-width: none;
        }

        .checkout-quickpay__specs {
            width: 100%;
            max-width: 280px;
            display: grid;
            gap: .4rem;
            margin: 0;
            padding: 0;
            list-style: none;
            font-size: .78rem;
            color: #495057;
        }

        .checkout-quickpay__specs li {
            display: flex;
            gap: .45rem;
            align-items: baseline;
        }

        .checkout-quickpay__aside {
            display: grid;
            gap: .75rem;
            justify-items: center;
            align-content: start;
            min-width: 0;
        }

        .checkout-quickpay__marca {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .5rem .85rem;
            border: 1px solid #e8edf4;
            border-radius: 999px;
            background: #fff;
            box-shadow: 0 .5rem 1.5rem rgba(31, 45, 61, .08);
            font-size: .8rem;
            color: #495057;
        }

        .checkout-quickpay__pasos {
            width: 100%;
            max-width: 280px;
            display: grid;
            gap: .6rem;
            padding: .9rem 1rem;
            border: 1px solid #e4eaf2;
            border-radius: .9rem;
            background: #fff;
        }

        .checkout-quickpay__pasos-titulo {
            font-size: .75rem;
            color: #6c757d;
            margin: 0;
        }

        .checkout-quickpay__paso {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            gap: .6rem;
            align-items: start;
            font-size: .8rem;
            color: #495057;
        }

        .checkout-quickpay__paso b {
            display: grid;
            place-items: center;
            width: 22px;
            height: 22px;
            border-radius: 999px;
            background: #eef4ff;
            color: #174ea6;
            font-size: .72rem;
        }

        /* El botón de siempre: si el panel de datos crece, el cliente pierde de
           vista lo que paga, así que la orden de pago se queda pegada abajo. */
        .checkout-cta {
            position: sticky;
            bottom: .75rem;
            z-index: 5;
            margin-top: 1rem;
            padding: .5rem;
            border-radius: 1.15rem;
            background: rgba(255, 255, 255, .94);
            box-shadow: 0 .6rem 1.6rem rgba(31, 45, 61, .16);
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
                            // El umbral de envío gratis mira el subtotal ANTES del cupón:
                            // si no, aplicar un descuento podría encarecer el pedido.
                            $envioGratis = $subtotal_acumulado >= $reglasEnvio['umbral_gratis'];
                            $costoEnvioInicial = $carritos->isNotEmpty() && ! $envioGratis
                                ? $reglasEnvio['costo_estandar']
                                : 0;
                            $descuentoCupon = (float) ($cupon['descuento'] ?? 0);
                            $porcentajeCupon = (int) ($cupon['porcentaje'] ?? 0);
                            $totalInicial = round($subtotal_acumulado - $descuentoCupon + $costoEnvioInicial, 2);
                            // Si el envío volvió con un error, la modalidad elegida
                            // no puede reiniciarse a «envío» sin avisar al cliente.
                            $modalidadActual = old('modalidad_entrega', 'envio');
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
                                                data-initial-step="{{ $errors->hasAny(['name', 'dni', 'telefono', 'privacy_consent']) ? 1 : ($errors->hasAny(['direccion_id', 'modalidad_entrega']) ? 2 : ($errors->any() ? 3 : 1)) }}">
                                                @csrf
                                                <input type="hidden" name="total" id="hidden-total" value="{{ $totalInicial }}">
                                                <input type="hidden" name="shipping_option" id="hidden-shipping-option" value="{{ $costoEnvioInicial === 0 ? 'free' : 'standard' }}">

                                                <div class="card border-0 shadow-sm rounded-4">
                                                    <div class="card-body p-3 p-lg-4">
                                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                                                            <div>
                                                                <h2 class="h4 fw-bold mb-1">Finaliza tu compra</h2>
                                                                <p class="text-muted small mb-0">Completa los pasos para confirmar tu pedido. Los campos marcados con <span class="text-danger">*</span> son obligatorios.</p>
                                                            </div>
                                                            <span class="badge rounded-pill bg-light text-primary border px-3 py-2">
                                                                Paso <span id="checkout-current-step">1</span> de 3
                                                            </span>
                                                        </div>
                                                        <nav aria-label="Pasos de compra" class="checkout-stepper mb-4">
                                                            <ol class="list-unstyled d-flex align-items-start mb-0">
                                                                @foreach (['Identificación', 'Entrega', 'Pago'] as $stepIndex => $stepLabel)
                                                                    <li class="checkout-step flex-fill text-center"
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
                                                                    <div class="form-check border rounded-3 p-3 ps-5 @error('privacy_consent') border-danger @enderror">
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
                                                                    Continuar con la entrega <i class="bi bi-arrow-right ms-1"></i>
                                                                </button>
                                                            </div>
                                                        </section>

                                                        <section class="checkout-step-panel" id="checkout-panel-2" data-checkout-panel="2" aria-labelledby="checkout-step-2-title" hidden>
                                                            <h2 id="checkout-step-2-title" class="h4 fw-bold mb-2">
                                                                <i class="bi bi-truck me-2 text-primary"></i>Entrega de Productos
                                                            </h2>
                                                            <div class="border rounded-4 p-3 p-lg-4 mb-4">
                                                            <p class="fw-semibold mb-3">¿Cómo deseas que te entreguen los productos?</p>

                                                            <div class="mb-4">
                                                                <span class="summary-label fw-semibold d-block mb-2">Modalidad de entrega</span>
                                                                <div class="btn-group w-100 flex-column flex-lg-row" role="group" aria-label="Modalidad de entrega">
                                                                    <input type="radio" class="btn-check" name="modalidad_entrega" id="modalidad_envio" value="envio" @checked($modalidadActual === 'envio') autocomplete="off">
                                                                    <label class="btn btn-outline-primary py-2" for="modalidad_envio">
                                                                        <i class="bi bi-truck me-1"></i> Envío a domicilio
                                                                    </label>
                                                                    <input type="radio" class="btn-check" name="modalidad_entrega" id="modalidad_recojo" value="recojo" @checked($modalidadActual === 'recojo') autocomplete="off">
                                                                    <label class="btn btn-outline-primary py-2" for="modalidad_recojo">
                                                                        <i class="bi bi-shop me-1"></i> Recojo en tienda
                                                                    </label>
                                                                    <input type="radio" class="btn-check" name="modalidad_entrega" id="modalidad_provincia" value="provincia" @checked($modalidadActual === 'provincia') autocomplete="off">
                                                                    <label class="btn btn-outline-primary py-2" for="modalidad_provincia">
                                                                        <i class="bi bi-box-seam me-1"></i> Envío a provincias
                                                                    </label>
                                                                </div>
                                                                <div class="alert alert-info small mb-0 mt-3 {{ $modalidadActual === 'provincia' ? '' : 'd-none' }}" id="provincia-note" role="note">
                                                                    Despachamos a tu ciudad por agencia. <strong>El costo del envío a provincias se coordina contigo por WhatsApp o teléfono antes de despachar</strong>, y el pedido no sale hasta que lo confirmes.
                                                                </div>
                                                                @php
                                                                    // Con un solo punto no tiene sentido obligar a elegir; con
                                                                    // varios, el cliente decide dónde pasa a retirar.
                                                                    $puntosDisponibles = $puntosRecojo ?? collect();
                                                                    $puntoElegido = (int) old('punto_recojo_id', $puntosDisponibles->count() === 1 ? $puntosDisponibles->first()->id : 0);
                                                                @endphp
                                                                <div class="mt-3 {{ $modalidadActual === 'recojo' ? '' : 'd-none' }}" id="punto-recojo-card">
                                                                    @if ($puntosDisponibles->isNotEmpty())
                                                                        <span class="summary-label fw-semibold d-block mb-2">Elige dónde recoger</span>
                                                                        <div class="row g-2">
                                                                            @foreach ($puntosDisponibles as $punto)
                                                                                <div class="col-12 col-lg-6">
                                                                                    <input class="btn-check" type="radio" name="punto_recojo_id" id="punto-recojo-{{ $punto->id }}" value="{{ $punto->id }}" autocomplete="off" @checked($puntoElegido === $punto->id)>
                                                                                    <label class="punto-recojo-opcion w-100" for="punto-recojo-{{ $punto->id }}">
                                                                                        <span class="fw-semibold d-block">{{ $punto->nombre }}</span>
                                                                                        <span class="small d-block">{{ $punto->direccion }}</span>
                                                                                        @if (filled($punto->horario))
                                                                                            <span class="small text-muted d-block"><i class="bi bi-clock me-1"></i>{{ $punto->horario }}</span>
                                                                                        @endif
                                                                                        @if (filled($punto->telefono))
                                                                                            <span class="small text-muted d-block"><i class="bi bi-telephone me-1"></i>{{ $punto->telefono }}</span>
                                                                                        @endif
                                                                                    </label>
                                                                                </div>
                                                                            @endforeach
                                                                        </div>
                                                                        <div class="alert alert-warning py-2 px-3 mt-2 d-none" id="punto-recojo-alert" role="alert">
                                                                            Elige en qué punto quieres recoger tu pedido.
                                                                        </div>
                                                                        <p class="mb-0 small text-muted mt-2">Te avisamos por WhatsApp cuando el pedido esté listo para recoger.</p>
                                                                    @else
                                                                        <div class="punto-recojo-card p-3">
                                                                            <p class="fw-semibold mb-1"><i class="bi bi-shop me-1 text-primary"></i>Punto de recojo</p>
                                                                            @if (filled($ajuste?->direccion))
                                                                                <p class="mb-1">{{ $ajuste->direccion }}</p>
                                                                            @else
                                                                                <p class="mb-1 text-warning">La tienda aún no publicó su dirección de recojo: te la confirmamos por WhatsApp al aprobar el pedido.</p>
                                                                            @endif
                                                                            <p class="mb-0 small text-muted">Te avisamos por WhatsApp cuando el pedido esté listo para recoger.</p>
                                                                            @if (filled($ajuste?->telefono))
                                                                                <p class="mb-0 small text-muted">Teléfono de contacto: <strong>{{ $ajuste->telefono }}</strong>.</p>
                                                                            @endif
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            </div>

                                                            <div class="mb-4" id="shipping-address-container" style="display: {{ $modalidadActual === 'recojo' ? 'none' : 'block' }}">
                                                                <label for="direccion_id_select" class="summary-label fw-semibold mb-2 d-block">Dirección de destino <span class="text-danger">*</span></label>
                                                                <p class="small text-muted mb-2" id="shipping-address-hint">Usamos esta dirección para despachar tu pedido.</p>
                                                                <div id="no-addresses-alert" class="alert alert-warning py-2 px-3 my-2 {{ $direcciones->isNotEmpty() ? 'd-none' : '' }}" style="font-size: 13px;">
                                                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>Registra y selecciona una dirección antes de continuar.
                                                                </div>
                                                                <select class="form-select {{ $direcciones->isEmpty() ? 'd-none' : '' }}" name="direccion_id" id="direccion_id_select" @required($direcciones->isNotEmpty())>
                                                                    @foreach ($direcciones as $dir)
                                                                        <option value="{{ $dir->id }}" data-provincia-id="{{ $dir->provincia_id }}" {{ $dir->es_predeterminada ? 'selected' : '' }}>
                                                                            [{{ $dir->alias }}] {{ $dir->direccion_exacta }} ({{ $dir->provincia->nombre }}, {{ $dir->departamento->nombre }})
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 mt-2"
                                                                    data-bs-toggle="modal" data-bs-target="#expressAddressModal">
                                                                    <i class="bi bi-plus-circle me-1"></i>Agregar dirección
                                                                </button>

                                                                @php
                                                                    // Las agencias dependen de la ciudad del pedido, así que
                                                                    // se pintan todas y el JS deja visible solo el grupo de
                                                                    // la provincia elegida. Sin agencias en esa provincia,
                                                                    // el bloque entero no existe y el flujo no cambia nada.
                                                                    $agenciasDisponibles = $agencias ?? collect();
                                                                    $agenciaElegida = (int) old('agencia_provincia_id', 0);
                                                                @endphp
                                                                @if ($agenciasDisponibles->isNotEmpty())
                                                                    <div class="mt-3 d-none" id="agencias-provincia-card">
                                                                        @foreach ($agenciasDisponibles as $provinciaId => $agenciasDeProvincia)
                                                                            <div class="agencias-provincia-grupo d-none" data-provincia-id="{{ $provinciaId }}">
                                                                                <span class="summary-label fw-semibold d-block mb-2">Elige dónde recoger en {{ $agenciasDeProvincia->first()->provincia?->nombre }}</span>
                                                                                <div class="row g-2">
                                                                                    @foreach ($agenciasDeProvincia as $agencia)
                                                                                        <div class="col-12 col-lg-6">
                                                                                            <input class="btn-check" type="radio" name="agencia_provincia_id" id="agencia-{{ $agencia->id }}" value="{{ $agencia->id }}" autocomplete="off" @checked($agenciaElegida === $agencia->id)>
                                                                                            <label class="punto-recojo-opcion w-100" for="agencia-{{ $agencia->id }}">
                                                                                                <span class="fw-semibold d-block">{{ $agencia->nombre }}</span>
                                                                                                <span class="small d-block">{{ $agencia->direccion }}</span>
                                                                                                @if (filled($agencia->horario))
                                                                                                    <span class="small text-muted d-block"><i class="bi bi-clock me-1"></i>{{ $agencia->horario }}</span>
                                                                                                @endif
                                                                                                @if (filled($agencia->telefono))
                                                                                                    <span class="small text-muted d-block"><i class="bi bi-telephone me-1"></i>{{ $agencia->telefono }}</span>
                                                                                                @endif
                                                                                            </label>
                                                                                        </div>
                                                                                    @endforeach
                                                                                </div>
                                                                            </div>
                                                                        @endforeach
                                                                        <div class="alert alert-warning py-2 px-3 mt-2 d-none" id="agencia-provincia-alert" role="alert">
                                                                            Elige en qué agencia de tu ciudad quieres recoger tu pedido.
                                                                        </div>
                                                                        <div class="alert alert-warning py-2 px-3 mt-2 d-none" id="agencia-provincia-nota" role="note">
                                                                            Aún no tenemos agencia en la ciudad de esa dirección: el despacho se coordina contigo por WhatsApp antes de enviar.
                                                                        </div>
                                                                        <p class="mb-0 small text-muted mt-2">La agencia guarda tu pedido hasta que pases a buscarlo; te avisamos por WhatsApp cuando llegue.</p>
                                                                    </div>
                                                                @endif
                                                            </div>

                                                            <div class="mb-3" id="shipping-options-container" style="display: {{ $modalidadActual === 'envio' ? 'block' : 'none' }}">
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

                                                            </div>

                                                            <div class="d-flex flex-wrap justify-content-between gap-2 mt-4">
                                                                <button type="button" class="btn btn-link text-muted px-0" data-checkout-previous="1">
                                                                    <i class="bi bi-arrow-left me-1"></i>Volver a mis datos
                                                                </button>
                                                                <button type="button" class="btn btn-primary rounded-pill px-4" data-checkout-next="3">
                                                                    Continuar con el pago <i class="bi bi-arrow-right ms-1"></i>
                                                                </button>
                                                            </div>
                                                        </section>

                                                        <section class="checkout-step-panel" id="checkout-panel-3" data-checkout-panel="3" aria-labelledby="checkout-step-3-title" hidden>
                                                            <h2 id="checkout-step-3-title" class="h4 fw-bold mb-2">
                                                                <i class="bi bi-credit-card me-2 text-primary"></i>Forma de Pago
                                                            </h2>
                                                            <div class="border rounded-4 p-3 p-lg-4 mb-4">
                                                            <p class="fw-semibold mb-2">¿Deseas pagar online o con efectivo/transferencia?</p>
                                                            <p class="text-muted small mb-4">Con Yape o Plin transfiere siempre el total exacto, verifica el titular y adjunta tu comprobante al crear el pedido. Con tarjeta el pago lo confirma Mercado Pago solo, sin comprobantes.</p>
                                                            <div class="mb-4" id="payment-options-container" role="radiogroup" aria-labelledby="payment-method-label">
                                                                <span class="summary-label fw-semibold d-block mb-2" id="payment-method-label">Elige un medio de pago</span>
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
                                                                            <span class="checkout-method-card__badge checkout-method-card__badge--rapido">El más rápido</span>
                                                                            <span class="checkout-method-card__icon checkout-method-card__icon--marca" aria-hidden="true"><img src="{{ asset('assets/img/pagos/visa.svg') }}" width="31" height="22" alt=""></span>
                                                                            <span class="checkout-method-card__title">Tarjeta Visa · crédito o débito</span>
                                                                            <small class="checkout-method-card__hint">Confirmación automática por Mercado Pago</small>
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
                                                                                <div class="checkout-quickpay">
                                                                                    <div>
                                                                                        <p class="checkout-quickpay__titulo">Paga más rápido</p>
                                                                                        <p class="checkout-quickpay__guia">Escanea este QR con la billetera electrónica de tu banco, desde la app <strong>Yape</strong>.</p>
                                                                                        <div class="checkout-quickpay__divide">o verifica estos datos antes de pagar</div>
                                                                                        <p class="checkout-quickpay__aviso">
                                                                                            <i class="bi bi-shield-check" aria-hidden="true"></i>
                                                                                            <span>Comprueba que el nombre del destinatario coincida antes de pagar.</span>
                                                                                        </p>
                                                                                        <div class="checkout-bcp-data">
                                                                                            <div class="checkout-bcp-data__row">
                                                                                                <span class="text-start">
                                                                                                    <span class="checkout-bcp-data__label">Total exacto</span>
                                                                                                    <strong class="checkout-qr-preview__amount"><span data-checkout-qr-total>{{ \App\Models\Ajuste::formatear_precio($totalInicial) }}</span></strong>
                                                                                                </span>
                                                                                            </div>
                                                                                            <div class="checkout-bcp-data__row">
                                                                                                <span class="text-start">
                                                                                                    <span class="checkout-bcp-data__label">Titular</span>
                                                                                                    <strong>{{ $ajuste->web_yape_titular }}</strong>
                                                                                                </span>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="checkout-quickpay__qr">
                                                                                        <button type="button" class="checkout-qr-preview__image-wrap" data-bs-toggle="modal" data-bs-target="#qrZoomModal" data-qr-zoom="Yape" aria-label="Agrandar el código QR de Yape">
                                                                                            <img class="checkout-qr-preview__image" src="{{ asset('storage/'.ltrim($ajuste->web_yape_qr, '/')) }}"
                                                                                                alt="Código QR oficial de Yape de {{ $ajuste->web_yape_titular }}" loading="eager">
                                                                                        </button>
                                                                                        <p class="small text-muted mb-0">¿No lo puedes leer? Tócalo para agrandarlo.</p>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        @endif
                                                                        @if ($plinDisponible)
                                                                            <div data-checkout-qr="plin" @if ($metodoSolicitado !== 'plin') hidden @endif>
                                                                                <div class="checkout-quickpay">
                                                                                    <div>
                                                                                        <p class="checkout-quickpay__titulo">Paga más rápido</p>
                                                                                        <p class="checkout-quickpay__guia">Escanea este QR con la billetera electrónica de tu banco, desde la app <strong>Plin</strong>.</p>
                                                                                        <div class="checkout-quickpay__divide">o verifica estos datos antes de pagar</div>
                                                                                        <p class="checkout-quickpay__aviso">
                                                                                            <i class="bi bi-shield-check" aria-hidden="true"></i>
                                                                                            <span>Comprueba que el nombre del destinatario coincida antes de pagar.</span>
                                                                                        </p>
                                                                                        <div class="checkout-bcp-data">
                                                                                            <div class="checkout-bcp-data__row">
                                                                                                <span class="text-start">
                                                                                                    <span class="checkout-bcp-data__label">Total exacto</span>
                                                                                                    <strong class="checkout-qr-preview__amount"><span data-checkout-qr-total>{{ \App\Models\Ajuste::formatear_precio($totalInicial) }}</span></strong>
                                                                                                </span>
                                                                                            </div>
                                                                                            <div class="checkout-bcp-data__row">
                                                                                                <span class="text-start">
                                                                                                    <span class="checkout-bcp-data__label">Titular</span>
                                                                                                    <strong>{{ $ajuste->web_plin_titular }}</strong>
                                                                                                </span>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="checkout-quickpay__qr">
                                                                                        <button type="button" class="checkout-qr-preview__image-wrap" data-bs-toggle="modal" data-bs-target="#qrZoomModal" data-qr-zoom="Plin" aria-label="Agrandar el código QR de Plin">
                                                                                            <img class="checkout-qr-preview__image" src="{{ asset('storage/'.ltrim($ajuste->web_plin_qr, '/')) }}"
                                                                                                alt="Código QR oficial de Plin de {{ $ajuste->web_plin_titular }}" loading="eager">
                                                                                        </button>
                                                                                        <p class="small text-muted mb-0">¿No lo puedes leer? Tócalo para agrandarlo.</p>
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
                                                                                                    <strong class="checkout-qr-preview__amount"><span data-checkout-qr-total>{{ \App\Models\Ajuste::formatear_precio($totalInicial) }}</span></strong>
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
                                                                                <div class="checkout-quickpay">
                                                                                    <div>
                                                                                        <p class="checkout-quickpay__titulo">Paga con tu tarjeta</p>
                                                                                        <p class="checkout-quickpay__guia">Al presionar el botón se abre Mercado Pago en esta misma pestaña: allí escribes los datos de tu tarjeta en la pantalla segura de la pasarela.</p>
                                                                                        <div class="checkout-quickpay__divide">aquí no se escribe ninguna tarjeta</div>
                                                                                        <p class="checkout-quickpay__aviso">
                                                                                            <i class="bi bi-shield-check" aria-hidden="true"></i>
                                                                                            <span>La tienda no ve ni guarda el número, la fecha ni el CVV de tu tarjeta.</span>
                                                                                        </p>
                                                                                        <div class="checkout-bcp-data">
                                                                                            <div class="checkout-bcp-data__row">
                                                                                                <span class="text-start">
                                                                                                    <span class="checkout-bcp-data__label">Total a pagar</span>
                                                                                                    <strong class="checkout-qr-preview__amount"><span data-checkout-qr-total>{{ \App\Models\Ajuste::formatear_precio($totalInicial) }}</span></strong>
                                                                                                </span>
                                                                                            </div>
                                                                                            <div class="checkout-bcp-data__row">
                                                                                                <span class="text-start">
                                                                                                    <span class="checkout-bcp-data__label">Si la tarjeta es rechazada</span>
                                                                                                    <strong>Tu pedido queda guardado y puedes reintentar</strong>
                                                                                                    <small class="text-muted d-block">También puedes elegir Yape o Plin desde tu pedido.</small>
                                                                                                </span>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="checkout-quickpay__aside">
                                                                                        <span class="checkout-quickpay__marca">
                                                                                            <img src="{{ asset('assets/img/pagos/visa.svg') }}" width="31" height="22" alt="Visa">
                                                                                            <span><i class="bi bi-lock-fill me-1" aria-hidden="true"></i>Pago seguro</span>
                                                                                        </span>
                                                                                        <div class="checkout-quickpay__pasos">
                                                                                            <p class="checkout-quickpay__pasos-titulo">Así funciona el pago con tarjeta</p>
                                                                                            <p class="checkout-quickpay__paso"><b>1</b><span>Presionas el botón y te llevamos a Mercado Pago.</span></p>
                                                                                            <p class="checkout-quickpay__paso"><b>2</b><span>Allí escribes tu tarjeta: la tienda nunca la ve.</span></p>
                                                                                            <p class="checkout-quickpay__paso"><b>3</b><span>Si la aprueban, el pedido queda pagado al instante.</span></p>
                                                                                        </div>
                                                                                        <ul class="checkout-quickpay__specs">
                                                                                            <li><i class="bi bi-credit-card" aria-hidden="true"></i><span>Tarjetas aceptadas: <strong>Visa</strong> débito o crédito.</span></li>
                                                                                            <li><i class="bi bi-clock" aria-hidden="true"></i><span>El enlace vence en {{ $mercadoPago->minutosVigencia() }} minutos y puedes abrirlo otra vez desde tu pedido.</span></li>
                                                                                        </ul>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        @endif
                                                                        <div class="small mt-2 text-center" id="payment-copy-feedback" role="status" aria-live="polite"></div>
                                                                    </div>
                                                                @endif
                                                                @if (! $yapeDisponible && ! $plinDisponible && ! $bcpDisponible && ! $tarjetaDisponible)
                                                                    <div class="alert alert-warning py-2 px-3 mb-0 small" role="status">
                                                                        Yape y Plin estarán disponibles cuando la tienda configure los datos de cada método.
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
                                                            </div>

                                                            @php
                                                                // Sin JavaScript este botón es el único que ve el cliente: su texto
                                                                // debe prometer exactamente lo que pasará con el método elegido.
                                                                $montoCheckout = \App\Models\Ajuste::formatear_precio($totalInicial);
                                                                $etiquetaInicial = match ($metodoSolicitado) {
                                                                    'tarjeta' => '<i class="bi bi-credit-card me-1"></i>Pagar ' . $montoCheckout . ' con tarjeta',
                                                                    'yape', 'plin', 'bcp' => '<i class="bi bi-arrow-repeat me-1"></i>Crear pedido y subir comprobante',
                                                                    default => '<i class="bi bi-lock me-1"></i>Elige un método de pago',
                                                                };
                                                            @endphp
                                                            <div class="checkout-cta">
                                                                <button type="submit" class="btn btn-primary btn-lg rounded-pill py-3 w-100" id="btn-submit-checkout"
                                                                    @disabled(! $yapeDisponible && ! $plinDisponible && ! $bcpDisponible && ! $tarjetaDisponible)>
                                                                    {!! $etiquetaInicial !!}
                                                                </button>
                                                            </div>
                                                            <button type="button" class="btn btn-link text-muted w-100 mt-1" data-checkout-previous="2">
                                                                <i class="bi bi-arrow-left me-1"></i>Volver a mis datos
                                                            </button>

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

                        <div class="coupon-box border rounded-3 p-2 mb-3">
                            <label for="codigo-cupon" class="form-label fw-semibold small mb-1">
                                <i class="bi bi-ticket-perforated me-1 text-primary"></i>Cupón de descuento
                            </label>
                            <form action="{{ route('web.carrito.cupon') }}" method="POST" class="d-flex gap-2" id="form-cupon">
                                @csrf
                                <input type="text" name="codigo" id="codigo-cupon" value="{{ old('codigo_cupon', $cupon['codigo'] ?? '') }}"
                                    class="form-control form-control-sm text-uppercase @error('codigo') is-invalid @enderror"
                                    placeholder="Escribe el código" maxlength="30" autocomplete="off" aria-describedby="cupon-help">
                                <button type="submit" class="btn btn-sm btn-outline-primary rounded-pill px-3 text-nowrap">
                                    {{ ($cupon['codigo'] ?? null) ? 'Actualizar' : 'Aplicar' }}
                                </button>
                                @if (! empty($cupon['codigo']))
                                    <button type="submit" class="btn btn-sm btn-link text-muted text-nowrap" name="quitar" value="1"
                                        aria-label="Quitar el cupón aplicado" title="Quitar el cupón">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                @endif
                            </form>
                            <div id="cupon-help" class="form-text">
                                El descuento baja solo del precio de los productos; el envío se calcula igual.
                            </div>
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

                        <div class="summary-item {{ empty($cupon['codigo']) || $descuentoCupon <= 0 ? 'd-none' : '' }}" id="cupon-container">
                            <span class="summary-label text-danger" id="cupon-label">
                                Descuento (<span id="cupon-codigo">{{ $cupon['codigo'] }}</span>
                                <span id="cupon-porcentaje">−{{ $porcentajeCupon }}%</span>)
                            </span>
                            <span class="summary-value text-danger" id="cupon-display">-{{ \App\Models\Ajuste::formatear_precio($descuentoCupon) }}</span>
                        </div>

                        <div class="summary-item shipping-item">
                            <span class="summary-label" id="shipping-label">{{ $modalidadActual === 'recojo' ? 'Recojo en tienda' : ($modalidadActual === 'provincia' ? 'Envío a provincias' : 'Costo de Envío') }}</span>
                            <span class="summary-value text-dark fw-medium" id="shipping-display">
                                {{ $modalidadActual === 'provincia' ? 'Se coordina' : ($costoEnvioInicial === 0 ? 'Gratis' : \App\Models\Ajuste::formatear_precio($costoEnvioInicial)) }}
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
                        <p class="small mb-2 text-muted d-none" id="delivery-point-line" aria-live="polite"></p>
                        <div class="summary-total border-top pt-3" aria-live="polite">
                            <span class="summary-label">Total a pagar</span>
                            <span class="summary-value" id="total-display">{{ \App\Models\Ajuste::formatear_precio($totalInicial) }}</span>
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

        <!-- Modal: QR agrandado, para escanearlo con el celular desde la pantalla -->
        <div class="modal fade" id="qrZoomModal" tabindex="-1" aria-labelledby="qrZoomModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content text-start">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="qrZoomModalLabel">Código QR</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body text-center">
                        <img id="qr-zoom-image" class="qr-zoom__image" src="" alt="">
                        <p class="small text-muted mt-3 mb-0">Acerca el celular a esta pantalla para escanearlo.</p>
                    </div>
                    <div class="modal-footer justify-content-center border-0">
                        <a id="qr-zoom-open" class="btn btn-sm btn-outline-primary rounded-pill px-3" href="#" target="_blank" rel="noopener">
                            <i class="bi bi-arrows-fullscreen me-1" aria-hidden="true"></i>Abrir imagen completa
                        </a>
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

                const esRecojoEnTienda = () => document.querySelector('input[name="modalidad_entrega"]:checked')?.value === 'recojo';
                const esEnvioProvincia = () => document.querySelector('input[name="modalidad_entrega"]:checked')?.value === 'provincia';

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

                    if (targetStep > 2 && !esRecojoEnTienda() && (!addressSelect || !addressSelect.value)) {
                        // El aviso vive en el panel de entrega: si se deja pasar al
                        // cliente hasta el pago, ya no podría verlo ni corregirlo.
                        noAddressesAlert?.classList.remove('d-none');
                        noAddressesAlert?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        return;
                    }

                    if (targetStep > 2 && esRecojoEnTienda()) {
                        const avisoPunto = document.getElementById('punto-recojo-alert');
                        // Solo cuando hay puntos listados: sin ellos el carrito muestra
                        // la dirección general de Ajustes y no hay nada que elegir.
                        if (document.querySelectorAll('input[name="punto_recojo_id"]').length
                            && !document.querySelector('input[name="punto_recojo_id"]:checked')) {
                            avisoPunto?.classList.remove('d-none');
                            avisoPunto?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            return;
                        }
                    }

                    document.getElementById('punto-recojo-alert')?.classList.add('d-none');

                    if (targetStep > 2 && esEnvioProvincia()) {
                        const avisoAgencia = document.getElementById('agencia-provincia-alert');
                        // Se pregunta por el grupo visible, no por los radios: los de
                        // otras ciudades están desactivados y no aplican a este pedido.
                        const grupoActivo = document.querySelector('.agencias-provincia-grupo:not(.d-none)');
                        if (grupoActivo && !grupoActivo.querySelector('input[name="agencia_provincia_id"]:checked')) {
                            avisoAgencia?.classList.remove('d-none');
                            avisoAgencia?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            return;
                        }
                    }

                    document.getElementById('agencia-provincia-alert')?.classList.add('d-none');

                    noAddressesAlert?.classList.add('d-none');
                    showCheckoutStep(targetStep);
                }

                document.querySelectorAll('[data-checkout-go]').forEach(button => {
                    button.addEventListener('click', () => {
                        navigateToCheckoutStep(Number(button.dataset.checkoutGo));
                    });
                });

                document.querySelectorAll('input[name="punto_recojo_id"]').forEach(radio => {
                    radio.addEventListener('change', () => {
                        document.getElementById('punto-recojo-alert')?.classList.add('d-none');
                        pintarPuntoElegido('recojo');
                    });
                });

                document.querySelectorAll('input[name="agencia_provincia_id"]').forEach(radio => {
                    radio.addEventListener('change', () => {
                        document.getElementById('agencia-provincia-alert')?.classList.add('d-none');
                        pintarPuntoElegido('provincia');
                    });
                });

                document.getElementById('direccion_id_select')?.addEventListener('change', () => {
                    // Cambiar de ciudad cambia las agencias disponibles: si no se
                    // repinta aquí, el cliente elegiría una agencia de otra provincia.
                    recalculateCart();
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
                // El porcentaje viene del servidor, nunca del cliente: el monto real
                // se recalcula otra vez al crear el pedido.
                let cuponCodigo = "{{ $cupon['codigo'] ?? '' }}";
                let cuponPorcentaje = parseInt("{{ $cupon['porcentaje'] ?? 0 }}", 10) || 0;
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
                const shippingLabel = document.getElementById('shipping-label');
                const shippingMessage = document.getElementById('shipping-message');
                const totalDisplay = document.getElementById('total-display');
                const cuponContainer = document.getElementById('cupon-container');
                const cuponDisplay = document.getElementById('cupon-display');
                const cuponCodigoLabel = document.getElementById('cupon-codigo');
                const cuponPorcentajeLabel = document.getElementById('cupon-porcentaje');
                const shippingOptionsContainer = document.getElementById('shipping-options-container');
                const shippingAddressContainer = document.getElementById('shipping-address-container');
                const provinciaNote = document.getElementById('provincia-note');
                const recojoCard = document.getElementById('punto-recojo-card');
                const addressHint = document.getElementById('shipping-address-hint');
                const addressSelect = document.getElementById('direccion_id_select');
                const hayDirecciones = {{ $direcciones->isNotEmpty() ? 'true' : 'false' }};
                const agenciasCard = document.getElementById('agencias-provincia-card');
                const agenciasGrupos = document.querySelectorAll('.agencias-provincia-grupo');
                const agenciaRadios = document.querySelectorAll('input[name="agencia_provincia_id"]');

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

                // El QR se agranda con la imagen que ya está descargada en la
                // página: no se vuelve a pedir el archivo ni se expone otra ruta.
                const qrZoomModal = document.getElementById('qrZoomModal');
                if (qrZoomModal) {
                    document.querySelectorAll('[data-qr-zoom]').forEach(boton => {
                        boton.addEventListener('click', () => {
                            const miniatura = boton.querySelector('img');
                            if (!miniatura) return;

                            const grande = document.getElementById('qr-zoom-image');
                            grande.src = miniatura.src;
                            grande.alt = miniatura.alt;
                            document.getElementById('qr-zoom-open').href = miniatura.src;
                            qrZoomModal.querySelector('.modal-title').textContent = 'Código QR de '+boton.dataset.qrZoom;
                        });
                    });
                }

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
                    btnSubmitCheckout.className = 'btn btn-primary btn-lg rounded-pill py-3 w-100';
                }

                function pintarAgenciasProvincia(mostrar) {
                    if (!agenciasCard) return;

                    const provinciaId = mostrar ? (addressSelect?.selectedOptions[0]?.dataset.provinciaId || '') : '';
                    let hayGrupo = false;

                    agenciasGrupos.forEach(grupo => {
                        const aplica = mostrar && provinciaId !== '' && grupo.dataset.provinciaId === provinciaId;
                        grupo.classList.toggle('d-none', !aplica);
                        hayGrupo = hayGrupo || aplica;

                        // Un radio de otra ciudad, aunque esté oculto, seguiría
                        // viajando en el pedido: desactivado desaparece del envío.
                        grupo.querySelectorAll('input[name="agencia_provincia_id"]').forEach(radio => {
                            radio.disabled = !aplica;
                            if (!aplica) radio.checked = false;
                        });
                    });

                    agenciasCard.classList.toggle('d-none', !hayGrupo);
                    if (!hayGrupo) {
                        document.getElementById('agencia-provincia-alert')?.classList.add('d-none');
                    }
                    // Sin agencia en esa ciudad el bloque desaparecía en silencio y el
                    // cliente leía una pantalla rota: aquí dice qué pasa en su lugar.
                    document.getElementById('agencia-provincia-nota')?.classList.toggle('d-none', !(mostrar && !hayGrupo));
                }

                function pintarPuntoElegido(deliveryMode) {
                    const linea = document.getElementById('delivery-point-line');
                    if (!linea) return;

                    const radio = deliveryMode === 'recojo'
                        ? document.querySelector('input[name="punto_recojo_id"]:checked')
                        : (deliveryMode === 'provincia'
                            ? document.querySelector('input[name="agencia_provincia_id"]:checked')
                            : null);
                    const tarjeta = radio ? document.querySelector('label[for="' + radio.id + '"]') : null;

                    if (!tarjeta) {
                        linea.classList.add('d-none');
                        linea.textContent = '';
                        return;
                    }

                    // El nombre del local va primero y después la dirección: del cartón
                    // del carrito solo interesa eso, no el horario ni el teléfono.
                    const partes = [...tarjeta.querySelectorAll('span')].map(span => span.textContent.trim()).filter(Boolean);
                    linea.textContent = (deliveryMode === 'recojo' ? 'Recojo en ' : 'Agencia ') + partes[0] + (partes[1] ? ' · ' + partes[1] : '');
                    linea.classList.remove('d-none');
                }

                function descuentoCuponActual(subtotal, porcentaje) {
                    if (!porcentaje) return 0;
                    const base = Math.max(0, subtotal);
                    const redondeado = (monto) => Math.round((monto + Number.EPSILON) * 100) / 100;
                    return Math.min(redondeado(base * porcentaje / 100), redondeado(base));
                }

                function recalcularCupon() {
                    const descuento = descuentoCuponActual(subtotalAcumulado, cuponPorcentaje);

                    if (cuponContainer) {
                        cuponContainer.classList.toggle('d-none', !cuponCodigo || descuento <= 0);
                    }
                    if (cuponCodigoLabel) cuponCodigoLabel.textContent = cuponCodigo;
                    if (cuponPorcentajeLabel) cuponPorcentajeLabel.textContent = `−${cuponPorcentaje}%`;
                    if (cuponDisplay) cuponDisplay.textContent = '-' + formatearPrecioJS(descuento);

                    return descuento;
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

                    const muestraDireccion = deliveryMode !== 'recojo';
                    if (shippingAddressContainer) shippingAddressContainer.style.display = muestraDireccion ? 'block' : 'none';
                    if (recojoCard) recojoCard.classList.toggle('d-none', deliveryMode !== 'recojo');
                    if (addressHint) {
                        addressHint.textContent = deliveryMode === 'provincia'
                            ? 'Indica la ciudad donde debe llegar tu pedido: lo despachamos por agencia.'
                            : 'Usamos esta dirección para despachar tu pedido.';
                    }
                    // Un <select> oculto y obligatorio invalida el formulario sin
                    // que el cliente entienda por qué no avanza.
                    if (addressSelect) addressSelect.required = muestraDireccion && hayDirecciones;
                    if (agenciasCard) pintarAgenciasProvincia(deliveryMode === 'provincia');

                    if (deliveryMode === 'recojo') {
                        if (shippingOptionsContainer) shippingOptionsContainer.style.display = 'none';
                        if (provinciaNote) provinciaNote.classList.add('d-none');
                        shippingCost = 0;
                        selectedOption = 'pickup';
                    } else if (deliveryMode === 'provincia') {
                        // El despacho por agencia no tiene tarifa fija en la tienda:
                        // se coordina antes de enviar, así que el carrito no suma nada.
                        if (shippingOptionsContainer) shippingOptionsContainer.style.display = 'none';
                        if (provinciaNote) provinciaNote.classList.remove('d-none');
                        shippingCost = 0;
                        selectedOption = 'provincia';
                    } else {
                        if (shippingOptionsContainer) shippingOptionsContainer.style.display = 'block';
                        if (provinciaNote) provinciaNote.classList.add('d-none');

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
                        // «Gratis» mentiría en provincias: ahí el costo no es cero,
                        // es un número que falta coordinar antes de despachar.
                        shippingDisplay.innerText = deliveryMode === 'provincia'
                            ? 'Se coordina'
                            : (shippingCost > 0 ? formatearPrecioJS(shippingCost) : 'Gratis');
                    }

                    if (shippingLabel) {
                        shippingLabel.textContent = deliveryMode === 'recojo'
                            ? 'Recojo en tienda'
                            : (deliveryMode === 'provincia' ? 'Envío a provincias' : 'Costo de Envío');
                    }

                    pintarPuntoElegido(deliveryMode);

                    if (shippingMessage) {
                        shippingMessage.classList.remove('text-success', 'text-muted', 'text-info');

                        if (deliveryMode === 'recojo') {
                            shippingMessage.textContent = 'Sin costo de envío al recoger en tienda.';
                            shippingMessage.classList.add('text-info');
                        } else if (deliveryMode === 'provincia') {
                            shippingMessage.textContent = 'Envío a provincias: lo coordinamos antes de despachar.';
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

                    const descuento = recalcularCupon();
                    const total = Math.max(0, subtotalAcumulado - descuento + shippingCost);
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
                    checkoutForm.addEventListener('submit', function(event) {
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
                        // El FormData se arma ANTES de desactivar nada: un input
                        // deshabilitado no es enviable y el navegador lo descarta,
                        // con lo que el carrito llegaría vacío al servidor.
                        const formData = new FormData(this);

                        if (updateCartButton) {
                            updateCartButton.disabled = true;
                            updateCartButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Guardando...';
                        }
                        document.querySelectorAll('.quantity-input, .quantity-btn').forEach(control => {
                            control.disabled = true;
                        });

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
                                cuponCodigo = data.cupon?.codigo ?? '';
                                cuponPorcentaje = data.cupon?.porcentaje ?? 0;

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
