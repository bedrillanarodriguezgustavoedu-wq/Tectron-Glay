@extends('layouts.admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Datos de la Oferta: {{ $oferta->nombre }}</h1>
        <a href="{{ route('admin.ofertas.index') }}" class="btn btn-secondary">Volver</a>
    </div>
    <hr>

    <div class="row">
        <div class="col-md-5">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-light">
                    <h4 class="mb-0 text-dark fw-bold">Detalle de la Promoción</h4>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-5 text-muted fw-bold">Nombre:</div>
                        <div class="col-md-7 fw-semibold text-dark fs-5">{{ $oferta->nombre }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-5 text-muted fw-bold">Descuento:</div>
                        <div class="col-md-7">
                            @if ($oferta->tipo_descuento === 'porcentaje')
                                <span class="badge bg-danger fs-5 px-3 py-1 rounded-pill">-{{ (int)$oferta->valor_descuento }}%</span>
                            @else
                                <span class="badge bg-danger fs-5 px-3 py-1 rounded-pill">-{{ $ajuste->divisa ?? '$' }}{{ number_format($oferta->valor_descuento, 2) }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-5 text-muted fw-bold">Estado Admin:</div>
                        <div class="col-md-7">
                            @if ($oferta->activo)
                                <span class="badge bg-success px-3 py-2 rounded-pill">Activa</span>
                            @else
                                <span class="badge bg-danger px-3 py-2 rounded-pill">Inactiva (Pausada)</span>
                            @endif
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-5 text-muted fw-bold">Fecha Inicio:</div>
                        <div class="col-md-7 text-dark fw-medium">
                            <i class="bi bi-calendar-event text-primary me-1"></i>
                            {{ \Carbon\Carbon::parse($oferta->fecha_inicio)->format('d/m/Y H:i') }}
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-5 text-muted fw-bold">Fecha Fin:</div>
                        <div class="col-md-7 text-dark fw-medium">
                            <i class="bi bi-calendar-x text-danger me-1"></i>
                            {{ \Carbon\Carbon::parse($oferta->fecha_fin)->format('d/m/Y H:i') }}
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-5 text-muted fw-bold">Estado Campaña:</div>
                        <div class="col-md-7">
                            @php
                                $ahora = now();
                                $inicio = \Carbon\Carbon::parse($oferta->fecha_inicio);
                                $fin = \Carbon\Carbon::parse($oferta->fecha_fin);
                            @endphp

                            @if (!$oferta->activo)
                                <span class="badge bg-secondary">Pausada</span>
                            @elseif ($ahora->between($inicio, $fin))
                                <span class="badge bg-success px-3 py-2 rounded-pill"><i class="bi bi-play-fill"></i> Vigente</span>
                            @elseif ($ahora->lt($inicio))
                                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill"><i class="bi bi-hourglass-split"></i> Próxima</span>
                            @else
                                <span class="badge bg-danger px-3 py-2 rounded-pill"><i class="bi bi-calendar-x-fill"></i> Vencida</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h4 class="mb-0 text-dark fw-bold">Productos Asociados</h4>
                    <span class="badge bg-secondary rounded-pill px-3 py-2">{{ $oferta->productos->count() }} Productos</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                        <table class="table table-bordered table-striped table-hover align-middle">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Producto</th>
                                    <th style="text-align: right;">Precio Normal</th>
                                    <th style="text-align: right; color: red;">Precio Oferta</th>
                                    <th style="text-align: right;">Ahorro</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($oferta->productos as $producto)
                                    @php
                                        $precio_oferta_calculado = $producto->precio_venta;
                                        if ($oferta->tipo_descuento === 'porcentaje') {
                                            $precio_oferta_calculado = $producto->precio_venta * (1 - ($oferta->valor_descuento / 100));
                                        } else {
                                            $precio_oferta_calculado = max(0, $producto->precio_venta - $oferta->valor_descuento);
                                        }
                                        $ahorro = $producto->precio_venta - $precio_oferta_calculado;
                                    @endphp
                                    <tr>
                                        <td class="fw-semibold">
                                            <a href="{{ url('/admin/producto/' . $producto->id) }}" class="text-decoration-none">
                                                {{ $producto->nombre }}
                                            </a>
                                        </td>
                                        <td style="text-align: right;">{{ $ajuste->divisa ?? '$' }}{{ number_format($producto->precio_venta, 2) }}</td>
                                        <td style="text-align: right; color: red;" class="fw-bold">{{ $ajuste->divisa ?? '$' }}{{ number_format($precio_oferta_calculado, 2) }}</td>
                                        <td style="text-align: right;" class="text-success fw-bold">-{{ $ajuste->divisa ?? '$' }}{{ number_format($ahorro, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            No hay productos asociados a esta oferta.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
