@extends('layouts.admin')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <div class="text-uppercase text-primary fw-semibold small mb-1">App GALY</div>
            <h1 class="mb-1">Comentarios de la app</h1>
            <p class="text-muted mb-0">Reseñas que dejaron los clientes desde la app Android.</p>
        </div>
        <span class="badge bg-primary-subtle text-primary fs-6">{{ number_format($comentarios->total()) }} comentarios</span>
    </div>

    @if (session('mensaje'))
        <div class="alert alert-success alert-dismissible fade show" role="status">
            {{ session('mensaje') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    @if ($errors->has('comentario'))
        <div class="alert alert-danger" role="alert">{{ $errors->first('comentario') }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ route('admin.app-galy.comentarios') }}" method="GET" class="row g-2 align-items-center mb-3">
                <div class="col-12 col-md-6 col-lg-5">
                    <div class="input-group">
                        <input type="text" name="buscar" class="form-control"
                            placeholder="Buscar por comentario, cliente o producto" value="{{ $buscar }}">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search" aria-hidden="true"></i> Buscar
                        </button>
                    </div>
                </div>
                @if ($buscar !== '')
                    <div class="col-12 col-md-3">
                        <a href="{{ route('admin.app-galy.comentarios') }}" class="btn btn-outline-secondary">Limpiar</a>
                    </div>
                @endif
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cliente</th>
                            <th class="text-center">Estrellas</th>
                            <th>Comentario</th>
                            <th class="text-center">Fecha</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($comentarios as $comentario)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if (! empty($comentario->producto_imagen))
                                            <img src="{{ rtrim(config('galy.api_url'), '/') . '/img/' . rawurlencode(basename($comentario->producto_imagen)) }}"
                                                alt="" width="36" height="36" class="rounded object-fit-cover"
                                                onerror="this.style.display='none'">
                                        @endif
                                        <span class="small">{{ $comentario->producto_nombre ?: '—' }}</span>
                                    </div>
                                </td>
                                <td class="small">
                                    {{ $comentario->usuario_nombre ?: 'Cliente sin nombre' }}
                                    @if (! empty($comentario->usuario_email))
                                        <div class="text-muted">{{ $comentario->usuario_email }}</div>
                                    @endif
                                </td>
                                <td class="text-center text-warning">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <i class="bi {{ $i <= (int) $comentario->estrellas ? 'bi-star-fill' : 'bi-star' }}"
                                            aria-hidden="true"></i>
                                    @endfor
                                </td>
                                <td class="small">
                                    {{ \Illuminate\Support\Str::limit((string) $comentario->comentario, 160) }}
                                    <details class="mt-1">
                                        <summary class="text-primary small">Editar</summary>
                                        <form action="{{ route('admin.app-galy.comentarios.actualizar', $comentario->id) }}"
                                            method="POST" class="mt-2">
                                            @csrf
                                            @method('PUT')
                                            <label class="visually-hidden" for="comentario-{{ $comentario->id }}">
                                                Comentario
                                            </label>
                                            <textarea id="comentario-{{ $comentario->id }}" name="comentario" rows="3"
                                                maxlength="1000" class="form-control form-control-sm" required>{{ $comentario->comentario }}</textarea>
                                            <button type="submit" class="btn btn-sm btn-primary mt-2">
                                                <i class="bi bi-check2" aria-hidden="true"></i> Guardar
                                            </button>
                                        </form>
                                    </details>
                                </td>
                                <td class="small text-center">
                                    {{ $comentario->fecha ? \Illuminate\Support\Carbon::parse($comentario->fecha)->format('d/m/Y') : '—' }}
                                </td>
                                <td class="text-end">
                                    <form action="{{ route('admin.app-galy.comentarios.eliminar', $comentario->id) }}"
                                        method="POST" class="d-inline"
                                        onsubmit="return confirm('¿Eliminar este comentario de la app?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash3" aria-hidden="true"></i> Eliminar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    La app todavía no tiene comentarios registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($comentarios->hasPages())
                <div class="mt-3">{{ $comentarios->links() }}</div>
            @endif
        </div>
    </div>
@endsection
