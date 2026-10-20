@extends('layouts.admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1>Administración de "Sobre Nosotros"</h1>
            <p class="text-muted mb-0">El contenido se comparte entre la web y la app GALY. La imagen principal se administra desde aquí.</p>
        </div>
    </div>
    <hr>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="{{ route('admin.nosotros.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="titulo_principal" class="form-label fw-bold">Título Principal</label>
                                <input type="text" name="titulo_principal" id="titulo_principal" class="form-control"
                                    value="{{ old('titulo_principal', $nosotros->titulo_principal ?? '') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label for="subtitulo" class="form-label fw-bold">Subtítulo (Badge)</label>
                                <input type="text" name="subtitulo" id="subtitulo" class="form-control"
                                    value="{{ old('subtitulo', $nosotros->subtitulo ?? '') }}" required>
                            </div>

                            <div class="col-12">
                                <label for="descripcion_principal" class="form-label fw-bold">Descripción Principal</label>
                                <textarea name="descripcion_principal" id="descripcion_principal" class="form-control" rows="3" required>{{ old('descripcion_principal', $nosotros->descripcion_principal ?? '') }}</textarea>
                            </div>

                            <div class="col-12">
                                <label for="historia" class="form-label fw-bold">Historia de la Empresa</label>
                                <textarea name="historia" id="historia" class="form-control" rows="4" required>{{ old('historia', $nosotros->historia ?? '') }}</textarea>
                            </div>

                            <div class="col-md-4">
                                <label for="mision" class="form-label fw-bold">Misión</label>
                                <textarea name="mision" id="mision" class="form-control" rows="3" required>{{ old('mision', $nosotros->mision ?? '') }}</textarea>
                            </div>
                            <div class="col-md-4">
                                <label for="vision" class="form-label fw-bold">Visión</label>
                                <textarea name="vision" id="vision" class="form-control" rows="3" required>{{ old('vision', $nosotros->vision ?? '') }}</textarea>
                            </div>
                            <div class="col-md-4">
                                <label for="valores" class="form-label fw-bold">Valores (Separados por comas)</label>
                                <textarea name="valores" id="valores" class="form-control" rows="3" required>{{ old('valores', $nosotros->valores ?? '') }}</textarea>
                            </div>

                            <div class="col-md-4">
                                <label for="experiencia_anos" class="form-label fw-bold">Años de Experiencia</label>
                                <input type="number" name="experiencia_anos" id="experiencia_anos" class="form-control"
                                    value="{{ old('experiencia_anos', $nosotros->experiencia_anos ?? 0) }}" min="0" required>
                            </div>
                            <div class="col-md-4">
                                <label for="clientes_count" class="form-label fw-bold">Cantidad de Clientes</label>
                                <input type="number" name="clientes_count" id="clientes_count" class="form-control"
                                    value="{{ old('clientes_count', $nosotros->clientes_count ?? 0) }}" min="0" required>
                            </div>
                            <div class="col-md-4">
                                <label for="productos_count" class="form-label fw-bold">Cantidad de Productos</label>
                                <input type="number" name="productos_count" id="productos_count" class="form-control"
                                    value="{{ old('productos_count', $nosotros->productos_count ?? 0) }}" min="0" required>
                            </div>

                            <div class="col-md-6">
                                <label for="video_url" class="form-label fw-bold">Enlace de Video Institucional (YouTube, Vimeo - opcional)</label>
                                <input type="url" name="video_url" id="video_url" class="form-control"
                                    value="{{ old('video_url', $nosotros->video_url ?? '') }}" placeholder="https://youtube.com/...">
                            </div>

                            <div class="col-md-6">
                                <label for="imagen_principal" class="form-label fw-bold">Imagen Principal</label>
                                <input type="file" name="imagen_principal" id="imagen_principal" class="form-control" accept="image/*">
                                @if ($nosotros && $nosotros->imagen_principal && file_exists(public_path('storage/' . $nosotros->imagen_principal)))
                                    <div class="mt-2">
                                        <img src="{{ asset('storage/' . $nosotros->imagen_principal) }}" alt="Preview" class="img-thumbnail" style="max-height: 120px;">
                                    </div>
                                @endif
                            </div>

                            <div class="col-12 mt-3">
                                <div class="form-check form-switch fs-5">
                                    <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1"
                                        {{ old('activo', $nosotros->activo ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold text-dark" for="activo">Sección "Nosotros" activa (Pública)</label>
                                </div>
                            </div>
                        </div>

                        @can('Editar Nosotros')
                            <div class="mt-4 text-end">
                                <button type="submit" class="btn btn-primary px-5 py-2">
                                    <i class="bi bi-save"></i> Guardar Cambios
                                </button>
                            </div>
                        @else
                            <div class="alert alert-warning mt-3 py-2 px-3 fs-6">
                                <i class="bi bi-exclamation-triangle"></i> No tienes permisos para editar este módulo.
                            </div>
                        @endcan
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
