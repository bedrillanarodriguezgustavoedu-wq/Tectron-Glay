@extends('layouts.admin')

@section('content')
    <h1>Editar Slider del Carrusel: {{ $carrusel->titulo ?? 'Sin título' }}</h1>
    <hr>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light">
                    <h4 class="mb-0 text-dark">Formulario de Edición</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.carruseles.update', $carrusel->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="form-group mb-3">
                            <label for="imagen" class="form-label fw-bold">Imagen del Slider (Suba una nueva para reemplazar)</label>
                            @if($carrusel->imagen)
                                <div class="mb-2">
                                    <img src="{{ asset('storage/' . $carrusel->imagen) }}" alt="Slider actual"
                                        style="max-width: 100%; height: auto; max-height: 150px; object-fit: cover;" class="img-thumbnail d-block">
                                </div>
                            @endif
                            <input type="file" name="imagen" id="imagen" class="form-control" accept="image/*">
                            <small class="text-muted">Resolución recomendada: 1920x600px o similar. Formatos: JPEG, PNG, JPG, WEBP. Máx. 2MB.</small>
                            @error('imagen')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="titulo" class="form-label fw-bold">Título de la Superposición (Overlay)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-type-h1"></i></span>
                                <input type="text" name="titulo" id="titulo" class="form-control"
                                    value="{{ old('titulo', $carrusel->titulo) }}" placeholder="ej: NUEVAS NOTEBOOKS GAMER, ENVÍO GRATIS">
                            </div>
                            <small class="text-muted">Opcional. Texto en formato de título que se muestra sobre el slider.</small>
                            @error('titulo')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="descripcion" class="form-label fw-bold">Descripción de la Superposición (Overlay)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-justify-left"></i></span>
                                <textarea name="descripcion" id="descripcion" class="form-control" rows="2"
                                    placeholder="ej: Compra ahora y obtén un 20% de descuento.">{{ old('descripcion', $carrusel->descripcion) }}</textarea>
                            </div>
                            <small class="text-muted">Opcional. Texto en formato de párrafo descriptivo.</small>
                            @error('descripcion')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="enlace" class="form-label fw-bold">Enlace de Botón (URL)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-link-45deg"></i></span>
                                <input type="url" name="enlace" id="enlace" class="form-control"
                                    value="{{ old('enlace', $carrusel->enlace) }}" placeholder="ej: https://mitienda.com/productos">
                            </div>
                            <small class="text-muted">Opcional. Si lo ingresa, se agregará un botón que dirija a esta URL.</small>
                            @error('enlace')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="orden" class="form-label fw-bold">Orden de Visualización</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-sort-numeric-down"></i></span>
                                <input type="number" name="orden" id="orden" class="form-control"
                                    value="{{ old('orden', $carrusel->orden) }}" min="0" required>
                            </div>
                            @error('orden')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group mb-4">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1" {{ old('activo', $carrusel->activo) ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="activo">Slider Activo</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <a href="{{ route('admin.carruseles.index') }}" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-success">Actualizar Slider</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
