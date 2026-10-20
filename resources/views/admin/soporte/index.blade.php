@extends('layouts.admin')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="mb-1">Soporte técnico</h1>
            <p class="text-muted mb-0">Bandeja compartida de tickets de la web y GALY. Los cambios de estado también notifican al cliente desde GALY.</p>
        </div>
        <span class="badge bg-primary-subtle text-primary fs-6">{{ $tickets->total() }} tickets</span>
    </div>

    @if (session('mensaje'))
        <div class="alert alert-success alert-dismissible fade show" role="status">
            {{ session('mensaje') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    @if ($errors->has('soporte'))
        <div class="alert alert-danger" role="alert">{{ $errors->first('soporte') }}</div>
    @endif

    @if ($tickets->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-tools fs-1 text-muted" aria-hidden="true"></i>
                <h2 class="h5 mt-3">No hay tickets de soporte</h2>
                <p class="text-muted mb-0">Las solicitudes creadas en GALY aparecerán aquí.</p>
            </div>
        </div>
    @else
        <div class="d-grid gap-3">
            @foreach ($tickets as $ticket)
                <article class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                            <div>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <h2 class="h5 mb-0">Ticket #{{ $ticket->id }}</h2>
                                    <span class="badge text-bg-secondary">{{ $ticket->estado ?: 'Recibido' }}</span>
                                </div>
                                <p class="text-muted small mb-0 mt-1">
                                    {{ $ticket->fecha_ingreso ?: 'Fecha no disponible' }}
                                </p>
                            </div>
                            <div class="text-md-end">
                                <div class="fw-semibold">{{ $ticket->cliente_nombre ?: 'Cliente GALY' }}</div>
                                <div class="text-muted small">Usuario #{{ $ticket->usuario_id }}</div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-lg-7">
                                <h3 class="h6 mb-1">{{ $ticket->equipo_modelo }}</h3>
                                <p class="text-muted mb-3" style="white-space: pre-line">{{ $ticket->descripcion_falla ?: 'Sin descripción de falla.' }}</p>

                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ([
                                        'imagen_ingreso' => 'Ver foto de ingreso',
                                        'imagen_listo' => 'Ver foto de entrega',
                                    ] as $field => $label)
                                        @if ($ticket->$field)
                                            <a class="btn btn-sm btn-outline-secondary"
                                                href="{{ $imagesUrl . '/' . rawurlencode(basename($ticket->$field)) }}"
                                                target="_blank" rel="noopener noreferrer">
                                                <i class="bi bi-image me-1" aria-hidden="true"></i>{{ $label }}
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            </div>

                            <div class="col-lg-5">
                                @can('Editar Soporte Técnico')
                                    <form action="{{ route('admin.soporte.update', $ticket->id) }}" method="POST" class="rounded-3 bg-body-tertiary p-3">
                                        @csrf
                                        @method('PUT')
                                        <div class="row g-2 align-items-end">
                                            <div class="col-sm-7">
                                                <label for="estado-{{ $ticket->id }}" class="form-label">Estado</label>
                                                <select name="estado" id="estado-{{ $ticket->id }}" class="form-select @error('estado') is-invalid @enderror" required>
                                                    @foreach ($statuses as $status)
                                                        <option value="{{ $status }}" @selected(old('estado', $ticket->estado ?: 'Recibido') === $status)>
                                                            {{ $status }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-sm-5">
                                                <label for="costo-{{ $ticket->id }}" class="form-label">Costo (S/)</label>
                                                <input type="number" name="costo" id="costo-{{ $ticket->id }}"
                                                    class="form-control @error('costo') is-invalid @enderror"
                                                    value="{{ old('costo', $ticket->costo_estimado ?? 0) }}"
                                                    min="0" max="99999999.99" step="0.01" required>
                                            </div>
                                            <div class="col-12 d-flex justify-content-end pt-1">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="bi bi-save me-1" aria-hidden="true"></i>Guardar y notificar
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                @else
                                    <dl class="mb-0">
                                        <dt>Estado</dt>
                                        <dd>{{ $ticket->estado ?: 'Recibido' }}</dd>
                                        <dt>Costo estimado</dt>
                                        <dd class="mb-0">S/ {{ number_format((float) $ticket->costo_estimado, 2) }}</dd>
                                    </dl>
                                @endcan
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-4">{{ $tickets->links() }}</div>
    @endif
@endsection
