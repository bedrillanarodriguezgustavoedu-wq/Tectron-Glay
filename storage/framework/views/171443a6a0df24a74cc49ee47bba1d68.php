<?php $__env->startSection("meta_robots", "noindex, follow"); ?>

<?php $__env->startSection('content'); ?>
    <?php
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
    ?>
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
    </style>
    <div class="page-title light-background py-4">
        <div class="container text-center">
            <h1 class="fw-bold mb-2"><?php echo e($pedidoPendiente ? '¡Pedido enviado!' : 'Confirmación de Pedido'); ?></h1>
            <p class="text-muted mb-0">
                <?php echo e($pedidoPendiente ? 'Guardamos tu pedido. Completa la transferencia y envía el comprobante para que la tienda revise el pago.' : 'Tu compra se ha realizado correctamente.'); ?>

            </p>
        </div>
    </div>
    <section id="confirmation" class="confirmation section py-5">
        <div class="container" data-aos="fade-up" data-aos-delay="100">
            <?php if(session('mensaje')): ?>
                <div class="alert alert-<?php echo e(session('icono') === 'success' ? 'success' : (session('icono') === 'info' ? 'info' : 'warning')); ?> alert-dismissible fade show mb-4" role="alert">
                    <?php echo e(session('mensaje')); ?>

                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            <?php endif; ?>
            <div class="row justify-content-center mb-5">
                <div class="col-lg-10">
                    <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                        <div class="card-body text-center p-5">
                            <div class="mb-4">
                                <i class="bi <?php echo e($pedidoPendiente ? 'bi-clock-history text-warning' : 'bi-check-circle-fill text-success'); ?>" style="font-size: 5rem;"></i>
                            </div>
                            <h2 class="fw-bold <?php echo e($pedidoPendiente ? 'text-warning' : 'text-success'); ?> mb-3">
                                <?php echo e($pedidoPendiente ? '¡Pedido enviado!' : '¡Gracias por tu compra!'); ?>

                            </h2>
                            <p class="lead text-muted mb-5">
                                <?php if($pedidoPendiente): ?>
                                    El pedido todavía no está pagado. La tienda confirmará el pago después de revisar tu comprobante.
                                <?php else: ?>
                                    Tu pedido ha sido procesado con éxito y está confirmado.
                                    Recibirás una copia de esta confirmación por correo electrónico.
                                <?php endif; ?>
                            </p>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <div class="border rounded-4 p-3 bg-light h-100">
                                        <small class="text-muted d-block">
                                            Número de Orden
                                        </small>
                                        <h4 class="fw-bold text-primary mb-0">
                                            #<?php echo e($orden->id); ?>

                                        </h4>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border rounded-4 p-3 bg-light h-100">
                                        <small class="text-muted d-block">
                                            <?php echo e($pedidoPendiente ? 'Total del pedido' : 'Total Pagado'); ?>

                                        </small>
                                        <h4 class="fw-bold <?php echo e($pedidoPendiente ? 'text-dark' : 'text-success'); ?> mb-0">
                                            <?php echo e(\App\Models\Ajuste::formatear_precio($orden->total)); ?>

                                        </h4>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border rounded-4 p-3 bg-light h-100">
                                        <small class="text-muted d-block">
                                            Método de pago
                                        </small>
                                        <h6 class="fw-semibold mb-0">
                                            <?php echo e($orden->getMetodoPagoTexto()); ?>

                                        </h6>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border rounded-4 p-3 bg-light h-100">
                                        <small class="text-muted d-block">
                                            Fecha del Pedido
                                        </small>
                                        <h6 class="fw-semibold mb-0">
                                            <?php echo e(\Carbon\Carbon::parse($orden->created_at)->format('d/m/Y H:i')); ?>

                                        </h6>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
                                <a href="<?php echo e(route('web.dashboard')); ?>" class="btn btn-primary rounded-pill px-4">
                                    <i class="bi bi-bag-check me-1"></i>Ver mis pedidos
                                </a>
                                <a href="<?php echo e(route('web.index')); ?>" class="btn btn-outline-secondary rounded-pill px-4">
                                    <i class="bi bi-house me-1"></i>Volver al inicio
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php if($pagoConTransferencia): ?>
                <div class="row justify-content-center mb-4">
                    <div class="col-lg-10">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body p-4 p-lg-5">
                                <h3 class="h4 fw-bold mb-3">Paga con <?php echo e($metodoManual); ?></h3>
                                <p class="text-muted"><?php echo e($destinoNoFiable
                                    ? 'Todavía no puedes pagar este pedido: la tienda está verificando la cuenta receptora de '.$metodoManual.'.'
                                    : ($orden->metodo_pago === 'bcp'
                                        ? 'Transfiere exactamente el importe del pedido a la CCI indicada. Revisa que la aplicación muestre al titular antes de confirmar.'
                                        : 'Escanea el QR oficial y transfiere exactamente el importe del pedido. Revisa que la aplicación muestre al titular indicado antes de confirmar.')); ?></p>
                                <div class="alert alert-warning small">
                                    <i class="bi bi-shield-check me-1"></i>
                                    El pedido seguirá pendiente hasta que la tienda confirme el abono. No te bases solo en una captura: conserva el comprobante y verifica que los datos del destinatario sean correctos.
                                </div>
                                <div class="row align-items-center g-4">
                                    <div class="col-md-7">
                                        <?php if($destinoNoFiable): ?>
                                            <div class="alert alert-danger mb-3" role="alert">
                                                <i class="bi bi-shield-exclamation me-1" aria-hidden="true"></i>
                                                <strong>No hagas la transferencia todavía.</strong>
                                                La tienda está corrigiendo los datos de la cuenta de <?php echo e($metodoManual); ?>.
                                                Te avisaremos por correo o escríbenos por WhatsApp para completar el pago sin que sueltes dinero a un destino equivocado.
                                            </div>
                                        <?php else: ?>
                                        <div class="bg-light rounded-3 p-3 mb-3">
                                            <div class="text-muted small"><?php echo e($orden->metodo_pago === 'bcp' ? 'CCI BCP' : 'Número receptor'); ?></div>
                                            <div class="d-flex flex-wrap align-items-center gap-2">
                                                <div class="fs-4 fw-bold"><?php echo e($numeroManual); ?></div>
                                                <?php if($numeroManual): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-primary" data-copy-payment-number="<?php echo e($numeroManual); ?>">
                                                        <i class="bi bi-copy me-1" aria-hidden="true"></i>Copiar número
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                            <?php if($titularManual): ?>
                                                <div class="mt-1">Titular: <strong><?php echo e($titularManual); ?></strong></div>
                                            <?php endif; ?>
                                            <?php if($cuentaBcp): ?>
                                                <div class="mt-1">Cuenta BCP: <strong><?php echo e($cuentaBcp); ?></strong></div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                        <div class="small text-muted mb-3" id="payment-copy-feedback" role="status" aria-live="polite"></div>
                                        <div class="bg-light rounded-3 p-3 mb-3">
                                            <div class="text-muted small">Importe exacto</div>
                                            <div class="fs-4 fw-bold"><?php echo e(\App\Models\Ajuste::formatear_precio($orden->total)); ?></div>
                                            <div class="small text-muted">Referencia: pedido #<?php echo e($orden->id); ?></div>
                                        </div>
                                    </div>
                                    <?php if($qrManual && ! $destinoNoFiable): ?>
                                        <div class="col-md-5 text-center">
                                            <div class="border rounded-4 bg-white p-3 d-inline-block shadow-sm">
                                                <img src="<?php echo e(url('/storage/'.ltrim($qrManual, '/'))); ?>"
                                                    alt="Código QR oficial de <?php echo e($metodoManual); ?> del negocio"
                                                    class="img-fluid" style="width: min(100%, 320px);" loading="eager">
                                            </div>
                                            <div class="fw-semibold mt-3">Escanea con <?php echo e($metodoManual); ?></div>
                                            <div class="small text-muted">Confirma el importe y el titular antes de transferir.</div>
                                        </div>
                                    <?php else: ?>
                                        <div class="col-md-5">
                                            <div class="alert alert-warning mb-0">
                                                El código QR no está disponible en este momento. No realices la transferencia; comunícate con la tienda.
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <?php if($pagoConTransferencia && (! $pagoManual?->comprobante_path || $pagoManual->estado !== 'confirmado')): ?>
                <div class="row justify-content-center mb-4">
                    <div class="col-lg-10">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body p-4 p-lg-5">
                                <div class="receipt-step__heading">
                                    <span class="receipt-step__number" aria-hidden="true">3</span>
                                    <div>
                                        <h3 class="h5 fw-bold mb-1"><?php echo e($pagoManual?->comprobante_path ? 'Reemplazar comprobante' : 'Subir comprobante'); ?></h3>
                                        <p class="text-muted small mb-0">Paso final: la tienda revisa el archivo y confirma el abono.</p>
                                    </div>
                                </div>
                                <?php if($pagoManual?->comprobante_path): ?>
                                    <div class="alert alert-info">
                                        <i class="bi bi-hourglass-split me-1"></i>
                                        Comprobante recibido. El pago está pendiente de revisión por la tienda. Si enviaste una imagen incorrecta, puedes reemplazarla aquí.
                                    </div>
                                    <a href="<?php echo e(route('web.pedidos.comprobante.ver', $orden->id)); ?>"
                                        target="_blank" rel="noopener noreferrer"
                                        class="d-inline-block mb-3" aria-label="Ver comprobante enviado">
                                        <img src="<?php echo e(route('web.pedidos.comprobante.ver', $orden->id)); ?>"
                                            alt="Vista previa de tu comprobante enviado"
                                            class="img-thumbnail" style="max-width: 240px; max-height: 220px; object-fit: contain;"
                                            loading="eager">
                                    </a>
                                <?php endif; ?>
                                <form action="<?php echo e(route('web.pedidos.comprobante', $orden->id)); ?>" method="POST" enctype="multipart/form-data">
                                    <?php echo csrf_field(); ?>
                                    <div class="receipt-dropzone <?php $__errorArgs = ['comprobante'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="receipt-dropzone">
                                        <input type="file" name="comprobante" id="comprobante"
                                            class="receipt-dropzone__input <?php $__errorArgs = ['comprobante'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                            accept="image/jpeg,image/png,image/webp" required
                                            onchange="previewPaymentReceipt(event)">
                                        <i class="bi bi-file-earmark-text receipt-dropzone__icon" aria-hidden="true"></i>
                                        <span class="fw-semibold">Comprobante de pago</span>
                                        <small class="text-muted">Sube una captura o foto de tu comprobante de pago. Solo se aceptan imágenes en formato PNG, JPG, JPEG o WEBP de hasta 5 MB.</small>
                                        <span class="receipt-dropzone__file text-primary small fw-semibold" data-receipt-file></span>
                                    </div>
                                    <?php $__errorArgs = ['comprobante'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="text-danger small mt-1"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    <img id="payment-receipt-preview" src="" alt="Vista previa del nuevo comprobante"
                                        class="img-thumbnail mt-2 d-none" style="max-width: 240px; max-height: 220px; object-fit: contain;">
                                    <button type="submit" class="btn btn-dark w-100 rounded-3 mt-3">
                                        <?php echo e($pagoManual?->comprobante_path ? 'Enviar comprobante nuevo' : 'Continuar'); ?>

                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <?php if($orden->metodo_pago === 'tarjeta' && $pagoPendiente): ?>
                <div class="row justify-content-center mb-4">
                    <div class="col-lg-10">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body p-4 p-lg-5">
                                <?php if($revisionMercadoPago): ?>
                                    <h3 class="h4 fw-bold mb-3">Mercado Pago registró un cobro</h3>
                                    <p class="text-muted mb-0">La tienda está verificando ese cobro antes de confirmar tu pedido.
                                        <strong>No vuelvas a pagar</strong>: si repites el cargo, el dinero puede demorar en devolverse.</p>
                                <?php else: ?>
                                    <h3 class="h4 fw-bold mb-3">Falta confirmar tu pago con tarjeta</h3>
                                    <p class="text-muted">Tu pedido quedó reservado, pero Mercado Pago no registró un pago aprobado. Reabre el pago para completarlo.</p>
                                    <div class="row align-items-center g-4">
                                        <div class="col-md-7">
                                            <div class="bg-light rounded-3 p-3 mb-3">
                                                <div class="text-muted small">Importe exacto</div>
                                                <div class="fs-4 fw-bold"><?php echo e(\App\Models\Ajuste::formatear_precio($orden->total)); ?></div>
                                                <div class="small text-muted">Pedido #<?php echo e($orden->id); ?> · <?php echo e($orden->getMetodoPagoTexto()); ?></div>
                                            </div>
                                            <form action="<?php echo e(route('web.pedidos.tarjeta.reintentar', $orden->id)); ?>" method="POST">
                                                <?php echo csrf_field(); ?>
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
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
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
                            <?php $__currentLoopData = $orden->detalles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $detalle): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="d-flex justify-content-between align-items-center p-4 border-bottom">
                                    <div>
                                        <h6 class="fw-bold mb-1">
                                            <?php echo e($detalle->producto->nombre); ?>

                                        </h6>
                                        <?php
                                            $imagen = $detalle->producto->imagen_principal_url;
                                        ?>
                                        <img src="<?php echo e($imagen); ?>" width="100px" alt="<?php echo e($detalle->producto->nombre); ?>"
                                            onerror="this.onerror=null;this.src='<?php echo e(asset('storage/productos/default.webp')); ?>';"
                                            loading="lazy">
                                        <br><br>
                                        <small class="text-muted">
                                            Cantidad : <?php echo e($detalle->cantidad); ?> <br>
                                            Precio Unitario :
                                            <?php echo e(\App\Models\Ajuste::formatear_precio($detalle->precio)); ?>

                                        </small>
                                    </div>
                                    <span class="fw-bold">Subtotal :
                                        <?php echo e(\App\Models\Ajuste::formatear_precio($detalle->cantidad * $detalle->precio)); ?>

                                    </span>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
                                        <?php echo e($orden->usuario->name ?? 'Cliente'); ?>

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
                                        <?php echo e($orden->usuario->email ?? 'email'); ?>

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
                                        Dirección de Envío
                                    </small>
                                    <span>
                                        <?php echo e($orden->direccion_envio); ?>

                                    </span>
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
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>Total Final</span>
                                <span class="fw-bold text-success fs-4">
                                    <?php echo e(\App\Models\Ajuste::formatear_precio($orden->total)); ?>

                                </span>
                            </div>
                            <hr>
                            <p class="mb-2">
                                <strong>Método de Pago:</strong>
                                <?php echo e($orden->getMetodoPagoTexto()); ?>

                            </p>
                            <p class="mb-0">
                                <strong>Estado:</strong>
                                <span class="badge bg-<?php echo e($orden->getEstadoPagoBadgeColor()); ?>">
                                    <?php echo e($orden->getEstadoPagoTexto()); ?>

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
    <script>
        const paymentCopyButton = document.querySelector('[data-copy-payment-number]');
        const paymentCopyFeedback = document.getElementById('payment-copy-feedback');

        if (paymentCopyButton && paymentCopyFeedback) {
            paymentCopyButton.addEventListener('click', async () => {
                if (!navigator.clipboard || !window.isSecureContext) {
                    paymentCopyFeedback.textContent = 'No se pudo copiar automáticamente. Selecciona el número y cópialo manualmente.';
                    return;
                }

                try {
                    await navigator.clipboard.writeText(paymentCopyButton.dataset.copyPaymentNumber);
                    paymentCopyFeedback.textContent = 'Número copiado. Verifica el titular antes de transferir.';
                } catch (error) {
                    paymentCopyFeedback.textContent = 'No se pudo copiar automáticamente. Selecciona el número y cópialo manualmente.';
                }
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.web', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\ecommerce (4)\ecommerce\resources\views/web/orden_completado.blade.php ENDPATH**/ ?>