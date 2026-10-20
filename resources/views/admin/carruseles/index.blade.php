@extends('layouts.admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Listado de Carrusel Home (Sliders)</h1>
        @can('Crear Carrusel')
            <a href="{{ route('admin.carruseles.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Crear Nuevo Slider
            </a>
        @endcan
    </div>
    <hr>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light">
                    <h4 class="mb-0 text-dark">Sliders cargados</h4>
                </div>

                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <form action="{{ route('admin.carruseles.index') }}" method="GET">
                                <div class="input-group">
                                    <input type="text" name="buscar" class="form-control" placeholder="Buscar por título..."
                                        value="{{ request('buscar') }}">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search"></i> Buscar
                                    </button>
                                    @if (request('buscar'))
                                        <a href="{{ route('admin.carruseles.index') }}" class="btn btn-success">
                                            <i class="bi bi-trash"></i> Limpiar
                                        </a>
                                    @endif
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-striped align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 60px; text-align: center;">Nro</th>
                                    <th style="width: 220px; text-align: center;">Slider</th>
                                    <th>Título (Overlay)</th>
                                    <th>Descripción (Overlay)</th>
                                    <th>Enlace de Redirección</th>
                                    <th style="text-align: center;">Orden</th>
                                    <th style="text-align: center;">Estado</th>
                                    <th style="width: 250px; text-align: center;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $nro = ($carruseles->currentPage() - 1) * $carruseles->perPage() + 1;
                                @endphp
                                @forelse ($carruseles as $carrusel)
                                    <tr>
                                        <td style="text-align: center;">{{ $nro++ }}</td>
                                        <td style="text-align: center;">
                                            @if ($carrusel->imagen)
                                                <img src="{{ asset('storage/' . $carrusel->imagen) }}" alt="Slider {{ $carrusel->titulo }}"
                                                    style="max-width: 200px; height: auto; border-radius: 4px; max-height: 80px; object-fit: cover;">
                                            @else
                                                <span class="text-muted">Sin imagen</span>
                                            @endif
                                        </td>
                                        <td class="fw-bold">{{ $carrusel->titulo ?? 'Sin título' }}</td>
                                        <td style="font-size: 13px;">{{ $carrusel->descripcion ?? 'Sin descripción' }}</td>
                                        <td>
                                            @if($carrusel->enlace)
                                                <a href="{{ $carrusel->enlace }}" target="_blank" class="text-truncate d-inline-block" style="max-width: 180px;">
                                                    {{ $carrusel->enlace }}
                                                </a>
                                            @else
                                                <span class="text-muted">Ninguno</span>
                                            @endif
                                        </td>
                                        <td style="text-align: center;">
                                            <span class="badge bg-secondary px-3 py-1 rounded">{{ $carrusel->orden }}</span>
                                        </td>
                                        <td style="text-align: center;">
                                            <form action="{{ route('admin.carruseles.toggle', $carrusel->id) }}" method="POST">
                                                @csrf
                                                @if ($carrusel->activo)
                                                    <button type="submit" class="btn btn-success btn-sm px-3 rounded-pill">Activo</button>
                                                @else
                                                    <button type="submit" class="btn btn-danger btn-sm px-3 rounded-pill">Inactivo</button>
                                                @endif
                                            </form>
                                        </td>
                                        <td style="text-align: center;">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('admin.carruseles.show', $carrusel->id) }}" class="btn btn-info">
                                                    <i class="bi bi-eye"></i> Ver
                                                </a>
                                                @can('Editar Carrusel')
                                                    <a href="{{ route('admin.carruseles.edit', $carrusel->id) }}" class="btn btn-success">
                                                        <i class="bi bi-pencil"></i> Editar
                                                    </a>
                                                @endcan
                                                @can('Eliminar Carrusel')
                                                    <button type="button" class="btn btn-danger" onclick="confirmarEliminar{{ $carrusel->id }}(event)">
                                                        <i class="bi bi-trash"></i> Eliminar
                                                    </button>
                                                @endcan
                                            </div>

                                            @can('Eliminar Carrusel')
                                                <form action="{{ route('admin.carruseles.destroy', $carrusel->id) }}" method="POST" id="form-eliminar-{{ $carrusel->id }}" style="display: none;">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>

                                                <script>
                                                    function confirmarEliminar{{ $carrusel->id }}(event) {
                                                        event.preventDefault();
                                                        Swal.fire({
                                                            title: '¿Desea eliminar este slider?',
                                                            text: "Esta acción retirará la imagen del slider principal.",
                                                            icon: 'warning',
                                                            showCancelButton: true,
                                                            confirmButtonColor: '#d33',
                                                            cancelButtonColor: '#3085d6',
                                                            confirmButtonText: 'Sí, eliminar',
                                                            cancelButtonText: 'Cancelar'
                                                        }).then((result) => {
                                                            if (result.isConfirmed) {
                                                                document.getElementById('form-eliminar-{{ $carrusel->id }}').submit();
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
                                            No se encontraron sliders en el carrusel principal.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($carruseles->hasPages())
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="text-muted">
                                Mostrando {{ $carruseles->firstItem() }} a {{ $carruseles->lastItem() }} de {{ $carruseles->total() }} sliders
                            </div>
                            <div>
                                {{ $carruseles->appends(request()->query())->links('pagination::bootstrap-4') }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
