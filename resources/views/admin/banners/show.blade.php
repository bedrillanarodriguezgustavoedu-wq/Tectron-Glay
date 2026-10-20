@extends('layouts.admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Datos del Banner: {{ $banner->titulo }}</h1>
        <a href="{{ route('admin.banners.index') }}" class="btn btn-secondary">Volver</a>
    </div>
    <hr>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light">
                    <h4 class="mb-0 text-dark fw-bold">Información Registrada</h4>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4 text-muted fw-bold">Nombre:</div>
                        <div class="col-md-8 fw-semibold text-dark fs-5">{{ $banner->titulo }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 text-muted fw-bold">Enlace:</div>
                        <div class="col-md-8">
                            @if ($banner->enlace)
                                <a href="{{ $banner->enlace }}" target="_blank" class="fw-medium text-decoration-none">
                                    <i class="bi bi-box-arrow-up-right me-1"></i> {{ $banner->enlace }}
                                </a>
                            @else
                                <span class="text-muted">Sin enlace de redirección</span>
                            @endif
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 text-muted fw-bold">Orden:</div>
                        <div class="col-md-8"><span class="badge bg-secondary px-3 py-1 rounded">{{ $banner->orden }}</span></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 text-muted fw-bold">Fecha Programada:</div>
                        <div class="col-md-8 text-dark">
                            <i class="bi bi-calendar-event me-1"></i>
                            @if ($banner->fecha_publicacion)
                                {{ \Carbon\Carbon::parse($banner->fecha_publicacion)->format('d/m/Y H:i:s') }}
                            @else
                                <span class="text-success">Inmediata (Sin programar)</span>
                            @endif
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 text-muted fw-bold">Estado Admin:</div>
                        <div class="col-md-8">
                            @if ($banner->activo)
                                <span class="badge bg-success px-3 py-2 rounded-pill">Activo</span>
                            @else
                                <span class="badge bg-danger px-3 py-2 rounded-pill">Inactivo</span>
                            @endif
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 text-muted fw-bold">Estado Visual:</div>
                        <div class="col-md-8">
                            @php
                                $fechaPublicacion = $banner->fecha_publicacion;
                                $visible = $banner->activo && (! $fechaPublicacion || $fechaPublicacion->lte(now()));
                            @endphp
                            @if ($visible)
                                <span class="badge bg-success px-3 py-2 rounded-pill"><i class="bi bi-eye-fill"></i> Visible en Tienda</span>
                            @elseif ($banner->activo && $fechaPublicacion?->isFuture())
                                <span class="badge bg-info text-dark px-3 py-2 rounded-pill"
                                    title="Este banner se publicará automáticamente en la fecha indicada">
                                    <i class="bi bi-calendar-event"></i> Programado para {{ $fechaPublicacion->format('d/m/Y H:i') }}
                                </span>
                            @else
                                <span class="badge bg-danger px-3 py-2 rounded-pill"><i class="bi bi-eye-slash-fill"></i> Inactivo en Tienda</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light">
                    <h4 class="mb-0 text-dark fw-bold">Vista Previa de Imagen</h4>
                </div>
                <div class="card-body text-center bg-dark p-4 rounded-bottom">
                    @if ($banner->imagen)
                        <img src="{{ $banner->image_url }}" alt="Imagen {{ $banner->titulo }}"
                            style="max-width: 100%; height: auto; max-height: 250px; object-fit: contain;" class="shadow img-thumbnail">
                    @else
                        <div class="p-5 border border-secondary text-muted">
                            <i class="bi bi-image fs-1 d-block mb-2"></i>
                            Sin Imagen Registrada
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
