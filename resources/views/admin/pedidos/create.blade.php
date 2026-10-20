@extends('layouts.admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Pedido Nro: #{{ $pedido->id }}</h1>
        <a href="{{ url('/admin/pedidos') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver al Listado
        </a>
    </div>
    <hr>

    <!-- Visual Stepper Progress Bar -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow-sm border-0">
                <div class="card-body py-4">
                    <h5 class="card-title mb-4 text-center text-md-start">
                        <i class="bi bi-activity text-primary me-2"></i> Progreso del Pedido 
                        <span class="badge bg-light-primary text-primary ms-2">{{ $pedido->getModalidadTexto() }}</span>
                    </h5>
                    
                    @php
                        $flujo = $pedido->getFlujoEstados();
                        $current_index = array_search($pedido->estado_orden, $flujo);
                        $total_steps = count($flujo);
                    @endphp

                    <!-- Stepper Container -->
                    <div class="d-flex justify-content-between align-items-center position-relative my-3 flex-wrap">
                        @foreach ($flujo as $idx => $state)
                            @php
                                $is_completed = $idx <= $current_index;
                                $is_active = $idx == $current_index;
                                $badge_color = $is_completed ? 'success' : 'secondary';
                                $border_class = $is_active ? 'border-primary border-3 shadow-lg scale-up' : '';
                            @endphp
                            <div class="text-center position-relative mb-3 mb-md-0" style="flex: 1; min-width: 100px; z-index: 1;">
                                <div class="rounded-circle bg-{{ $badge_color }} text-white d-flex align-items-center justify-content-center m-auto {{ $border_class }}" 
                                     style="width: 44px; height: 44px; font-weight: bold; transition: all 0.3s ease;">
                                    @if ($is_completed && !$is_active)
                                        <i class="bi bi-check-lg" style="font-size: 1.2rem;"></i>
                                    @else
                                        {{ $idx + 1 }}
                                    @endif
                                </div>
                                <div class="small fw-bold mt-2 {{ $is_active ? 'text-primary' : 'text-muted' }}" style="font-size: 0.85rem;">
                                    {{ $state }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- LEFT COLUMN: Items and History -->
        <div class="col-lg-8">
            <!-- Order Items -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-box-seam text-secondary me-2"></i> Productos Comprados</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 5%">Nro</th>
                                    <th style="width: 15%">Imagen</th>
                                    <th>Producto</th>
                                    <th class="text-center">Precio</th>
                                    <th class="text-center">Cantidad</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $nro = 1; @endphp
                                @foreach ($pedido->detalles as $detalle)
                                    <tr>
                                        <td class="text-center">{{ $nro++ }}</td>
                                        <td>
                                            <img src="{{ $detalle->producto?->imagen_principal_url ?? asset('storage/productos/default.webp') }}"
                                                alt="{{ $detalle->producto?->nombre ?? 'Imagen de producto' }}"
                                                class="img-thumbnail" style="width: 80px; height: 64px; object-fit: contain;"
                                                onerror="this.onerror=null;this.src='{{ asset('storage/productos/default.webp') }}';"
                                                loading="lazy">
                                        </td>
                                        <td>
                                            <h6 class="mb-1">
                                                @if ($detalle->producto)
                                                    <a href="{{ url('/admin/producto/' . $detalle->producto->id) }}" class="text-decoration-none">
                                                        {{ $detalle->producto->nombre }}
                                                    </a>
                                                @else
                                                    <span class="text-muted">Producto no disponible</span>
                                                @endif
                                            </h6>
                                            @if ($detalle->producto?->descripcion_corta)
                                                <small class="text-muted">{{ $detalle->producto->descripcion_corta }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $pedido->divisa . ' ' . number_format($detalle->precio, 2) }}</td>
                                        <td class="text-center fw-bold">{{ $detalle->cantidad }}</td>
                                        <td class="text-end fw-bold">
                                            @php
                                                $subtotal = $detalle->precio * $detalle->cantidad;
                                            @endphp
                                            {{ $pedido->divisa . ' ' . number_format($subtotal, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="table-light fw-bold fs-6">
                                    <td colspan="5" class="text-end">Total del Pedido:</td>
                                    <td class="text-end text-primary">{{ $pedido->divisa . ' ' . number_format($pedido->total, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Status Transition History -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-clock-history text-secondary me-2"></i> Historial de Cambios de Estado</h5>
                </div>
                <div class="card-body">
                    @if($pedido->historial->isEmpty())
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-calendar-x display-6"></i>
                            <p class="mt-2">No se registra historial para este pedido.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Estado Anterior</th>
                                        <th>Estado Nuevo</th>
                                        <th>Fecha y Hora</th>
                                        <th>Responsable</th>
                                        <th>Comentarios / Notas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pedido->historial as $h)
                                        <tr>
                                            <td>
                                                @if($h->estado_anterior)
                                                    <span class="badge bg-light-secondary text-secondary border border-secondary-subtle">
                                                        {{ $h->estado_anterior }}
                                                    </span>
                                                @else
                                                    <span class="text-muted"><small><i>Creación Pedido</i></small></span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $pedido->getBadgeColor($h->estado_nuevo) }}">
                                                    {{ $h->estado_nuevo }}
                                                </span>
                                            </td>
                                            <td>
                                                <div><i class="bi bi-calendar3 me-1"></i> {{ date('d/m/Y', strtotime($h->fecha)) }}</div>
                                                <small class="text-muted"><i class="bi bi-clock me-1"></i> {{ date('H:i:s', strtotime($h->hora)) }}</small>
                                            </td>
                                            <td>
                                                @if($h->usuario)
                                                    <span class="fw-semibold">{{ $h->usuario->name }}</span>
                                                    <br><small class="badge bg-dark text-xxs">Admin</small>
                                                @else
                                                    <span class="text-muted">Sistema / Cliente</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="p-2 bg-light rounded text-xs" style="max-width: 250px; overflow-wrap: break-word;">
                                                    {!! $h->nota ? $h->nota : '<span class="text-muted"><i>Sin observaciones</i></span>' !!}
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Customer details & Status actions -->
        <div class="col-lg-4">
            <!-- Order Action Panel (Advance Status) -->
            <div class="card shadow-sm border-0 mb-4 bg-light-primary border-start border-primary border-4">
                <div class="card-body">
                    <h5 class="card-title fw-bold text-primary mb-3">
                        <i class="bi bi-gear-fill me-2"></i> Gestión de Estado
                    </h5>
                    
                    <div class="mb-3">
                        <label class="text-muted small d-block">Estado Actual</label>
                        <span class="badge bg-{{ $pedido->getBadgeColor() }} fs-6 py-2 px-3 mt-1">
                            {{ $pedido->estado_orden }}
                        </span>
                    </div>

                    @php
                        $siguiente = $pedido->getSiguienteEstado();
                        $pagoManualPendiente = in_array(strtolower((string) $pedido->metodo_pago), ['yape', 'plin', 'bcp'], true)
                            && ! in_array(strtolower((string) $pedido->estado_pago), ['pagado', 'completed', 'approved'], true);
                    @endphp

                    @if ($pagoManualPendiente)
                        <div class="alert alert-warning border-0 rounded-3 my-3 small">
                            <i class="bi bi-lock-fill me-1"></i>
                            El pedido no se puede preparar ni avanzar hasta que el pago esté confirmado. Revisa el comprobante y verifica el abono en la app de {{ ucfirst(strtolower($pedido->metodo_pago)) }}.
                        </div>
                    @elseif ($siguiente)
                        <div class="alert alert-info border-0 rounded-3 my-3 py-2 small">
                            <i class="bi bi-info-circle-fill me-1"></i>
                            El flujo establecido requiere avanzar el pedido secuencialmente a: <strong>{{ $siguiente }}</strong>.
                        </div>

                        <form action="{{ route('admin.pedidos.avanzar', $pedido->id) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="nota" class="form-label fw-semibold">Nota o Comentario (*opcional)</label>
                                <textarea name="nota" id="nota" class="form-control" rows="3" 
                                          placeholder="ej: Código de seguimiento de courier o instrucciones especiales."></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                                <i class="bi bi-chevron-right me-1"></i> Avanzar a: {{ $siguiente }}
                            </button>
                        </form>
                    @else
                        <div class="alert alert-success border-0 rounded-3 my-3 text-center">
                            <i class="bi bi-check-circle-fill display-6 d-block mb-2 text-success"></i>
                            <strong>Pedido Finalizado</strong>
                            <p class="small text-muted mb-0 mt-1">El pedido ha alcanzado su etapa de finalización en esta modalidad.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Customer Card -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h5 class="card-title border-bottom pb-2 mb-3"><i class="bi bi-person text-secondary me-2"></i> Cliente</h5>
                    @php
                        $cliente = $pedido->usuario;
                        $nombreCliente = $cliente?->name ?? $usuarioGaly?->nombre ?? $pedido->cliente_nombre;
                        $emailCliente = $cliente?->email ?? $usuarioGaly?->email ?? $pedido->cliente_email;
                    @endphp
                    @if ($nombreCliente)
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px; font-weight: bold;">
                                {{ strtoupper(substr($nombreCliente, 0, 2)) }}
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">{{ $nombreCliente }}</h6>
                                <small class="text-muted">{{ $emailCliente ?? 'Correo no disponible' }}</small>
                            </div>
                        </div>
                    @elseif ($emailCliente)
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px; font-weight: bold;">
                                {{ strtoupper(substr($emailCliente, 0, 2)) }}
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">{{ $emailCliente }}</h6>
                                <small class="text-muted">Cliente de la app</small>
                            </div>
                        </div>
                    @else
                        <p class="text-muted mb-3">Cliente no vinculado (ID GALY: {{ $pedido->usuario_id ?? 'no disponible' }})</p>
                    @endif
                    <div class="small">
                        <div class="mb-2">
                            <span class="text-muted d-block">Rol del Usuario</span>
                            <strong>{{ $cliente?->roles->pluck('name')->implode(', ') ?: 'Cliente' }}</strong>
                        </div>
                        @if (filled($cliente?->telefono))
                            <div class="mb-2">
                                <span class="text-muted d-block">Teléfono de contacto</span>
                                <strong>{{ $cliente->telefono }}</strong>
                            </div>
                        @endif
                        @if (filled($cliente?->dni))
                            <div class="mb-2">
                                <span class="text-muted d-block">DNI</span>
                                <strong>{{ $cliente->dni }}</strong>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Shipping / Delivery Mode Card -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h5 class="card-title border-bottom pb-2 mb-3"><i class="bi bi-pin-map text-secondary me-2"></i> Entrega</h5>
                    <div class="small">
                        <div class="mb-3">
                            <span class="text-muted d-block">Modalidad seleccionada</span>
                            <span class="badge bg-light-primary text-primary fs-6 mt-1">
                                {{ $pedido->getModalidadTexto() }}
                            </span>
                        </div>
                        <div class="mb-3">
                            <span class="text-muted d-block">Dirección registrada</span>
                            <p class="fw-semibold bg-light p-2 rounded mt-1 mb-0">{{ $pedido->direccion_envio }}</p>
                        </div>
                        @php($direccionAsociada = $pedido->getRelation('direccion'))
                        @if ($direccionAsociada instanceof \App\Models\Direccion && $pedido->modalidad_entrega === 'recojo')
                            <div class="mb-3">
                                <span class="text-muted d-block">Dirección asociada a la cuenta</span>
                                <p class="bg-light p-2 rounded mt-1 mb-0">{{ $direccionAsociada->getFormattedAddress() }}</p>
                            </div>
                        @endif
                        <div class="mb-3">
                            <span class="text-muted d-block">Método de Pago</span>
                            <span class="fw-bold d-block mt-1">{{ $pedido->getMetodoPagoTexto() }}</span>
                        </div>
                        <div class="mb-3">
                            <span class="text-muted d-block">Estado de Pago</span>
                            <span class="badge bg-{{ $pedido->getEstadoPagoBadgeColor() }} fs-6 mt-1">
                                {{ $pedido->getEstadoPagoTexto() }}
                            </span>
                        </div>
                        @php($intentoTarjeta = strtolower((string) $pedido->metodo_pago) === 'tarjeta' ? $pedido->intentosPago->first() : null)
                        @php($revisionTarjeta = strtolower((string) $pedido->metodo_pago) === 'tarjeta'
                            && ! in_array($pedido->estado_pago, ['Pagado', 'COMPLETED', 'approved'], true)
                            && $pedido->intentosPago->contains(fn ($intento) => $intento->requiereRevisionManual()))
                        @if ($intentoTarjeta)
                            <div class="mb-3">
                                <span class="text-muted d-block">Intento de pago con tarjeta</span>
                                <span class="badge bg-{{ $intentoTarjeta->getBadgeColor() }} mt-1">{{ $intentoTarjeta->getEstadoTexto() }}</span>
                                @if ($revisionTarjeta)
                                    <span class="badge bg-danger mt-1">Cobro por verificar</span>
                                @endif
                                <small class="d-block text-muted mt-1">
                                    Referencia Mercado Pago: {{ $intentoTarjeta->mercadopago_payment_id ?: 'sin asignar' }}
                                    @if ($intentoTarjeta->estado_mercadopago)
                                        · Estado en Mercado Pago: {{ $intentoTarjeta->estado_mercadopago }}{{ $intentoTarjeta->detalle_estado_mercadopago ? ' ('.$intentoTarjeta->detalle_estado_mercadopago.')' : '' }}
                                    @endif
                                    @if ($intentoTarjeta->estaVigente())
                                        · Vence a las {{ $intentoTarjeta->expira_at->format('H:i') }} del {{ $intentoTarjeta->expira_at->format('d/m/Y') }}
                                    @endif
                                </small>
                                @if ($revisionTarjeta)
                                    <div class="alert alert-warning py-2 small mt-2 mb-0">
                                        Mercado Pago reportó un cobro aprobado que no cuadra con este pedido.
                                        Comprueba el importe real en tu cuenta antes de marcarlo como pagado.
                                    </div>
                                @endif
                                <small class="d-block text-muted">Mercado Pago confirma este pago automáticamente al recibir el cobro; aquí no hay comprobante que revisar.</small>
                            </div>
                        @endif
                        @if ($pedido->pagoManual)
                            <div class="mb-3">
                                @if (filled($pedido->pagoManual->numero_operacion))
                                    <span class="text-muted d-block">Número de operación</span>
                                    <strong>{{ $pedido->pagoManual->numero_operacion }}</strong>
                                @endif
                                <span class="text-muted d-block">Comprobante de {{ ucfirst($pedido->pagoManual->metodo_pago) }}</span>
                                @if ($pedido->pagoManual->comprobante_path)
                                    <a href="{{ route('admin.pedidos.comprobante', $pedido->id) }}"
                                        target="_blank" rel="noopener noreferrer" class="d-inline-block mt-2"
                                        aria-label="Abrir comprobante completo del pedido #{{ $pedido->id }}">
                                        <img src="{{ route('admin.pedidos.comprobante', $pedido->id) }}"
                                            alt="Comprobante enviado por el cliente"
                                            class="img-thumbnail d-block" style="max-width: 260px; max-height: 260px; object-fit: contain;"
                                            loading="eager">
                                    </a>
                                    <a href="{{ route('admin.pedidos.comprobante', $pedido->id) }}"
                                        target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm mt-2">
                                        <i class="bi bi-arrows-fullscreen me-1"></i> Abrir tamaño completo
                                    </a>
                                    <small class="text-muted d-block mt-1">
                                        Enviado: {{ $pedido->pagoManual->comprobante_enviado_at?->format('d/m/Y H:i') ?? 'fecha no disponible' }}
                                    </small>
                                @else
                                    <span class="badge bg-warning text-dark mt-1">Sin comprobante adjunto</span>
                                    <small class="d-block text-muted mt-1">Verifica directamente en la app bancaria que el abono llegó y coincide con el pedido antes de confirmarlo.</small>
                                @endif
                            </div>
                        @elseif (in_array(strtolower((string) $pedido->metodo_pago), ['yape', 'plin', 'bcp'], true))
                            <div class="alert alert-warning small">
                                No se adjuntó comprobante. Verifica directamente en la app bancaria que el abono llegó y coincide con el importe antes de confirmar.
                            </div>
                        @endif
                        @if ($pedido->estado_pago === 'Pendiente' && auth()->user()->can('Procesar Pedido'))
                            <div class="mt-3">
                                <form action="{{ route('admin.pedidos.marcar_pagado', $pedido->id) }}" method="POST">
                                    @csrf
                                    @if (in_array(strtolower((string) $pedido->metodo_pago), ['yape', 'plin', 'bcp'], true))
                                        <div class="alert alert-warning small mb-2">
                                            Verifica en la app {{ strtoupper($pedido->metodo_pago) }} que el abono llegó y que el importe coincide con el pedido. No confirmes solo por la imagen.
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" name="verificado_en_app"
                                                id="verificado_en_app" value="1" required>
                                            <label class="form-check-label small" for="verificado_en_app">
                                                Confirmo que verifiqué el abono recibido en la app.
                                            </label>
                                        </div>
                                    @elseif (strtolower((string) $pedido->metodo_pago) === 'tarjeta')
                                        <div class="alert alert-warning small mb-2">
                                            Mercado Pago confirma los pagos con tarjeta automáticamente. Si el pedido sigue pendiente, revisa el cobro en tu cuenta de Mercado Pago antes de marcarlo como pagado; confirmar sin cobro real puede derivar en un doble cargo.
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" name="verificado_en_mercadopago"
                                                id="verificado_en_mercadopago" value="1" required>
                                            <label class="form-check-label small" for="verificado_en_mercadopago">
                                                Ya veo este cobro aprobado en mi cuenta de Mercado Pago.
                                            </label>
                                        </div>
                                    @endif
                                    <button type="submit" class="btn btn-success btn-sm w-100 fw-bold py-2 shadow-sm">
                                        <i class="bi bi-cash-coin me-1"></i>
                                        {{ match (strtolower((string) $pedido->metodo_pago)) {
                                            'yape', 'plin', 'bcp' => 'Confirmar pago verificado',
                                            'tarjeta' => 'Confirmar cobro de Mercado Pago',
                                            default => 'Marcar como Pagado',
                                        } }}
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CKEditor Setup -->
    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const notaElement = document.querySelector('#nota');
            if (notaElement) {
                ClassicEditor
                    .create(notaElement, {
                        toolbar: {
                            items: [
                                'bold', 'italic', 'underline', '|',
                                'link', 'bulletedList', 'numberedList', '|',
                                'undo', 'redo'
                            ]
                        },
                        language: 'es',
                    })
                    .catch(error => {
                        console.error(error);
                    });
            }
        });
    </script>
@endsection
