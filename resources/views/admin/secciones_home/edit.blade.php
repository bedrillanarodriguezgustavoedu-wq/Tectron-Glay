@extends('layouts.admin')

@section('content')
    <h1>Configurar Sección: {{ $seccion->titulo }}</h1>
    <hr>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light">
                    <h4 class="mb-0 text-dark">Ajustes del Componente</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.secciones_home.update', $seccion->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-group mb-3">
                            <label for="titulo" class="form-label fw-bold">Título de la Sección (*)</label>
                            <input type="text" name="titulo" id="titulo" class="form-control @error('titulo') is-invalid @enderror" value="{{ old('titulo', $seccion->titulo) }}" required>
                            @error('titulo')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label fw-bold">Identificador Clave (Utilizado en código)</label>
                            <input type="text" class="form-control bg-light" value="{{ $seccion->clave }}" readonly disabled>
                        </div>

                        <div class="form-group mb-3">
                            <label for="orden" class="form-label fw-bold">Orden de Visualización (*)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-sort-numeric-down"></i></span>
                                <input type="number" name="orden" id="orden" class="form-control"
                                    value="{{ old('orden', $seccion->orden) }}" min="1" required>
                            </div>
                            <small class="text-muted">Las secciones se ordenarán de forma ascendente en la página de inicio (ej: 1 se muestra arriba, 6 abajo).</small>
                            @error('orden')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="limite" class="form-label fw-bold">Cantidad Límite de Elementos (*)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-hash"></i></span>
                                <input type="number" name="limite" id="limite" class="form-control"
                                    value="{{ old('limite', $seccion->limite) }}" min="1" max="50" required>
                            </div>
                            <small class="text-muted">Número máximo de productos, marcas o categorías a mostrar en esta sección.</small>
                            @error('limite')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group mb-4">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1" {{ old('activo', $seccion->activo) ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="activo">Sección Visible en el Home</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <a href="{{ route('admin.secciones_home.index') }}" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-success">Guardar Configuración</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
