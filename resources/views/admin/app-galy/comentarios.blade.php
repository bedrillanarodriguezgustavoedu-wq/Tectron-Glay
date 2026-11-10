@extends('layouts.admin')

@section('title', 'Comentarios de la app · ' . ($ajuste->nombre ?? 'TECTRON'))

@section('content')
    @php
        $coleccion = $comentarios->getCollection();
        $promedio = $coleccion->count() > 0 ? (float) $coleccion->avg(fn ($item) => (int) $item->estrellas) : 0.0;
        $cincoEstrellas = $coleccion->filter(fn ($item) => (int) $item->estrellas === 5)->count();
        $conTexto = $coleccion->filter(fn ($item) => trim((string) $item->comentario) !== '')->count();
    @endphp

    <div class="ga-page-head">
        <div class="ga-page-head-main">
            <div class="ga-eyebrow">App GALY</div>
            <h1 class="ga-page-title">Comentarios de la app</h1>
            <p class="ga-page-sub">Reseñas que dejaron los clientes desde la app Android. Puedes editar el texto o eliminar una reseña; los cambios se reflejan al instante en GALY.</p>
        </div>
        <div class="ga-head-actions">
            <a class="btn-ga-ghost" href="{{ route('admin.app-galy.notificaciones') }}">
                <i class="bi bi-bell" aria-hidden="true"></i> Push
            </a>
            <span class="ga-count-pill">
                <i class="bi bi-chat-left-text" aria-hidden="true"></i>
                <strong>{{ number_format($comentarios->total()) }}</strong> comentarios
            </span>
        </div>
    </div>

    @if ($errors->has('comentario'))
        <div class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
            {{ $errors->first('comentario') }}
        </div>
    @endif

    {{-- Resumen de la página actual --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="ga-kpi">
                <div class="ga-kpi-top">
                    <div>
                        <p class="ga-kpi-label">Total de reseñas</p>
                        <p class="ga-kpi-value">{{ number_format($comentarios->total()) }}</p>
                    </div>
                    <span class="ga-kpi-icon"><i class="bi bi-chat-square-text-fill" aria-hidden="true"></i></span>
                </div>
                <span class="ga-kpi-foot"><i class="bi bi-phone" aria-hidden="true"></i> Desde la app Android</span>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="ga-kpi">
                <div class="ga-kpi-top">
                    <div>
                        <p class="ga-kpi-label">Promedio</p>
                        <p class="ga-kpi-value">{{ number_format($promedio, 1) }}</p>
                    </div>
                    <span class="ga-kpi-icon is-warning"><i class="bi bi-star-fill" aria-hidden="true"></i></span>
                </div>
                <span class="ga-kpi-foot"><i class="bi bi-star-half" aria-hidden="true"></i> En esta página</span>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="ga-kpi">
                <div class="ga-kpi-top">
                    <div>
                        <p class="ga-kpi-label">5 estrellas</p>
                        <p class="ga-kpi-value">{{ number_format($cincoEstrellas) }}</p>
                    </div>
                    <span class="ga-kpi-icon is-success"><i class="bi bi-emoji-smile-fill" aria-hidden="true"></i></span>
                </div>
                <span class="ga-kpi-foot"><i class="bi bi-hand-thumbs-up" aria-hidden="true"></i> En esta página</span>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="ga-kpi">
                <div class="ga-kpi-top">
                    <div>
                        <p class="ga-kpi-label">Con texto</p>
                        <p class="ga-kpi-value">{{ number_format($conTexto) }}</p>
                    </div>
                    <span class="ga-kpi-icon is-info"><i class="bi bi-pencil-square" aria-hidden="true"></i></span>
                </div>
                <span class="ga-kpi-foot"><i class="bi bi-eye" aria-hidden="true"></i> Reseñas visibles</span>
            </div>
        </div>
    </div>

    {{-- Buscador --}}
    <form action="{{ route('admin.app-galy.comentarios') }}" method="GET" class="ga-toolbar mb-3" role="search">
        <div class="ga-search">
            <i class="bi bi-search" aria-hidden="true"></i>
            <label class="visually-hidden" for="buscar-comentarios">Buscar comentarios</label>
            <input type="search" id="buscar-comentarios" name="buscar" class="form-control"
                placeholder="Buscar por comentario, cliente o producto" value="{{ $buscar }}">
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-search" aria-hidden="true"></i> Buscar
        </button>
        @if ($buscar !== '')
            <a href="{{ route('admin.app-galy.comentarios') }}" class="btn btn-outline-secondary">
                <i class="bi bi-x-lg" aria-hidden="true"></i> Limpiar
            </a>
        @endif
        <span class="ga-muted small ms-auto">
            <i class="bi bi-clock-history" aria-hidden="true"></i>
            {{ number_format($comentarios->total()) }} en total
        </span>
    </form>

    {{-- Listado --}}
    <div class="ga-surface ga-table-card">
        <div class="ga-card-head">
            <div>
                <h2>Reseñas recibidas</h2>
                <p>
                    @if ($buscar !== '')
                        Resultados para «{{ $buscar }}»
                    @else
                        Las más recientes primero
                    @endif
                </p>
            </div>
            @if ($comentarios->hasPages())
                <span class="badge bg-secondary">
                    Página {{ $comentarios->currentPage() }} de {{ $comentarios->lastPage() }}
                </span>
            @endif
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Producto</th>
                        <th scope="col">Cliente</th>
                        <th scope="col" class="text-center">Estrellas</th>
                        <th scope="col" style="min-width: 280px;">Comentario</th>
                        <th scope="col" class="text-center">Fecha</th>
                        <th scope="col" class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($comentarios as $comentario)
                        @php
                            $nombreCliente = $comentario->usuario_nombre ?: 'Cliente sin nombre';
                            $inicial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($nombreCliente, 0, 1));
                            $estrellas = max(0, min(5, (int) $comentario->estrellas));
                            $fecha = $comentario->fecha ? \Illuminate\Support\Carbon::parse($comentario->fecha) : null;
                        @endphp
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if (! empty($comentario->producto_imagen))
                                        <img src="{{ rtrim(config('galy.api_url'), '/') . '/img/' . rawurlencode(basename($comentario->producto_imagen)) }}"
                                            alt="" width="42" height="42" class="rounded object-fit-cover flex-shrink-0"
                                            onerror="this.style.display='none'">
                                    @else
                                        <span class="ga-avatar ga-avatar-sm is-soft flex-shrink-0">
                                            <i class="bi bi-box-seam" aria-hidden="true"></i>
                                        </span>
                                    @endif
                                    <span>
                                        <strong class="d-block small">{{ $comentario->producto_nombre ?: 'Producto no disponible' }}</strong>
                                        <small class="ga-muted">ID {{ $comentario->producto_id ?? '—' }}</small>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="ga-avatar ga-avatar-sm">{{ $inicial }}</span>
                                    <span>
                                        <strong class="d-block small">{{ $nombreCliente }}</strong>
                                        <small class="ga-muted">{{ $comentario->usuario_email ?: 'Sin correo' }}</small>
                                    </span>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="ga-stars" aria-label="{{ $estrellas }} de 5 estrellas">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <i class="bi {{ $i <= $estrellas ? 'bi-star-fill' : 'bi-star' }}" aria-hidden="true"></i>
                                    @endfor
                                    <strong>{{ $estrellas }}.0</strong>
                                </span>
                            </td>
                            <td>
                                <span class="ga-bubble d-block">
                                    {{ \Illuminate\Support\Str::limit(trim((string) $comentario->comentario), 170) ?: 'Sin texto.' }}
                                </span>
                                <details class="ga-disclosure">
                                    <summary>Editar comentario</summary>
                                    <form action="{{ route('admin.app-galy.comentarios.actualizar', $comentario->id) }}" method="POST" class="mt-2">
                                        @csrf
                                        @method('PUT')
                                        <label class="visually-hidden" for="comentario-{{ $comentario->id }}">Comentario</label>
                                        <textarea id="comentario-{{ $comentario->id }}" name="comentario" rows="3" maxlength="1000"
                                            class="form-control form-control-sm" required>{{ $comentario->comentario }}</textarea>
                                        <button type="submit" class="btn btn-sm btn-primary mt-2">
                                            <i class="bi bi-check2" aria-hidden="true"></i> Guardar cambios
                                        </button>
                                    </form>
                                </details>
                            </td>
                            <td class="text-center">
                                @if ($fecha)
                                    <strong class="d-block small">{{ $fecha->format('d/m/Y') }}</strong>
                                    <small class="ga-muted">{{ $fecha->diffForHumans() }}</small>
                                @else
                                    <span class="ga-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <form action="{{ route('admin.app-galy.comentarios.eliminar', $comentario->id) }}"
                                    method="POST" class="d-inline"
                                    onsubmit="return confirm('¿Eliminar este comentario de la app?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar comentario">
                                        <i class="bi bi-trash3" aria-hidden="true"></i>
                                        <span class="visually-hidden">Eliminar comentario</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="ga-empty">
                                    <span class="ga-empty-icon"><i class="bi bi-chat-square-dots" aria-hidden="true"></i></span>
                                    <strong>
                                        {{ $buscar !== '' ? 'No encontramos comentarios con ese criterio' : 'La app todavía no tiene comentarios' }}
                                    </strong>
                                    <p>
                                        {{ $buscar !== '' ? 'Prueba con otra palabra o limpia la búsqueda para ver todas las reseñas.' : 'Cuando los clientes califiquen productos desde la app Android, sus reseñas aparecerán aquí.' }}
                                    </p>
                                    @if ($buscar !== '')
                                        <a href="{{ route('admin.app-galy.comentarios') }}" class="btn btn-sm btn-outline-primary mt-2">
                                            <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Ver todas
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($comentarios->hasPages())
            <div class="d-flex justify-content-center border-top px-3 py-3" style="border-color: var(--ga-line) !important;">
                {{ $comentarios->links() }}
            </div>
        @endif
    </div>
@endsection
