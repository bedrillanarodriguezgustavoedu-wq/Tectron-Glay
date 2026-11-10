@extends('layouts.admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Listado de Banners Promocionales</h1>
        @can('Crear Banner')
            <a href="{{ route('admin.banners.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Crear Nuevo Banner
            </a>
        @endcan
    </div>
    <hr>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light">
                    <h4 class="mb-0 text-dark">Banners publicados</h4>
                </div>

                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <form action="{{ route('admin.banners.index') }}" method="GET">
                                <div class="input-group">
                                    <input type="text" name="buscar" class="form-control" placeholder="Buscar por nombre..."
                                        value="{{ request('buscar') }}">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search"></i> Buscar
                                    </button>
                                    @if (request('buscar'))
                                        <a href="{{ route('admin.banners.index') }}" class="btn btn-success">
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
                                    <th style="width: 200px; text-align: center;">Banner</th>
                                    <th>Nombre del Banner</th>
                                    <th>Enlace de Redirección</th>
                                    <th style="text-align: center;">Orden</th>
                                    <th style="text-align: center;">Publicación</th>
                                    <th style="text-align: center;">Estado Admin</th>
                                    <th style="text-align: center;">Estado Visual</th>
                                    <th style="width: 250px; text-align: center;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $nro = ($banners->currentPage() - 1) * $banners->perPage() + 1;
                                @endphp
                                @forelse ($banners as $banner)
                                    <tr>
                                        <td style="text-align: center;">{{ $nro++ }}</td>
                                        <td style="text-align: center;">
                                            @if ($banner->imagen)
                                                <img src="{{ $banner->image_url }}" alt="Banner {{ $banner->titulo }}"
                                                    style="max-width: 180px; height: auto; border-radius: 4px; max-height: 80px; object-fit: cover;">
                                            @else
                                                <span class="text-muted">Sin imagen</span>
                                            @endif
                                        </td>
                                        <td class="fw-bold">
                                            {{ $banner->titulo }}
                                            @if ($banner->portada)
                                                <span class="badge bg-primary ms-1"><i class="bi bi-house-door-fill"></i> Portada</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($banner->enlace)
                                                <a href="{{ $banner->enlace }}" target="_blank" class="text-truncate d-inline-block" style="max-width: 200px;">
                                                    {{ $banner->enlace }}
                                                </a>
                                            @else
                                                <span class="text-muted">Ninguno</span>
                                            @endif
                                        </td>
                                        <td style="text-align: center;">
                                            <span class="badge bg-secondary px-3 py-1 rounded">{{ $banner->orden }}</span>
                                        </td>
                                        <td style="text-align: center; font-size: 13px;">
                                            @if ($banner->fecha_publicacion)
                                                {{ \Carbon\Carbon::parse($banner->fecha_publicacion)->format('d/m/Y H:i') }}
                                            @else
                                                <span class="badge bg-success">Inmediata</span>
                                            @endif
                                        </td>
                                        <td style="text-align: center;">
                                            <form action="{{ route('admin.banners.toggle', $banner->id) }}" method="POST">
                                                @csrf
                                                @if ($banner->activo)
                                                    <button type="submit" class="btn btn-success btn-sm px-3 rounded-pill">Activo</button>
                                                @else
                                                    <button type="submit" class="btn btn-danger btn-sm px-3 rounded-pill">Inactivo</button>
                                                @endif
                                            </form>
                                        </td>
                                        <td style="text-align: center;">
                                            @php
                                                $fechaPublicacion = $banner->fecha_publicacion;
                                                $visible = $banner->activo && (! $fechaPublicacion || $fechaPublicacion->lte(now()));
                                            @endphp
                                            @if ($visible)
                                                <span class="badge bg-success"><i class="bi bi-eye-fill"></i> Visible</span>
                                            @elseif ($banner->activo && $fechaPublicacion?->isFuture())
                                                <span class="badge bg-info text-dark" title="Este banner se publicará automáticamente en la fecha indicada">
                                                    <i class="bi bi-calendar-event"></i> Programado
                                                </span>
                                            @else
                                                <span class="badge bg-danger"><i class="bi bi-eye-slash-fill"></i> Inactivo</span>
                                            @endif
                                        </td>
                                        <td style="text-align: center;">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('admin.banners.show', $banner->id) }}" class="btn btn-info">
                                                    <i class="bi bi-eye"></i> Ver
                                                </a>
                                                @can('Editar Banner')
                                                    <a href="{{ route('admin.banners.edit', $banner->id) }}" class="btn btn-success">
                                                        <i class="bi bi-pencil"></i> Editar
                                                    </a>
                                                @endcan
                                                @can('Eliminar Banner')
                                                    <button type="button" class="btn btn-danger" onclick="confirmarEliminar{{ $banner->id }}(event)">
                                                        <i class="bi bi-trash"></i> Eliminar
                                                    </button>
                                                @endcan
                                            </div>

                                            @can('Eliminar Banner')
                                                <form action="{{ route('admin.banners.destroy', $banner->id) }}" method="POST" id="form-eliminar-{{ $banner->id }}" style="display: none;">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>

                                                <script>
                                                    function confirmarEliminar{{ $banner->id }}(event) {
                                                        event.preventDefault();
                                                        Swal.fire({
                                                            title: '¿Desea eliminar el banner "{{ $banner->titulo }}"?',
                                                            text: "Esta acción retirará la promoción de la página de inicio.",
                                                            icon: 'warning',
                                                            showCancelButton: true,
                                                            confirmButtonColor: '#d33',
                                                            cancelButtonColor: '#3085d6',
                                                            confirmButtonText: 'Sí, eliminar',
                                                            cancelButtonText: 'Cancelar'
                                                        }).then((result) => {
                                                            if (result.isConfirmed) {
                                                                document.getElementById('form-eliminar-{{ $banner->id }}').submit();
                                                            }
                                                        });
                                                    }
                                                </script>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-4 text-muted">
                                            No se encontraron banners promocionales registrados.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($banners->hasPages())
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="text-muted">
                                Mostrando {{ $banners->firstItem() }} a {{ $banners->lastItem() }} de {{ $banners->total() }} banners
                            </div>
                            <div>
                                {{ $banners->appends(request()->query())->links('pagination::bootstrap-4') }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
