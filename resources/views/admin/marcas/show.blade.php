@extends('layouts.admin')

@section('title', $marca->nombre . ' · Marcas · ' . ($ajuste->nombre ?? 'TECTRON'))

@section('content')
    <div class="ga-page-head">
        <div class="ga-page-head-main">
            <div class="ga-eyebrow">Catálogo compartido</div>
            <h1 class="ga-page-title">{{ $marca->nombre }}</h1>
            <p class="ga-page-sub">Ficha de la marca: logo, estado y cantidad de productos asociados en la tienda y la app GALY.</p>
        </div>
        <div class="ga-head-actions">
            <a href="{{ route('web.marcas') }}" target="_blank" rel="noopener" class="btn-ga-ghost">
                <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Ver página pública
            </a>
            @can('Editar Marca')
                <a href="{{ route('admin.marcas.edit', $marca->id) }}" class="btn btn-primary">
                    <i class="bi bi-pencil" aria-hidden="true"></i> Editar marca
                </a>
            @endcan
            <a href="{{ route('admin.marcas.index') }}" class="btn-ga-ghost">
                <i class="bi bi-arrow-left" aria-hidden="true"></i> Volver
            </a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-5">
            <div class="ga-surface h-100">
                <div class="ga-card-head">
                    <div>
                        <h2>Logotipo</h2>
                        <p>Así se muestra en la tienda</p>
                    </div>
                    <span class="badge {{ $marca->activo ? 'bg-success' : 'bg-danger' }}">
                        {{ $marca->activo ? 'Activa' : 'Inactiva' }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="ga-logo-preview ga-logo-preview-lg">
                        @if ($marca->image_url)
                            <img src="{{ $marca->image_url }}" alt="Logo de {{ $marca->nombre }}">
                        @else
                            <span class="ga-logo-preview-empty">
                                <i class="bi bi-image" aria-hidden="true"></i>
                                <small>Esta marca aún no tiene logo</small>
                            </span>
                        @endif
                    </div>

                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                        <span class="ga-muted small">
                            <i class="bi bi-hash" aria-hidden="true"></i> {{ $marca->slug }}
                        </span>
                        <a href="{{ route('web.productos', ['marca' => $marca->slug]) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-grid" aria-hidden="true"></i> Ver productos
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-7">
            <div class="ga-surface h-100">
                <div class="ga-card-head">
                    <div>
                        <h2>Detalle de la marca</h2>
                        <p>Información registrada en el catálogo</p>
                    </div>
                </div>
                <div class="card-body">
                    <dl class="ga-detail-list">
                        <div class="ga-detail-row">
                            <dt>Nombre</dt>
                            <dd>{{ $marca->nombre }}</dd>
                        </div>
                        <div class="ga-detail-row">
                            <dt>Slug</dt>
                            <dd><code>{{ $marca->slug }}</code></dd>
                        </div>
                        <div class="ga-detail-row">
                            <dt>Estado</dt>
                            <dd>
                                <span class="badge {{ $marca->activo ? 'bg-success' : 'bg-danger' }}">
                                    {{ $marca->activo ? 'Activa y visible' : 'Inactiva u oculta' }}
                                </span>
                            </dd>
                        </div>
                        <div class="ga-detail-row">
                            <dt>Productos asociados</dt>
                            <dd>
                                <span class="badge bg-primary">{{ number_format($marca->productos_count) }}</span>
                            </dd>
                        </div>
                        <div class="ga-detail-row">
                            <dt>Registrada</dt>
                            <dd class="ga-muted">
                                <i class="bi bi-calendar3" aria-hidden="true"></i>
                                {{ $marca->created_at?->format('d/m/Y H:i') }}
                            </dd>
                        </div>
                        <div class="ga-detail-row">
                            <dt>Última modificación</dt>
                            <dd class="ga-muted">
                                <i class="bi bi-clock-history" aria-hidden="true"></i>
                                {{ $marca->updated_at?->format('d/m/Y H:i') }}
                            </dd>
                        </div>
                    </dl>

                    <div class="d-flex flex-wrap gap-2 mt-4 pt-3 border-top" style="border-color: var(--ga-line) !important;">
                        <form action="{{ route('admin.marcas.toggle', $marca->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn {{ $marca->activo ? 'btn-outline-warning' : 'btn-outline-success' }} btn-sm">
                                <i class="bi bi-{{ $marca->activo ? 'toggle-off' : 'toggle-on' }}" aria-hidden="true"></i>
                                {{ $marca->activo ? 'Desactivar marca' : 'Activar marca' }}
                            </button>
                        </form>
                        @can('Editar Marca')
                            <a href="{{ route('admin.marcas.edit', $marca->id) }}" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-pencil" aria-hidden="true"></i> Editar
                            </a>
                        @endcan
                        @can('Eliminar Marca')
                            <form action="{{ route('admin.marcas.destroy', $marca->id) }}" method="POST"
                                onsubmit="return confirm('¿Desactivar la marca {{ $marca->nombre }}? Se conservarán sus productos.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-trash" aria-hidden="true"></i> Desactivar y ocultar
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
