@extends('layouts.admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Listado de Ofertas y Promociones</h1>
        @can('Crear Oferta')
            <a href="{{ route('admin.ofertas.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Crear Nueva Oferta
            </a>
        @endcan
    </div>
    <hr>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light">
                    <h4 class="mb-0 text-dark">Ofertas registradas</h4>
                </div>

                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <form action="{{ route('admin.ofertas.index') }}" method="GET">
                                <div class="input-group">
                                    <input type="text" name="buscar" class="form-control" placeholder="Buscar por nombre..."
                                        value="{{ request('buscar') }}">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search"></i> Buscar
                                    </button>
                                    @if (request('buscar'))
                                        <a href="{{ route('admin.ofertas.index') }}" class="btn btn-success">
                                            <i class="bi bi-trash"></i> Limpiar
                                        </a>
                                    @endif
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-striped">
                            <thead>
                                <tr>
                                    <th style="width: 60px; text-align: center;">Nro</th>
                                    <th>Nombre de la Oferta</th>
                                    <th style="text-align: center;">Descuento (%)</th>
                                    <th style="text-align: center;">Vigencia</th>
                                    <th style="text-align: center;">Productos</th>
                                    <th style="text-align: center;">Estado Admin</th>
                                    <th style="text-align: center;">Estado Campaña</th>
                                    <th style="width: 250px; text-align: center;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $nro = ($ofertas->currentPage() - 1) * $ofertas->perPage() + 1;
                                @endphp
                                @forelse ($ofertas as $oferta)
                                    <tr>
                                        <td style="text-align: center; vertical-align: middle;">{{ $nro++ }}</td>
                                        <td style="vertical-align: middle; font-weight: bold;">{{ $oferta->nombre }}</td>
                                        <td style="text-align: center; vertical-align: middle;">
                                            @if ($oferta->tipo_descuento === 'porcentaje')
                                                <span class="badge bg-danger fs-6 px-3 py-2 rounded-pill">-{{ (int)$oferta->valor_descuento }}%</span>
                                            @else
                                                <span class="badge bg-danger fs-6 px-3 py-2 rounded-pill">-{{ $ajuste->divisa ?? '$' }}{{ number_format($oferta->valor_descuento, 2) }}</span>
                                            @endif
                                        </td>
                                        <td style="text-align: center; vertical-align: middle; font-size: 13px;">
                                            <div><strong>Inicio:</strong> {{ \Carbon\Carbon::parse($oferta->fecha_inicio)->format('d/m/Y H:i') }}</div>
                                            <div><strong>Fin:</strong> {{ \Carbon\Carbon::parse($oferta->fecha_fin)->format('d/m/Y H:i') }}</div>
                                        </td>
                                        <td style="text-align: center; vertical-align: middle;">
                                            <span class="badge bg-secondary rounded-pill px-3">{{ $oferta->productos_count }}</span>
                                        </td>
                                        <td style="text-align: center; vertical-align: middle;">
                                            <form action="{{ route('admin.ofertas.toggle', $oferta->id) }}" method="POST">
                                                @csrf
                                                @if ($oferta->activo)
                                                    <button type="submit" class="btn btn-success btn-sm px-3 rounded-pill">Activa</button>
                                                @else
                                                    <button type="submit" class="btn btn-danger btn-sm px-3 rounded-pill">Inactiva</button>
                                                @endif
                                            </form>
                                        </td>
                                        <td style="text-align: center; vertical-align: middle;">
                                            @php
                                                $ahora = now();
                                                $inicio = \Carbon\Carbon::parse($oferta->fecha_inicio);
                                                $fin = \Carbon\Carbon::parse($oferta->fecha_fin);
                                            @endphp

                                            @if (!$oferta->activo)
                                                <span class="badge bg-secondary">Pausada</span>
                                            @elseif ($ahora->between($inicio, $fin))
                                                <span class="badge bg-success"><i class="bi bi-play-fill"></i> Vigente</span>
                                            @elseif ($ahora->lt($inicio))
                                                <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Próxima</span>
                                            @else
                                                <span class="badge bg-danger"><i class="bi bi-calendar-x-fill"></i> Vencida</span>
                                            @endif
                                        </td>
                                        <td style="text-align: center; vertical-align: middle;">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('admin.ofertas.show', $oferta->id) }}" class="btn btn-info">
                                                    <i class="bi bi-eye"></i> Ver
                                                </a>
                                                @can('Editar Oferta')
                                                    <a href="{{ route('admin.ofertas.edit', $oferta->id) }}" class="btn btn-success">
                                                        <i class="bi bi-pencil"></i> Editar
                                                    </a>
                                                @endcan
                                                @can('Eliminar Oferta')
                                                    <button type="button" class="btn btn-danger" onclick="confirmarEliminar{{ $oferta->id }}(event)">
                                                        <i class="bi bi-trash"></i> Eliminar
                                                    </button>
                                                @endcan
                                            </div>

                                            @can('Eliminar Oferta')
                                                <form action="{{ route('admin.ofertas.destroy', $oferta->id) }}" method="POST" id="form-eliminar-{{ $oferta->id }}" style="display: none;">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>

                                                <script>
                                                    function confirmarEliminar{{ $oferta->id }}(event) {
                                                        event.preventDefault();
                                                        Swal.fire({
                                                            title: '¿Desea eliminar la oferta "{{ $oferta->nombre }}"?',
                                                            text: "Esta acción no se puede deshacer y los productos asociados volverán a sus precios estándar.",
                                                            icon: 'warning',
                                                            showCancelButton: true,
                                                            confirmButtonColor: '#d33',
                                                            cancelButtonColor: '#3085d6',
                                                            confirmButtonText: 'Sí, eliminar',
                                                            cancelButtonText: 'Cancelar'
                                                        }).then((result) => {
                                                            if (result.isConfirmed) {
                                                                document.getElementById('form-eliminar-{{ $oferta->id }}').submit();
                                                            }
                                                        });
                                                    }
                                                </script>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">
                                            No se encontraron ofertas registradas.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($ofertas->hasPages())
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="text-muted">
                                Mostrando {{ $ofertas->firstItem() }} a {{ $ofertas->lastItem() }} de {{ $ofertas->total() }} ofertas
                            </div>
                            <div>
                                {{ $ofertas->appends(request()->query())->links('pagination::bootstrap-4') }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
