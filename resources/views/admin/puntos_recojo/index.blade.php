@extends('layouts.admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Listado de Puntos de Recojo</h1>
        @can('Crear Punto de Recojo')
            <a href="{{ route('admin.puntos_recojo.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Crear Nuevo Punto
            </a>
        @endcan
    </div>
    <hr>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Locales donde el cliente puede recoger</h4>
                </div>

                <div class="card-body">
                    <div class="alert alert-info">
                        Cuando el cliente elige <strong>Recojo en tienda</strong> en el carrito, le aparece la lista de
                        los puntos que <strong>no</strong> tienen provincia asignada. Si no hay ninguno activo, el
                        carrito sigue mostrando la dirección general de Admin → Ajustes.
                        <br>
                        Los que sí tienen provincia asignada son <strong>agencias</strong>: se ofrecen bajo
                        <strong>Envío a provincias</strong> y solo a los clientes cuya dirección de destino esté en esa
                        provincia. Así el pedido queda en un local concreto de su ciudad en vez de una dirección suelta.
                    </div>

                    <form action="{{ route('admin.puntos_recojo.index') }}" method="GET" class="row g-2 align-items-end mb-3">
                        <div class="col-12 col-md-3">
                            <label for="filtro_tipo" class="form-label fw-bold small mb-1">Mostrar</label>
                            <select name="tipo" id="filtro_tipo" class="form-select form-select-sm">
                                <option value="" @selected(request('tipo') === null)>Todos los locales</option>
                                <option value="tienda" @selected(request('tipo') === 'tienda')>Solo «Recojo en tienda»</option>
                                <option value="agencia" @selected(request('tipo') === 'agencia')>Solo agencias de provincias</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="filtro_departamento" class="form-label fw-bold small mb-1">Departamento</label>
                            <select name="departamento_id" id="filtro_departamento" class="form-select form-select-sm">
                                <option value="">— Todos —</option>
                                @foreach ($departamentos as $departamento)
                                    <option value="{{ $departamento->id }}" @selected((int) request('departamento_id') === $departamento->id)>
                                        {{ $departamento->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-6 d-flex align-items-center gap-2">
                            <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3">Filtrar</button>
                            @if (request('tipo') || request('departamento_id'))
                                <a href="{{ route('admin.puntos_recojo.index') }}" class="btn btn-sm btn-link">Limpiar</a>
                            @endif
                            <span class="text-muted small ms-auto">
                                {{ $puntos->total() }} {{ Str::plural('local', $puntos->total()) }}
                            </span>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-striped align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 60px; text-align: center;">Nro</th>
                                    <th style="width: 180px;">Nombre</th>
                                    <th>Dirección</th>
                                    <th style="width: 170px;">Horario</th>
                                    <th style="width: 130px;">Teléfono</th>
                                    <th style="width: 190px;">Sirve para</th>
                                    <th style="width: 70px; text-align: center;">Orden</th>
                                    <th style="width: 120px; text-align: center;">Estado</th>
                                    <th style="width: 150px; text-align: center;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $nro = ($puntos->currentPage() - 1) * $puntos->perPage() + 1;
                                @endphp
                                @forelse ($puntos as $punto)
                                    <tr>
                                        <td style="text-align: center;">{{ $nro++ }}</td>
                                        <td class="fw-bold">{{ $punto->nombre }}</td>
                                        <td>{{ $punto->direccion }}</td>
                                        <td>{{ $punto->horario ?: '—' }}</td>
                                        <td>{{ $punto->telefono ?: '—' }}</td>
                                        <td>
                                            @if ($punto->provincia_id)
                                                <span class="badge bg-info text-dark">
                                                    <i class="bi bi-box-seam me-1"></i>Agencia en {{ $punto->provincia?->nombre }}
                                                </span>
                                                <small class="d-block text-muted">{{ $punto->provincia?->departamento?->nombre }}</small>
                                            @else
                                                <span class="badge bg-secondary"><i class="bi bi-shop me-1"></i>Recojo en tienda</span>
                                            @endif
                                        </td>
                                        <td style="text-align: center;">{{ $punto->orden }}</td>
                                        <td style="text-align: center;">
                                            @can('Editar Punto de Recojo')
                                                <form action="{{ route('admin.puntos_recojo.toggle', $punto->id) }}" method="POST">
                                                    @csrf
                                                    @if ($punto->activo)
                                                        <button type="submit" class="btn btn-success btn-sm px-3 rounded-pill">Visible</button>
                                                    @else
                                                        <button type="submit" class="btn btn-secondary btn-sm px-3 rounded-pill">Oculto</button>
                                                    @endif
                                                </form>
                                            @else
                                                <span class="badge bg-secondary">{{ $punto->activo ? 'Visible' : 'Oculto' }}</span>
                                            @endcan
                                        </td>
                                        <td style="text-align: center;">
                                            <div class="btn-group btn-group-sm" role="group">
                                                @can('Editar Punto de Recojo')
                                                    <a href="{{ route('admin.puntos_recojo.edit', $punto->id) }}" class="btn btn-success">
                                                        <i class="bi bi-pencil"></i> Editar
                                                    </a>
                                                @endcan
                                                @can('Eliminar Punto de Recojo')
                                                    @if ($punto->activo)
                                                        <button type="button" class="btn btn-danger" onclick="confirmarEliminar{{ $punto->id }}(event)">
                                                            <i class="bi bi-shop"></i> Quitar
                                                        </button>

                                                        <form action="{{ route('admin.puntos_recojo.destroy', $punto->id) }}" method="POST" id="form-eliminar-{{ $punto->id }}" style="display: none;">
                                                            @csrf
                                                            @method('DELETE')
                                                        </form>

                                                        <script>
                                                            function confirmarEliminar{{ $punto->id }}(event) {
                                                                event.preventDefault();
                                                                Swal.fire({
                                                                    title: '¿Quitar {{ $punto->nombre }} del carrito?',
                                                                    text: "Deja de ofrecerse como punto de recojo. El registro se conserva y los pedidos que ya se retiraron ahí siguen intactos.",
                                                                    icon: 'warning',
                                                                    showCancelButton: true,
                                                                    confirmButtonColor: '#d33',
                                                                    cancelButtonColor: '#3085d6',
                                                                    confirmButtonText: 'Sí, quitar',
                                                                    cancelButtonText: 'Cancelar'
                                                                }).then((result) => {
                                                                    if (result.isConfirmed) {
                                                                        document.getElementById('form-eliminar-{{ $punto->id }}').submit();
                                                                    }
                                                                });
                                                            }
                                                        </script>
                                                    @else
                                                        <span class="text-muted small">Ya está oculto</span>
                                                    @endif
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-4 text-muted">
                                            Todavía no hay puntos de recojo. El carrito muestra la dirección general de Ajustes hasta que crees el primero.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($puntos->hasPages())
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="text-muted">
                                Mostrando {{ $puntos->firstItem() }} a {{ $puntos->lastItem() }} de {{ $puntos->total() }} puntos
                            </div>
                            <div>
                                {{ $puntos->links('pagination::bootstrap-4') }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
