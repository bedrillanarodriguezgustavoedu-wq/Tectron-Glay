@extends('layouts.admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Listado de Cupones</h1>
        @can('Crear Cupon')
            <a href="{{ route('admin.cupones.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Crear Nuevo Cupón
            </a>
        @endcan
    </div>
    <hr>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Cupones registrados</h4>
                </div>

                <div class="card-body">
                    <div class="alert alert-info">
                        Estos cupones son los mismos que acepta la app GALY. Pausar uno aquí hace que deje de
                        aplicarse al instante; los pedidos ya cobrados no se modifican.
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <form action="{{ route('admin.cupones.index') }}" method="GET">
                                <div class="input-group">
                                    <input type="text" name="buscar" class="form-control" placeholder="Buscar por código..."
                                        value="{{ request('buscar') }}">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search"></i> Buscar
                                    </button>
                                    @if (request('buscar'))
                                        <a href="{{ route('admin.cupones.index') }}" class="btn btn-success">
                                            <i class="bi bi-x-lg"></i> Limpiar
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
                                    <th style="width: 70px; text-align: center;">Nro</th>
                                    <th>Código</th>
                                    <th style="width: 130px; text-align: center;">Descuento</th>
                                    <th style="width: 160px; text-align: center;">Vence</th>
                                    <th style="width: 130px; text-align: center;">Estado</th>
                                    <th style="width: 200px; text-align: center;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $nro = ($cupones->currentPage() - 1) * $cupones->perPage() + 1;
                                @endphp
                                @forelse ($cupones as $cupon)
                                    <tr>
                                        <td style="text-align: center; vertical-align: middle;">{{ $nro++ }}</td>
                                        <td style="vertical-align: middle; font-weight: bold;">{{ $cupon->codigo }}</td>
                                        <td style="text-align: center; vertical-align: middle;">
                                            <span class="badge bg-secondary rounded-pill px-3">{{ $cupon->descuento_porcentaje }}%</span>
                                        </td>
                                        <td style="text-align: center; vertical-align: middle;">
                                            {{ $cupon->fecha_expiracion ? $cupon->fecha_expiracion->format('d/m/Y') : 'Sin fecha' }}
                                        </td>
                                        <td style="text-align: center; vertical-align: middle;">
                                            @can('Editar Cupon')
                                                <form action="{{ route('admin.cupones.toggle', $cupon->id) }}" method="POST">
                                                    @csrf
                                                    @if ($cupon->estado === 'Vigente')
                                                        <button type="submit" class="btn btn-success btn-sm px-3 rounded-pill">Vigente</button>
                                                    @elseif ($cupon->estado === 'Pausado')
                                                        <button type="submit" class="btn btn-secondary btn-sm px-3 rounded-pill">Pausado</button>
                                                    @else
                                                        <button type="submit" class="btn btn-warning btn-sm px-3 rounded-pill">Vencido</button>
                                                    @endif
                                                </form>
                                            @else
                                                <span class="badge bg-secondary">{{ $cupon->estado }}</span>
                                            @endcan
                                        </td>
                                        <td style="text-align: center; vertical-align: middle;">
                                            <div class="btn-group btn-group-sm" role="group">
                                                @can('Editar Cupon')
                                                    <a href="{{ route('admin.cupones.edit', $cupon->id) }}" class="btn btn-success">
                                                        <i class="bi bi-pencil"></i> Editar
                                                    </a>
                                                @endcan
                                                @can('Eliminar Cupon')
                                                    <button type="button" class="btn btn-danger" onclick="confirmarEliminar{{ $cupon->id }}(event)">
                                                        <i class="bi bi-pause-circle"></i> Pausar
                                                    </button>

                                                    <form action="{{ route('admin.cupones.destroy', $cupon->id) }}" method="POST" id="form-eliminar-{{ $cupon->id }}" style="display: none;">
                                                        @csrf
                                                        @method('DELETE')
                                                    </form>

                                                    <script>
                                                        function confirmarEliminar{{ $cupon->id }}(event) {
                                                            event.preventDefault();
                                                            Swal.fire({
                                                                title: '¿Pausar el cupón "{{ $cupon->codigo }}"?',
                                                                text: "Deja de aceptarse en la app de inmediato. El registro se conserva y puedes reactivarlo.",
                                                                icon: 'warning',
                                                                showCancelButton: true,
                                                                confirmButtonColor: '#d33',
                                                                cancelButtonColor: '#3085d6',
                                                                confirmButtonText: 'Sí, pausar',
                                                                cancelButtonText: 'Cancelar'
                                                            }).then((result) => {
                                                                if (result.isConfirmed) {
                                                                    document.getElementById('form-eliminar-{{ $cupon->id }}').submit();
                                                                }
                                                            });
                                                        }
                                                    </script>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            No hay cupones con ese criterio.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($cupones->hasPages())
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="text-muted">
                                Mostrando {{ $cupones->firstItem() }} a {{ $cupones->lastItem() }} de {{ $cupones->total() }} cupones
                            </div>
                            <div>
                                {{ $cupones->appends(request()->query())->links('pagination::bootstrap-4') }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
