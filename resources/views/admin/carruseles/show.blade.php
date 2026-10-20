@extends('layouts.admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Datos del Slider: {{ $carrusel->titulo ?? 'Sin título' }}</h1>
        <a href="{{ route('admin.carruseles.index') }}" class="btn btn-secondary">Volver</a>
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
                        <div class="col-md-4 text-muted fw-bold">Título:</div>
                        <div class="col-md-8 fw-semibold text-dark fs-5">{{ $carrusel->titulo ?? 'Sin título' }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 text-muted fw-bold">Descripción:</div>
                        <div class="col-md-8 text-dark">{{ $carrusel->descripcion ?? 'Sin descripción' }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 text-muted fw-bold">Enlace del Botón:</div>
                        <div class="col-md-8">
                            @if ($carrusel->enlace)
                                <a href="{{ $carrusel->enlace }}" target="_blank" class="fw-medium text-decoration-none">
                                    <i class="bi bi-box-arrow-up-right me-1"></i> {{ $carrusel->enlace }}
                                </a>
                            @else
                                <span class="text-muted">Sin enlace de redirección</span>
                            @endif
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 text-muted fw-bold">Orden:</div>
                        <div class="col-md-8"><span class="badge bg-secondary px-3 py-1 rounded">{{ $carrusel->orden }}</span></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 text-muted fw-bold">Estado:</div>
                        <div class="col-md-8">
                            @if ($carrusel->activo)
                                <span class="badge bg-success px-3 py-2 rounded-pill">Activo</span>
                            @else
                                <span class="badge bg-danger px-3 py-2 rounded-pill">Inactivo</span>
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
                    @if ($carrusel->imagen)
                        <img src="{{ asset('storage/' . $carrusel->imagen) }}" alt="Slider {{ $carrusel->titulo }}"
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
