@extends('layouts.admin')

@section('title', 'Editar ' . $marca->nombre . ' · ' . ($ajuste->nombre ?? 'TECTRON'))

@section('content')
    <div class="ga-page-head">
        <div class="ga-page-head-main">
            <div class="ga-eyebrow">Catálogo compartido</div>
            <h1 class="ga-page-title">Editar marca: {{ $marca->nombre }}</h1>
            <p class="ga-page-sub">Actualiza el nombre, el logo o el estado de la marca. Los cambios se reflejan en la tienda y en la app GALY.</p>
        </div>
        <div class="ga-head-actions">
            <a href="{{ route('admin.marcas.show', $marca->id) }}" class="btn-ga-ghost">
                <i class="bi bi-eye" aria-hidden="true"></i> Ver detalle
            </a>
            <a href="{{ route('admin.marcas.index') }}" class="btn-ga-ghost">
                <i class="bi bi-arrow-left" aria-hidden="true"></i> Volver
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
                    <form action="{{ route('admin.marcas.update', $marca->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre de la marca (*)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-award" aria-hidden="true"></i></span>
                                <input type="text" name="nombre" id="nombre"
                                    class="form-control @error('nombre') is-invalid @enderror"
                                    value="{{ old('nombre', $marca->nombre) }}" placeholder="Ej.: ASUS, Logitech, Razer"
                                    required autocomplete="off">
                            </div>
                            @error('nombre')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="ga-muted d-block mt-2">
                                Slug actual: <code>{{ $marca->slug }}</code>. Se recalcula si cambias el nombre.
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
                                Sube una imagen solo si quieres reemplazar el logo actual. JPG, PNG o WEBP · máximo 2 MB.
                            </small>
                        </div>

                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="activo" id="activo"
                                    value="1" {{ old('activo', $marca->activo) ? 'checked' : '' }}>
                                <label class="form-check-label" for="activo">
                                    Marca activa (visible en la tienda)
                                </label>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check2" aria-hidden="true"></i> Guardar cambios
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
                        <h2>Logo actual</h2>
                        <p>Vista previa dentro de la tienda</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="ga-logo-preview" id="logoPreview">
                        @if ($marca->image_url)
                            <img src="{{ $marca->image_url }}" alt="Logo actual de {{ $marca->nombre }}">
                        @else
                            <span class="ga-logo-preview-empty">
                                <i class="bi bi-image" aria-hidden="true"></i>
                                <small>Esta marca aún no tiene logo</small>
                            </span>
                        @endif
                    </div>

                    <div class="ga-note mt-3">
                        <i class="bi bi-info-circle" aria-hidden="true"></i>
                        <span>
                            <strong>Nota:</strong> el logo anterior se elimina automáticamente cuando subes uno nuevo.
                            Si no subes nada, se conserva el actual.
                        </span>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <a href="{{ route('web.marcas') }}" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Ver página pública
                        </a>
                        @can('Eliminar Marca')
                            <form action="{{ route('admin.marcas.destroy', $marca->id) }}" method="POST"
                                onsubmit="return confirm('¿Desactivar la marca {{ $marca->nombre }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-trash" aria-hidden="true"></i> Desactivar marca
                                </button>
                            </form>
                        @endcan
                    </div>
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
            img.alt = 'Vista previa del nuevo logo';
            img.onload = function () { URL.revokeObjectURL(url); };
            caja.appendChild(img);
        });
    </script>
@endsection
