@extends('layouts.admin')

@section('title', 'Nueva marca · ' . ($ajuste->nombre ?? 'TECTRON'))

@section('content')
    <div class="ga-page-head">
        <div class="ga-page-head-main">
            <div class="ga-eyebrow">Catálogo compartido</div>
            <h1 class="ga-page-title">Nueva marca</h1>
            <p class="ga-page-sub">Registra un fabricante y su logo. Aparecerá en la página de marcas, en los filtros del catálogo y en la app GALY.</p>
        </div>
        <div class="ga-head-actions">
            <a href="{{ route('admin.marcas.index') }}" class="btn-ga-ghost">
                <i class="bi bi-arrow-left" aria-hidden="true"></i> Volver al listado
            </a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-7">
            <div class="ga-surface">
                <div class="ga-card-head">
                    <div>
                        <h2>Datos de la marca</h2>
                        <p>Los campos marcados con (*) son obligatorios</p>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.marcas.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre de la marca (*)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-award" aria-hidden="true"></i></span>
                                <input type="text" name="nombre" id="nombre"
                                    class="form-control @error('nombre') is-invalid @enderror"
                                    value="{{ old('nombre') }}" placeholder="Ej.: ASUS, Logitech, Razer" required
                                    autocomplete="off">
                            </div>
                            @error('nombre')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="ga-muted d-block mt-2">
                                El nombre debe ser único. El slug se genera automáticamente.
                            </small>
                        </div>

                        <div class="mb-3">
                            <label for="imagen" class="form-label">Logotipo de la marca</label>
                            <input type="file" name="imagen" id="imagen"
                                class="form-control @error('imagen') is-invalid @enderror"
                                accept="image/jpeg,image/png,image/webp">
                            @error('imagen')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="ga-muted d-block mt-2">
                                JPG, PNG o WEBP · máximo 2 MB. Si no subes logo, la tienda mostrará las iniciales.
                            </small>
                        </div>

                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="activo" id="activo"
                                    value="1" {{ old('activo', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="activo">
                                    Marca activa (visible en la tienda)
                                </label>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check2" aria-hidden="true"></i> Registrar marca
                            </button>
                            <a href="{{ route('admin.marcas.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="ga-surface h-100">
                <div class="ga-card-head">
                    <div>
                        <h2>Vista previa del logo</h2>
                        <p>Así se vería dentro de la tienda</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="ga-logo-preview" id="logoPreview">
                        <span class="ga-logo-preview-empty">
                            <i class="bi bi-image" aria-hidden="true"></i>
                            <small>Sin logo seleccionado</small>
                        </span>
                    </div>

                    <div class="ga-note mt-3">
                        <i class="bi bi-lightbulb" aria-hidden="true"></i>
                        <span>
                            <strong>Recomendación:</strong> usa un logo horizontal con fondo transparente.
                            Un tamaño de 400 × 200 px se ve nítido en la web y en la app.
                        </span>
                    </div>

                    <ul class="ga-list-reset d-grid gap-2 mt-3 small ga-muted">
                        <li><i class="bi bi-check2-circle text-success" aria-hidden="true"></i> Se mostrará en la página de marcas.</li>
                        <li><i class="bi bi-check2-circle text-success" aria-hidden="true"></i> Se usará en los filtros del catálogo.</li>
                        <li><i class="bi bi-check2-circle text-success" aria-hidden="true"></i> Se sincroniza con la app GALY.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('imagen')?.addEventListener('change', function (event) {
            const archivo = event.target.files && event.target.files[0];
            const caja = document.getElementById('logoPreview');
            if (!archivo || !caja) return;

            const url = URL.createObjectURL(archivo);
            caja.innerHTML = '';
            const img = document.createElement('img');
            img.src = url;
            img.alt = 'Vista previa del logo seleccionado';
            img.onload = function () { URL.revokeObjectURL(url); };
            caja.appendChild(img);
        });
    </script>
@endsection
