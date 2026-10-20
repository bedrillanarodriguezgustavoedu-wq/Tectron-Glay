@extends('layouts.admin')

@section('content')
    <h1>Listado de pedidos</h1>
    <hr>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Pedidos registrados
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <form action="{{ url('/admin/pedidos') }}" method="GET" class="mt-3">
                                <div class="input-group">
                                    <input type="text" name="buscar" class="form-control" placeholder="Buscar..."
                                        value="{{ $_REQUEST['buscar'] ?? '' }}">
                                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i>
                                        Buscar</button>
                                    @if (isset($_REQUEST['buscar']))
                                        <a href="{{ url('/admin/pedidos') }}" class="btn btn-success">
                                            <i class="bi bi-trash"></i> Limpiar</a>
                                    @endif
                                </div>
                            </form>
                            <br>
                        </div>
                    </div>
                    <table class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Nro</th>
                                <th>Cliente</th>
                                <th>Total</th>
                                <th>Entrega</th>
                                <th>Pago</th>
                                <th>Estado del pedido</th>
                                <th>Dirección de envío</th>
                                <th>Productos</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $nro = ($pedidos->currentPage() - 1) * $pedidos->perPage() + 1;
                            @endphp
                            @foreach ($pedidos as $pedido)
                                @php($cliente = $pedido->usuario ?? $usuariosGaly->get($pedido->usuario_id))
                                <tr>
                                    <td style="text-align: center">{{ $nro++ }}</td>
                                    <td>
                                        <strong>{{ $pedido->usuario?->name ?? $cliente?->nombre ?? 'Cliente no vinculado' }}</strong>
                                        <br>
                                        <small class="text-muted">
                                            {{ $pedido->usuario?->email ?? $cliente?->email ?? 'ID GALY: ' . ($pedido->usuario_id ?? 'no disponible') }}
                                        </small>
                                    </td>
                                    <td class="fw-bold">{{ $pedido->divisa . ' ' . number_format($pedido->total, 2) }}</td>
                                    <td>
                                        @if ($pedido->modalidad_entrega === 'recojo')
                                            <span class="badge bg-success"><i class="bi bi-shop"></i> Recojo</span>
                                        @else
                                            <span class="badge bg-primary"><i class="bi bi-truck"></i> Envío</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted d-block mb-1">{{ $pedido->getMetodoPagoTexto() }}</small>
                                        <span class="badge bg-{{ $pedido->getEstadoPagoBadgeColor() }}">
                                            {{ $pedido->getEstadoPagoTexto() }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $pedido->getBadgeColor() }}">
                                            {{ $pedido->estado_orden }}
                                        </span>
                                    </td>
                                    <td>
                                        <small>{{ $pedido->direccion_envio }}</small>
                                    </td>
                                    <td>
                                        <ul class="mb-0 ps-3">
                                            @foreach ($pedido->detalles as $detalle)
                                                <li>
                                                    <small>
                                                        {{ $detalle->producto?->nombre ?? 'Producto no disponible' }} (x{{ $detalle->cantidad }})
                                                    </small>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ url('/admin/pedido/' . $pedido->id) }}" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-gear-fill"></i> Gestionar
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if ($pedidos->hasPages())
                        <div class="d-flex justify-content-between align-items-center mt-4 px-3">
                            <div class="text-muted">
                                Mostrando {{ $pedidos->firstItem() }} a {{ $pedidos->lastItem() }} de
                                {{ $pedidos->total() }} registros
                            </div>
                            <div>
                                {{ $pedidos->links('pagination::bootstrap-4') }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <hr>
@endsection
