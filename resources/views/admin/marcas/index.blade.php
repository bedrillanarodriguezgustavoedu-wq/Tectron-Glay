@extends('layouts.admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Listado de Marcas</h1>
        @can('Crear Marca')
            <a href="{{ route('admin.marcas.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Crear Nueva Marca
            </a>
        @endcan
    </div>
    <hr>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Marcas registradas</h4>
                </div>

                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <form action="{{ route('admin.marcas.index') }}" method="GET">
                                <div class="input-group">
                                    <input type="text" name="buscar" class="form-control" placeholder="Buscar por nombre..."
                                        value="{{ request('buscar') }}">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search"></i> Buscar
                                    </button>
                                    @if (request('buscar'))
                                        <a href="{{ route('admin.marcas.index') }}" class="btn btn-success">
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
                                    <th style="width: 80px; text-align: center;">Nro</th>
                                    <th style="width: 120px; text-align: center;">Logotipo</th>
                                    <th>Nombre</th>
                                    <th>Slug</th>
                                    <th style="text-align: center;">Productos Asociados</th>
                                    <th style="text-align: center;">Estado</th>
                                    <th style="width: 250px; text-align: center;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $nro = ($marcas->currentPage() - 1) * $marcas->perPage() + 1;
                                @endphp
                                @forelse ($marcas as $marca)
                                    <tr>
                                        <td style="text-align: center; vertical-align: middle;">{{ $nro++ }}</td>
                                        <td style="text-align: center; vertical-align: middle;">
                                            <x-marca-logo :marca="$marca" width="100px" height="50px" />
                                        </td>
                                        <td style="vertical-align: middle; font-weight: bold;">{{ $marca->nombre }}</td>
                                        <td style="vertical-align: middle;">{{ $marca->slug }}</td>
                                        <td style="text-align: center; vertical-align: middle;">
                                            <span class="badge bg-secondary rounded-pill px-3">{{ $marca->productos_count }}</span>
                                        </td>
                                        <td style="text-align: center; vertical-align: middle;">
                                            <form action="{{ route('admin.marcas.toggle', $marca->id) }}" method="POST">
                                                @csrf
                                                @if ($marca->activo)
                                                    <button type="submit" class="btn btn-success btn-sm px-3 rounded-pill">Activa</button>
                                                @else
                                                    <button type="submit" class="btn btn-danger btn-sm px-3 rounded-pill">Inactiva</button>
                                                @endif
                                            </form>
                                        </td>
                                        <td style="text-align: center; vertical-align: middle;">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('admin.marcas.show', $marca->id) }}" class="btn btn-info">
                                                    <i class="bi bi-eye"></i> Ver
                                                </a>
                                                @can('Editar Marca')
                                                    <a href="{{ route('admin.marcas.edit', $marca->id) }}" class="btn btn-success">
                                                        <i class="bi bi-pencil"></i> Editar
                                                    </a>
                                                @endcan
                                                @can('Eliminar Marca')
                                                    <button type="button" class="btn btn-danger" onclick="confirmarEliminar{{ $marca->id }}(event)">
                                                        <i class="bi bi-trash"></i> Eliminar
                                                    </button>
                                                @endcan
                                            </div>

                                            @can('Eliminar Marca')
                                                <form action="{{ route('admin.marcas.destroy', $marca->id) }}" method="POST" id="form-eliminar-{{ $marca->id }}" style="display: none;">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>

                                                <script>
                                                    function confirmarEliminar{{ $marca->id }}(event) {
                                                        event.preventDefault();
                                                        Swal.fire({
                                                            title: '¿Desea eliminar la marca "{{ $marca->nombre }}"?',
                                                            text: "Esta acción no se puede deshacer y puede afectar a los productos asociados.",
                                                            icon: 'warning',
                                                            showCancelButton: true,
                                                            confirmButtonColor: '#d33',
                                                            cancelButtonColor: '#3085d6',
                                                            confirmButtonText: 'Sí, eliminar',
                                                            cancelButtonText: 'Cancelar'
                                                        }).then((result) => {
                                                            if (result.isConfirmed) {
                                                                document.getElementById('form-eliminar-{{ $marca->id }}').submit();
                                                            }
                                                        });
                                                    }
                                                </script>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            No se encontraron marcas registradas.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($marcas->hasPages())
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="text-muted">
                                Mostrando {{ $marcas->firstItem() }} a {{ $marcas->lastItem() }} de {{ $marcas->total() }} marcas
                            </div>
                            <div>
                                {{ $marcas->appends(request()->query())->links('pagination::bootstrap-4') }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
