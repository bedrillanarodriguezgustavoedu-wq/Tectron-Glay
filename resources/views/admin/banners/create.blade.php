@extends('layouts.admin')

@section('content')
    <h1>Crear Nuevo Banner</h1>
    <hr>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light">
                    <h4 class="mb-0 text-dark">Formulario de Registro</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.banners.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="form-group mb-3">
                            <label for="nombre" class="form-label fw-bold">Nombre del Banner (*)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-card-image"></i></span>
                                <input type="text" name="titulo" id="titulo" class="form-control"
                                    value="{{ old('titulo') }}" placeholder="ej: Oferta de laptops" required>
                            </div>
                            @error('titulo')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="subtitulo" class="form-label fw-bold">Subtítulo</label>
                            <input type="text" name="subtitulo" id="subtitulo" class="form-control"
                                value="{{ old('subtitulo') }}" maxlength="255">
                            @error('subtitulo')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="boton_texto" class="form-label fw-bold">Texto del botón</label>
                            <input type="text" name="boton_texto" id="boton_texto" class="form-control"
                                value="{{ old('boton_texto', 'VER CATÁLOGO') }}" maxlength="100">
                            @error('boton_texto')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="imagen" class="form-label fw-bold">Imagen del Banner (*)</label>
                            <input type="file" name="imagen" id="imagen" class="form-control" accept="image/*" required>
                            <small class="text-muted">Resolución recomendada: 1200x400px (aprox. 3:1). Formatos: JPEG, PNG, JPG, WEBP. Máx. 2MB.</small>
                            @error('imagen')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="enlace" class="form-label fw-bold">Enlace de Redirección (URL)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-link-45deg"></i></span>
                                <input type="url" name="enlace" id="enlace" class="form-control"
                                    value="{{ old('enlace') }}" placeholder="ej: https://mitienda.com/categorias/notebooks">
                            </div>
                            <small class="text-muted">Deje en blanco si el banner no redirigirá a ninguna página.</small>
                            @error('enlace')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="color_fondo" class="form-label fw-bold">Color de fondo en la App</label>
                            <input type="color" name="color_fondo" id="color_fondo" class="form-control form-control-color"
                                value="{{ old('color_fondo', '#FF0055') }}">
                            @error('color_fondo')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="orden" class="form-label fw-bold">Orden de Visualización</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-sort-numeric-down"></i></span>
                                        <input type="number" name="orden" id="orden" class="form-control"
                                            value="{{ old('orden', 0) }}" min="0" required>
                                    </div>
                                    @error('orden')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="fecha_publicacion" class="form-label fw-bold">Fecha de Publicación</label>
                                    <input type="datetime-local" name="fecha_publicacion" id="fecha_publicacion" class="form-control"
                                        value="{{ old('fecha_publicacion') }}">
                                    <small class="text-muted">Opcional. Deje en blanco para publicación inmediata.</small>
                                    @error('fecha_publicacion')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1" checked>
                                <label class="form-check-label fw-bold" for="activo">Banner Activo (Visible según fecha)</label>
                            </div>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="portada" id="portada" value="1" {{ old('portada') ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="portada">Usar como portada de bienvenida (inicio)</label>
                                <div><small class="text-muted">La portada aparece como la primera diapositiva del carrusel de inicio.</small></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <a href="{{ route('admin.banners.index') }}" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Registrar Banner</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
