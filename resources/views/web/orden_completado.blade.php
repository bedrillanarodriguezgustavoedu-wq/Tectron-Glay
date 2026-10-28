@extends('layouts.web')
@section("meta_robots", "noindex, follow")

@section('content')
    @php
        $pagoPendiente = $orden->estado_pago !== 'Pagado';
        $revisionMercadoPago = $orden->metodo_pago === 'tarjeta'
            && $pagoPendiente
            && $orden->intentosPago->contains(fn ($intento) => $intento->requiereRevisionManual());
        $pagoConTransferencia = in_array($orden->metodo_pago, ['yape', 'plin', 'bcp'], true) && $pagoPendiente;
        $pedidoPendiente = in_array($orden->metodo_pago, ['yape', 'plin', 'bcp', 'tarjeta'], true) && $pagoPendiente;
        [$metodoManual, $numeroManual, $titularManual, $qrManual, $cuentaBcp] = match ($orden->metodo_pago) {
            'yape' => ['Yape', $ajuste?->web_yape_numero, $ajuste?->web_yape_titular, $ajuste?->web_yape_qr, null],
            'plin' => ['Plin', $ajuste?->web_plin_numero, $ajuste?->web_plin_titular, $ajuste?->web_plin_qr, null],
            'bcp' => ['BCP', $ajuste?->web_bcp_cci, $ajuste?->web_bcp_titular, null, $ajuste?->web_bcp_cuenta],
            default => ['', null, null, null, null],
        };
        // Si la cuenta guardada deja de ser un destino de fiar (datos de prueba,
        // CCI incompleta), el pedido no se queda con un número a medio copiar.
        $destinoNoFiable = $pagoConTransferencia
            && \App\Models\Ajuste::motivoParaOcultarPagoWeb($ajuste, (string) $orden->metodo_pago) !== null;
        // En la app del banco se teclea el importe sin miles ni símbolo: copiar
        // «S/ 4,200.00» obliga a borrar a mano y es donde alguien redondea.
        $importeCopiable = number_format((float) $orden->total, 2, '.', '');
        $lineasDireccion = $orden->direccion_envio_lineas();
        // El título sigue el texto real del pedido y no la modalidad elegida: un
        // «envío a provincia» sin agencia guarda la casa del cliente, y llamarlo
        // «agencia» lo mandaría a retirar una caja que nunca llegó a ese local.
        $primerasPalabras = mb_strtolower(trim((string) $orden->direccion_envio));
        $tituloDireccion = match (true) {
            str_starts_with($primerasPalabras, 'recojo en ') => 'Punto de recojo',
            str_starts_with($primerasPalabras, 'agencia ') => 'Agencia de destino',
            default => 'Dirección de envío',
        };
    @endphp
    <style>
        .receipt-step__heading {
            display: flex;
            align-items: center;
            gap: .75rem;
            margin-bottom: 1.25rem;
        }

        .receipt-step__number {
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            background: #1a1d21;
            color: #fff;
            font-weight: 700;
        }

        .receipt-dropzone {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .35rem;
            padding: 2.25rem 1.25rem;
            text-align: center;
            border: 2px dashed #cfd9e6;
            border-radius: 1rem;
            background: #f7f9fc;
            transition: border-color .2s ease, background-color .2s ease;
        }

        .receipt-dropzone:hover,
        .receipt-dropzone.is-dragging {
            border-color: #0d6efd;
            background: #f1f6ff;
        }

        .receipt-dropzone:has(.receipt-dropzone__input:focus-visible) {
            outline: 3px solid rgba(13, 110, 253, .35);
            outline-offset: 3px;
        }

        .receipt-dropzone.is-invalid {
            border-color: #dc3545;
        }

        .receipt-dropzone__input {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .receipt-dropzone__icon {
            font-size: 1.9rem;
            color: #0d6efd;
        }

        .receipt-dropzone small {
            max-width: 460px;
        }

        /* El QR sube como foto de pantalla: el cuadro se ve grande pero el código
           de adentro es chico, así que agrandarlo no es un adorno. */
        /* El tema ya fija `button:not(:disabled) { cursor: pointer }`: sin el
           :not() esta regla empataría y perdería por orden de carga. */
        button.checkout-qr-zoom:not(:disabled) {
            cursor: zoom-in;
            display: block;
            padding: 0;
            border: 0;
            background: none;
            line-height: 0;
        }

        button.checkout-qr-zoom:focus-visible {
            outline: 2px solid var(--color-primary, #0d6efd);
            outline-offset: 2px;
            border-radius: .5rem;
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
    </style>
    <div class="page-title light-background py-4">
        <div class="container text-center">
            <h1 class="fw-bold mb-2">{{ $pedidoPendiente ? '¡Pedido enviado!' : 'Confirmación de Pedido' }}</h1>
            <p class="text-muted mb-0">
                {{ $pedidoPendiente ? 'Guardamos tu pedido. Completa la transferencia y envía el comprobante para que la tienda revise el pago.' : 'Tu compra se ha realizado correctamente.' }}
            </p>
        </div>
    </div>
    <section id="confirmation" class="confirmation section py-5">
        <div class="container" data-aos="fade-up" data-aos-delay="100">
            @if (session('mensaje'))
                <div class="alert alert-{{ session('icono') === 'success' ? 'success' : (session('icono') === 'info' ? 'info' : 'warning') }} alert-dismissible fade show mb-4" role="alert">
                    {{ session('mensaje') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            @endif
            <div class="row justify-content-center mb-5">
                <div class="col-lg-10">
                    <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                        <div class="card-body text-center p-5">
                            <div class="mb-4">
                                <i class="bi {{ $pedidoPendiente ? 'bi-clock-history text-warning' : 'bi-check-circle-fill text-success' }}" style="font-size: 5rem;"></i>
                            </div>
                            <h2 class="fw-bold {{ $pedidoPendiente ? 'text-warning' : 'text-success' }} mb-3">
                                {{ $pedidoPendiente ? '¡Pedido enviado!' : '¡Gracias por tu compra!' }}
                            </h2>
                            <p class="lead text-muted mb-5">
                                @if ($pedidoPendiente)
                                    El pedido todavía no está pagado. La tienda confirmará el pago después de revisar tu comprobante.
                                @else
                                    Tu pedido ha sido procesado con éxito y está confirmado.
                                    Recibirás una copia de esta confirmación por correo electrónico.
                                @endif
                            </p>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <div class="border rounded-4 p-3 bg-light h-100">
                                        <small class="text-muted d-block">
                                            Número de Orden
                                        </small>
                                        <h4 class="fw-bold text-primary mb-0">
                                            #{{ $orden->id }}
                                        </h4>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border rounded-4 p-3 bg-light h-100">
                                        <small class="text-muted d-block">
                                            {{ $pedidoPendiente ? 'Total del pedido' : 'Total Pagado' }}
                                        </small>
                                        <h4 class="fw-bold {{ $pedidoPendiente ? 'text-dark' : 'text-success' }} mb-0">
                                            {{ \App\Models\Ajuste::formatear_precio($orden->total) }}
                                        </h4>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border rounded-4 p-3 bg-light h-100">
                                        <small class="text-muted d-block">
                                            Método de pago
                                        </small>
                                        <h6 class="fw-semibold mb-0">
                                            {{ $orden->getMetodoPagoTexto() }}
                                        </h6>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border rounded-4 p-3 bg-light h-100">
                                        <small class="text-muted d-block">
                                            Fecha del Pedido
                                        </small>
                                        <h6 class="fw-semibold mb-0">
                                            {{ \Carbon\Carbon::parse($orden->created_at)->format('d/m/Y H:i') }}
                                        </h6>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
                                <a href="{{ route('web.dashboard') }}" class="btn btn-primary rounded-pill px-4">
                                    <i class="bi bi-bag-check me-1"></i>Ver mis pedidos
                                </a>
                                <a href="{{ route('web.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
                                    <i class="bi bi-house me-1"></i>Volver al inicio
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @if ($pagoConTransferencia)
                <div class="row justify-content-center mb-4">
                    <div class="col-lg-10">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body p-4 p-lg-5">
                                <h3 class="h4 fw-bold mb-3">Paga con {{ $metodoManual }}</h3>
                                <p class="text-muted">{{ $destinoNoFiable
                                    ? 'Todavía no puedes pagar este pedido: la tienda está verificando la cuenta receptora de '.$metodoManual.'.'
                                    : ($orden->metodo_pago === 'bcp'
                                        ? 'Transfiere exactamente el importe del pedido a la CCI indicada. Revisa que la aplicación muestre al titular antes de confirmar.'
                                        : 'Escanea el QR oficial y transfiere exactamente el importe del pedido. Revisa que la aplicación muestre al titular indicado antes de confirmar.') }}</p>
                                <div class="alert alert-warning small">
                                    <i class="bi bi-shield-check me-1"></i>
                                    El pedido seguirá pendiente hasta que la tienda confirme el abono. No te bases solo en una captura: conserva el comprobante y verifica que los datos del destinatario sean correctos.
                                </div>
                                <div class="row align-items-center g-4">
                                    <div class="col-md-7">
                                        @if ($destinoNoFiable)
                                            <div class="alert alert-danger mb-3" role="alert">
                                                <i class="bi bi-shield-exclamation me-1" aria-hidden="true"></i>
                                                <strong>No hagas la transferencia todavía.</strong>
                                                La tienda está corrigiendo los datos de la cuenta de {{ $metodoManual }}.
                                                Te avisaremos por correo o escríbenos por WhatsApp para completar el pago sin que sueltes dinero a un destino equivocado.
                                            </div>
                                        @else
                                        <div class="bg-light rounded-3 p-3 mb-3">
                                            <div class="text-muted small">{{ $orden->metodo_pago === 'bcp' ? 'CCI BCP' : 'Número receptor' }}</div>
                                            <div class="d-flex flex-wrap align-items-center gap-2">
                                                <div class="fs-4 fw-bold">{{ $numeroManual }}</div>
                                                @if ($numeroManual)
                                                    <button type="button" class="btn btn-sm btn-outline-primary" data-copy-payment-number="{{ $numeroManual }}"
                                                        data-copy-name="El número receptor" data-copy-note="Verifica que el titular de la app sea el de la tienda antes de confirmar.">
                                                        <i class="bi bi-copy me-1" aria-hidden="true"></i><span data-copy-label>Copiar número</span>
                                                    </button>
                                                @endif
                                            </div>
                                            @if ($titularManual)
                                                <div class="mt-1">Titular: <strong>{{ $titularManual }}</strong></div>
                                            @endif
                                            @if ($cuentaBcp)
                                                <div class="mt-1">Cuenta BCP: <strong>{{ $cuentaBcp }}</strong></div>
                                            @endif
                                        </div>
                                        @endif
                                        <div class="bg-light rounded-3 p-3 mb-3">
                                            <div class="text-muted small">Importe exacto</div>
                                            <div class="d-flex flex-wrap align-items-center gap-2">
                                                <div class="fs-4 fw-bold">{{ \App\Models\Ajuste::formatear_precio($orden->total) }}</div>
                                                <button type="button" class="btn btn-sm btn-outline-primary" data-copy-payment-number="{{ $importeCopiable }}"
                                                    data-copy-name="El importe" data-copy-note="Pégalo tal cual: no redondees ni cambies los centavos.">
                                                    <i class="bi bi-copy me-1" aria-hidden="true"></i><span data-copy-label>Copiar importe</span>
                                                </button>
                                            </div>
                                            <div class="small text-muted">Referencia: pedido #{{ $orden->id }}</div>
                                        </div>
                                        <!-- Debajo de los dos botones: el aviso de «copiado»
                                             tiene que quedar pegado a lo que se copió. -->
                                        <div class="small text-muted mb-3" id="payment-copy-feedback" role="status" aria-live="polite"></div>
                                    </div>
                                    @if ($qrManual && ! $destinoNoFiable)
                                        <div class="col-md-5 text-center">
                                            <div class="border rounded-4 bg-white p-3 d-inline-block shadow-sm">
                                                <button type="button" class="checkout-qr-zoom" data-bs-toggle="modal"
                                                    data-bs-target="#qrZoomModal" data-qr-zoom="{{ $metodoManual }}"
                                                    aria-label="Agrandar el código QR de {{ $metodoManual }}">
                                                    <img src="{{ url('/storage/'.ltrim($qrManual, '/')) }}"
                                                        alt="Código QR oficial de {{ $metodoManual }} del negocio"
                                                        class="img-fluid" style="width: min(100%, 320px);" loading="eager">
                                                </button>
                                            </div>
                                            <div class="fw-semibold mt-3">Escanea con {{ $metodoManual }}</div>
                                            <div class="small text-muted">Confirma el importe y el titular antes de transferir.</div>
                                            <div class="small text-primary">¿No lo puedes leer? Tócalo para agrandarlo.</div>
                                        </div>
                                    @else
                                        <div class="col-md-5">
                                            <div class="alert alert-warning mb-0">
                                                El código QR no está disponible en este momento. No realices la transferencia; comunícate con la tienda.
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            @if ($pagoConTransferencia && (! $pagoManual?->comprobante_path || $pagoManual->estado !== 'confirmado'))
                <div class="row justify-content-center mb-4">
                    <div class="col-lg-10">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body p-4 p-lg-5">
                                <div class="receipt-step__heading">
                                    <span class="receipt-step__number" aria-hidden="true">3</span>
                                    <div>
                                        <h3 class="h5 fw-bold mb-1">{{ $pagoManual?->comprobante_path ? 'Reemplazar comprobante' : 'Subir comprobante' }}</h3>
                                        <p class="text-muted small mb-0">Paso final: la tienda revisa el archivo y confirma el abono.</p>
                                    </div>
                                </div>
                                @if ($pagoManual?->comprobante_path)
                                    <div class="alert alert-info">
                                        <i class="bi bi-hourglass-split me-1"></i>
                                        Comprobante recibido. El pago está pendiente de revisión por la tienda. Si enviaste una imagen incorrecta, puedes reemplazarla aquí.
                                    </div>
                                    <a href="{{ route('web.pedidos.comprobante.ver', $orden->id) }}"
                                        target="_blank" rel="noopener noreferrer"
                                        class="d-inline-block mb-3" aria-label="Ver comprobante enviado">
                                        <img src="{{ route('web.pedidos.comprobante.ver', $orden->id) }}"
                                            alt="Vista previa de tu comprobante enviado"
                                            class="img-thumbnail" style="max-width: 240px; max-height: 220px; object-fit: contain;"
                                            loading="eager">
                                    </a>
                                @endif
                                <form action="{{ route('web.pedidos.comprobante', $orden->id) }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <div class="receipt-dropzone @error('comprobante') is-invalid @enderror" id="receipt-dropzone">
                                        <input type="file" name="comprobante" id="comprobante"
                                            class="receipt-dropzone__input @error('comprobante') is-invalid @enderror"
                                            accept="image/jpeg,image/png,image/webp" required
                                            onchange="previewPaymentReceipt(event)">
                                        <i class="bi bi-file-earmark-text receipt-dropzone__icon" aria-hidden="true"></i>
                                        <span class="fw-semibold">Comprobante de pago</span>
                                        <small class="text-muted">Sube una captura o foto de tu comprobante de pago. Solo se aceptan imágenes en formato PNG, JPG, JPEG o WEBP de hasta 5 MB.</small>
                                        <span class="receipt-dropzone__file text-primary small fw-semibold" data-receipt-file></span>
                                    </div>
                                    @error('comprobante')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                    <img id="payment-receipt-preview" src="" alt="Vista previa del nuevo comprobante"
                                        class="img-thumbnail mt-2 d-none" style="max-width: 240px; max-height: 220px; object-fit: contain;">
                                    <button type="submit" class="btn btn-dark w-100 rounded-3 mt-3">
                                        {{ $pagoManual?->comprobante_path ? 'Enviar comprobante nuevo' : 'Continuar' }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            @if ($orden->metodo_pago === 'tarjeta' && $pagoPendiente)
                <div class="row justify-content-center mb-4">
                    <div class="col-lg-10">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body p-4 p-lg-5">
                                @if ($revisionMercadoPago)
                                    <h3 class="h4 fw-bold mb-3">Mercado Pago registró un cobro</h3>
                                    <p class="text-muted mb-0">La tienda está verificando ese cobro antes de confirmar tu pedido.
                                        <strong>No vuelvas a pagar</strong>: si repites el cargo, el dinero puede demorar en devolverse.</p>
                                @else
                                    <h3 class="h4 fw-bold mb-3">Falta confirmar tu pago con tarjeta</h3>
                                    <p class="text-muted">Tu pedido quedó reservado, pero Mercado Pago no registró un pago aprobado. Reabre el pago para completarlo.</p>
                                    <div class="row align-items-center g-4">
                                        <div class="col-md-7">
                                            <div class="bg-light rounded-3 p-3 mb-3">
                                                <div class="text-muted small">Importe exacto</div>
                                                <div class="fs-4 fw-bold">{{ \App\Models\Ajuste::formatear_precio($orden->total) }}</div>
                                                <div class="small text-muted">Pedido #{{ $orden->id }} · {{ $orden->getMetodoPagoTexto() }}</div>
                                            </div>
                                            <form action="{{ route('web.pedidos.tarjeta.reintentar', $orden->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-primary rounded-pill px-4">
                                                    <i class="bi bi-credit-card me-1"></i> Pagar ahora con tarjeta
                                                </button>
                                            </form>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="alert alert-info mb-0 small">
                                                <i class="bi bi-info-circle me-1"></i>
                                                El pago se completa en Mercado Pago y su confirmación puede tardar unos segundos.
                                                Si ya pagaste, espera y recarga esta página antes de volver a intentar el cobro.
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            <div class="row g-4">
                <!-- PRODUCTOS -->
                <div class="col-lg-7" data-aos="fade-up" data-aos-delay="200">
                    <div class="card shadow-sm border-0 rounded-4 mb-4">
                        <div class="card-header bg-white border-0 pt-4 px-4">
                            <h4 class="fw-bold mb-0">
                                Productos Adquiridos
                            </h4>
                        </div>
                        <div class="card-body p-0">
                            @foreach ($orden->detalles as $detalle)
                                <div class="d-flex justify-content-between align-items-center p-4 border-bottom">
                                    <div>
                                        <h6 class="fw-bold mb-1">
                                            {{ $detalle->producto->nombre }}
                                        </h6>
                                        @php
                                            $imagen = $detalle->producto->imagen_principal_url;
                                        @endphp
                                        <img src="{{ $imagen }}" width="100px" alt="{{ $detalle->producto->nombre }}"
                                            onerror="this.onerror=null;this.src='{{ asset('storage/productos/default.webp') }}';"
                                            loading="lazy">
                                        <br><br>
                                        <small class="text-muted">
                                            Cantidad : {{ $detalle->cantidad }} <br>
                                            Precio Unitario :
                                            {{ \App\Models\Ajuste::formatear_precio($detalle->precio) }}
                                        </small>
                                    </div>
                                    <span class="fw-bold">Subtotal :
                                        {{ \App\Models\Ajuste::formatear_precio($detalle->cantidad * $detalle->precio) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <!-- DIRECCIÓN -->
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-4">
                                <i class="bi bi-geo-alt-fill text-danger me-2"></i>
                                Información de Entrega
                            </h5>
                            <div class="d-flex align-items-center mb-3">
                                <div class="flex-shrink-0">
                                    <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                                        <i class="bi bi-person-fill text-primary"></i>
                                    </div>
                                </div>
                                <div class="ms-3">
                                    <small class="text-muted d-block">
                                        Cliente
                                    </small>
                                    <span class="fw-semibold">
                                        {{ $orden->usuario->name ?? 'Cliente' }}
                                    </span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <div class="flex-shrink-0">
                                    <div class="bg-success bg-opacity-10 rounded-circle p-3">
                                        <i class="bi bi-envelope-fill text-success"></i>
                                    </div>
                                </div>
                                <div class="ms-3">
                                    <small class="text-muted d-block">
                                        Correo Electrónico
                                    </small>
                                    <span>
                                        {{ $orden->usuario->email ?? 'email' }}
                                    </span>
                                </div>
                            </div>
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    <div class="bg-danger bg-opacity-10 rounded-circle p-3">
                                        <i class="bi bi-house-door-fill text-danger"></i>
                                    </div>
                                </div>
                                <div class="ms-3">
                                    <small class="text-muted d-block">
                                        {{ $tituloDireccion }}
                                    </small>
                                    @if ($lineasDireccion === [])
                                        <span>{{ $orden->direccion_envio }}</span>
                                    @else
                                        <span>
                                            @foreach ($lineasDireccion as $linea)
                                                <span class="d-block">{{ $linea }}</span>
                                            @endforeach
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- RESUMEN -->
                <div class="col-lg-5" data-aos="fade-up" data-aos-delay="300">
                    <div class="card shadow-sm border-0 rounded-4 mb-4">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-4">
                                Resumen del Pago
                            </h5>
                            @if (filled($orden->descuento))
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-danger">
                                        Descuento
                                        @if (filled($orden->cupon))
                                            con cupón <strong>{{ $orden->cupon }}</strong>
                                        @endif
                                    </span>
                                    <span class="fw-semibold text-danger">
                                        -{{ \App\Models\Ajuste::formatear_precio((float) $orden->descuento) }}
                                    </span>
                                </div>
                            @endif
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>Total Final</span>
                                <span class="fw-bold text-success fs-4">
                                    {{ \App\Models\Ajuste::formatear_precio($orden->total) }}
                                </span>
                            </div>
                            <hr>
                            <p class="mb-2">
                                <strong>Método de Pago:</strong>
                                {{ $orden->getMetodoPagoTexto() }}
                            </p>
                            <p class="mb-0">
                                <strong>Estado:</strong>
                                <span class="badge bg-{{ $orden->getEstadoPagoBadgeColor() }}">
                                    {{ $orden->getEstadoPagoTexto() }}
                                </span>
                            </p>
                        </div>
                    </div>
                    <!-- SIGUIENTES PASOS -->
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-4">
                                ¿Qué sigue?
                            </h5>
                            <div class="d-flex align-items-start mb-3">
                                <i class="bi bi-envelope-fill text-primary me-3 fs-5"></i>
                                <span>
                                    Te enviamos a tu correo el detalle de tu compra.
                                    Guarda ese mensaje: ahí está el número de pedido.
                                </span>
                            </div>
                            <div class="d-flex align-items-start">
                                <i class="bi bi-truck text-success me-3 fs-5"></i>
                                <span>
                                    Te notificaremos cuando tu pedido sea enviado
                                    y esté en camino.
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    @if ($qrManual && ! $destinoNoFiable)
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
    @endif
    <script>
        const paymentCopyFeedback = document.getElementById('payment-copy-feedback');

        const mensajeCopiado = (texto, variante) => {
            if (!paymentCopyFeedback) return;

            paymentCopyFeedback.textContent = texto;
            paymentCopyFeedback.className = `small mb-3 ${variante}`;
            window.clearTimeout(paymentCopyFeedback.dataset.tiempo);
            paymentCopyFeedback.dataset.tiempo = window.setTimeout(() => {
                paymentCopyFeedback.textContent = '';
                paymentCopyFeedback.className = 'small text-muted mb-3';
            }, 5000);
        };

        const marcarCopiado = (button) => {
            const icono = button.querySelector('i');
            const etiqueta = button.querySelector('[data-copy-label]');
            if (!icono || button.dataset.copiado === '1') return;

            button.dataset.copiado = '1';
            if (etiqueta) {
                etiqueta.dataset.texto = etiqueta.textContent;
                etiqueta.textContent = 'Copiado';
            }
            button.classList.remove('btn-outline-primary');
            button.classList.add('btn-primary');

            window.setTimeout(() => {
                if (etiqueta) etiqueta.textContent = etiqueta.dataset.texto ?? etiqueta.textContent;
                button.classList.remove('btn-primary');
                button.classList.add('btn-outline-primary');
                button.dataset.copiado = '0';
            }, 1800);
        };

        // Dos botones (número e importe) comparten un solo aviso: el cliente tiene
        // que saber qué copió, así que cada botón trae su propio texto.
        document.querySelectorAll('[data-copy-payment-number]').forEach(button => {
            button.addEventListener('click', async () => {
                const nombre = button.dataset.copyName ?? 'El dato';

                if (!navigator.clipboard || !window.isSecureContext) {
                    mensajeCopiado('Tu navegador no permite copiar automáticamente: selecciona el dato y cópialo a mano.', 'text-warning');
                    return;
                }

                try {
                    await navigator.clipboard.writeText(button.dataset.copyPaymentNumber);
                    marcarCopiado(button);
                    mensajeCopiado(`${nombre} copiado. ${button.dataset.copyNote ?? ''}`, 'text-success');
                } catch (error) {
                    mensajeCopiado('No se pudo copiar. Selecciona el dato y cópialo manualmente.', 'text-warning');
                }
            });
        });

        // El QR se agranda con la imagen que ya está descargada en la página: no se
        // vuelve a pedir el archivo ni se expone otra ruta.
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

        function previewPaymentReceipt(event) {
            const file = event.target.files?.[0];
            const preview = document.getElementById('payment-receipt-preview');
            const dropzone = document.getElementById('receipt-dropzone');
            const fileName = dropzone?.querySelector('[data-receipt-file]');

            if (fileName) {
                fileName.textContent = file ? `Seleccionado: ${file.name}` : '';
            }

            if (!preview) return;

            if (preview.dataset.objectUrl) {
                URL.revokeObjectURL(preview.dataset.objectUrl);
                delete preview.dataset.objectUrl;
            }

            if (!file) {
                preview.removeAttribute('src');
                preview.classList.add('d-none');
                return;
            }

            const objectUrl = URL.createObjectURL(file);
            preview.src = objectUrl;
            preview.dataset.objectUrl = objectUrl;
            preview.classList.remove('d-none');
        }

        const receiptInput = document.getElementById('comprobante');
        const receiptDropzone = document.getElementById('receipt-dropzone');

        if (receiptInput && receiptDropzone) {
            ['dragenter', 'dragover'].forEach(eventName => {
                receiptDropzone.addEventListener(eventName, event => {
                    event.preventDefault();
                    receiptDropzone.classList.add('is-dragging');
                });
            });

            ['dragleave', 'dragend', 'drop'].forEach(eventName => {
                receiptDropzone.addEventListener(eventName, () => {
                    receiptDropzone.classList.remove('is-dragging');
                });
            });

            receiptDropzone.addEventListener('drop', event => {
                event.preventDefault();
                const files = event.dataTransfer?.files;
                if (!files || files.length === 0) return;

                receiptInput.files = files;
                previewPaymentReceipt({ target: receiptInput });
            });
        }
    </script>
@endsection
