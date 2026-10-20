@extends('layouts.admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Moderación de Reseñas</h1>
    </div>
    <hr>

    <!-- Filters and Search -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form action="{{ route('admin.reviews.index') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label fw-bold">Buscar</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" id="search" class="form-control" 
                            value="{{ request('search') }}" placeholder="Producto, cliente o comentario...">
                    </div>
                </div>
                <div class="col-md-3">
                    <label for="estrellas" class="form-label fw-bold">Calificación</label>
                    <select name="estrellas" id="estrellas" class="form-select">
                        <option value="">Todas</option>
                        @for ($i = 5; $i >= 1; $i--)
                            <option value="{{ $i }}" {{ request('estrellas') == $i ? 'selected' : '' }}>
                                {{ $i }} {{ $i == 1 ? 'Estrella' : 'Estrellas' }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="estado" class="form-label fw-bold">Estado</label>
                    <select name="estado" id="estado" class="form-select">
                        <option value="">Todos</option>
                        <option value="aprobado" {{ request('estado') === 'aprobado' ? 'selected' : '' }}>Aprobadas</option>
                        <option value="pendiente" {{ request('estado') === 'pendiente' ? 'selected' : '' }}>Pendientes de Aprobación</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100 me-2"><i class="bi bi-funnel"></i> Filtrar</button>
                    <a href="{{ route('admin.reviews.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- Reviews List -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 180px;">Producto</th>
                            <th style="width: 150px;">Cliente</th>
                            <th style="width: 120px;">Pedido</th>
                            <th style="width: 110px;">Calificación</th>
                            <th>Opinión</th>
                            <th style="width: 120px; text-align: center;">Estado</th>
                            <th style="width: 200px; text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($resenas as $resena)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if ($resena->producto->imagenes->first())
                                            <img src="{{ $resena->producto->imagenes->first()->url }}" 
                                                class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                        @endif
                                        <div class="small fw-semibold text-truncate" style="max-width: 120px;" title="{{ $resena->producto->nombre }}">
                                            {{ $resena->producto->nombre }}
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small fw-bold">{{ $resena->usuario->name }}</div>
                                    <div class="text-muted small text-truncate" style="max-width: 130px;" title="{{ $resena->usuario->email }}">
                                        {{ $resena->usuario->email }}
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">#ORD-{{ $resena->orden_id }}</span>
                                    <div class="small text-muted mt-1">{{ $resena->created_at->format('d/m/Y') }}</div>
                                </td>
                                <td>
                                    <div class="text-warning">
                                        @for ($i = 1; $i <= 5; $i++)
                                            @if ($i <= $resena->estrellas)
                                                <i class="bi bi-star-fill"></i>
                                            @else
                                                <i class="bi bi-star"></i>
                                            @endif
                                        @endfor
                                    </div>
                                    <small class="text-muted">({{ $resena->estrellas }} de 5)</small>
                                </td>
                                <td>
                                    @if ($resena->titulo)
                                        <div class="fw-bold text-dark mb-1">{{ $resena->titulo }}</div>
                                    @endif
                                    <p class="mb-2 text-secondary" style="font-size: 0.9rem; line-height: 1.4;">
                                        {{ $resena->comentario }}
                                    </p>

                                    <!-- Attached Photos -->
                                    @if ($resena->imagenes->isNotEmpty())
                                        <div class="d-flex gap-2 mb-2 flex-wrap">
                                            @foreach ($resena->imagenes as $img)
                                                <a href="{{ asset('storage/' . $img->imagen) }}" target="_blank">
                                                    <img src="{{ asset('storage/' . $img->imagen) }}" 
                                                        class="img-thumbnail rounded" style="width: 50px; height: 50px; object-fit: cover;">
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif

                                    <!-- Admin Response -->
                                    @if ($resena->respuesta_admin)
                                        <div class="bg-light p-2 rounded border-start border-primary border-3 mt-2" style="font-size: 0.85rem;">
                                            <div class="fw-semibold text-primary"><i class="bi bi-reply-fill"></i> Respuesta Oficial:</div>
                                            <p class="mb-0 text-muted">{{ $resena->respuesta_admin }}</p>
                                            <span class="text-muted small" style="font-size: 0.75rem;">
                                                Respondido el {{ $resena->fecha_respuesta->format('d/m/Y H:i') }}
                                            </span>
                                        </div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($resena->aprobado)
                                        <span class="badge bg-success"><i class="bi bi-patch-check-fill"></i> Aprobada</span>
                                    @else
                                        <span class="badge bg-warning text-dark"><i class="bi bi-clock-fill"></i> Pendiente</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-1 justify-content-center flex-wrap">
                                        @if (!$resena->aprobado)
                                            <form action="{{ route('admin.reviews.approve', $resena->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-sm fw-semibold" title="Aprobar Reseña">
                                                    <i class="bi bi-check-lg"></i> Aprobar
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('admin.reviews.reject', $resena->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-warning btn-sm text-dark fw-semibold" title="Desaprobar/Ocultar Reseña">
                                                    <i class="bi bi-eye-slash-fill"></i> Ocultar
                                                </button>
                                            </form>
                                        @endif

                                        <button class="btn btn-info btn-sm text-white fw-semibold" data-bs-toggle="collapse" 
                                            data-bs-target="#replyForm{{ $resena->id }}" title="Responder públicamente">
                                            <i class="bi bi-reply-fill"></i> Responder
                                        </button>

                                        <form action="{{ route('admin.reviews.destroy_admin', $resena->id) }}" method="POST" 
                                            onsubmit="return confirm('¿Está seguro de eliminar permanentemente esta reseña?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" title="Eliminar Reseña">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>

                                    <!-- Collapsible Admin Reply Form -->
                                    <div class="collapse mt-2" id="replyForm{{ $resena->id }}">
                                        <form action="{{ route('admin.reviews.reply', $resena->id) }}" method="POST" class="p-2 border rounded bg-light">
                                            @csrf
                                            <div class="mb-2">
                                                <label for="respuesta_admin_{{ $resena->id }}" class="form-label small fw-bold">Escribir Respuesta</label>
                                                <textarea name="respuesta_admin" id="respuesta_admin_{{ $resena->id }}" rows="2" class="form-control form-control-sm" 
                                                    placeholder="Ingrese su respuesta oficial..." required>{{ $resena->respuesta_admin }}</textarea>
                                            </div>
                                            <div class="d-flex justify-content-end gap-1">
                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="collapse" 
                                                    data-bs-target="#replyForm{{ $resena->id }}">Cancelar</button>
                                                <button type="submit" class="btn btn-primary btn-sm">Enviar</button>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bi bi-chat-left-dots fs-2 d-block mb-2"></i>
                                    No se encontraron reseñas que coincidan con los criterios.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($resenas->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $resenas->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
