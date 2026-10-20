@extends('layouts.admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Datos de la Marca: {{ $marca->nombre }}</h1>
        <a href="{{ route('admin.marcas.index') }}" class="btn btn-secondary">Volver</a>
    </div>
    <hr>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light">
                    <h4 class="mb-0 text-dark fw-bold">Detalle Registrado</h4>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4 text-muted fw-bold">Nombre:</div>
                        <div class="col-md-8 fw-semibold text-dark fs-5">{{ $marca->nombre }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 text-muted fw-bold">Slug:</div>
                        <div class="col-md-8 text-primary"><code>{{ $marca->slug }}</code></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 text-muted fw-bold">Estado:</div>
                        <div class="col-md-8">
                            @if ($marca->activo)
                                <span class="badge bg-success px-3 py-2 rounded-pill">Activa</span>
                            @else
                                <span class="badge bg-danger px-3 py-2 rounded-pill">Inactiva</span>
                            @endif
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 text-muted fw-bold">Fecha de Registro:</div>
                        <div class="col-md-8 text-muted"><i class="bi bi-clock"></i> {{ $marca->created_at->format('d/m/Y H:i:s') }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 text-muted fw-bold">Última Modificación:</div>
                        <div class="col-md-8 text-muted"><i class="bi bi-clock-history"></i> {{ $marca->updated_at->format('d/m/Y H:i:s') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light">
                    <h4 class="mb-0 text-dark fw-bold">Logotipo</h4>
                </div>
                <div class="card-body text-center">
                    @if ($marca->image_url)
                        <div class="p-3 border rounded bg-white d-inline-block">
                            <x-marca-logo :marca="$marca" width="250px" height="150px" />
                        </div>
                    @else
                        <div class="p-5 border rounded bg-light text-muted">
                            <x-marca-logo :marca="$marca" width="100px" height="80px" class="mb-2" />
                            <div>Sin logotipo disponible</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
