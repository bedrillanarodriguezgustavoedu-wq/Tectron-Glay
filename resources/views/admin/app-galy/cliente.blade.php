@extends('layouts.admin')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <div class="text-uppercase text-primary fw-semibold small mb-1">App GALY</div>
            <h1 class="mb-1">Cliente #{{ $cliente->id }}</h1>
            <p class="text-muted mb-0">Datos compartidos con la app Android y la tienda web.</p>
        </div>
        <a href="{{ route('admin.app-galy.clientes') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left" aria-hidden="true"></i> Volver a clientes
        </a>
    </div>

    @if (session('mensaje'))
        <div class="alert alert-success alert-dismissible fade show" role="status">
            {{ session('mensaje') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <strong>Revisa los datos:</strong>
            <ul class="mb-0 mt-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent">
                    <h2 class="h5 mb-0">Datos del cliente</h2>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.app-galy.clientes.update', $cliente->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="nombre" class="form-label">Nombre</label>
                                <input type="text" class="form-control" id="nombre" name="nombre"
                                    value="{{ old('nombre', $cliente->nombre) }}" maxlength="100" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="email" class="form-label">Correo</label>
                                <input type="email" class="form-control" id="email" name="email"
                                    value="{{ old('email', $cliente->email) }}" maxlength="100" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="telefono" name="telefono"
                                    value="{{ old('telefono', $cliente->telefono) }}" maxlength="20">
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="puntos" class="form-label">Puntos</label>
                                <input type="number" class="form-control" id="puntos" name="puntos"
                                    value="{{ old('puntos', (int) ($cliente->puntos ?? 0)) }}" min="0" max="1000000">
                            </div>
                            <div class="col-12">
                                <label for="direccion" class="form-label">Dirección</label>
                                <textarea class="form-control" id="direccion" name="direccion" rows="2"
                                    maxlength="1000">{{ old('direccion', $cliente->direccion) }}</textarea>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="estado" name="estado"
                                        value="1" @checked(old('estado', (int) ($cliente->estado ?? 1) === 1))>
                                    <label class="form-check-label" for="estado">Cuenta activa</label>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="es_admin" name="es_admin"
                                        value="1" @checked(old('es_admin', (int) $cliente->es_admin === 1))>
                                    <label class="form-check-label" for="es_admin">Administrador de la app</label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('admin.app-galy.clientes') }}" class="btn btn-outline-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check2-circle" aria-hidden="true"></i> Guardar cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <p class="text-muted text-uppercase small mb-1">Total gastado</p>
                    <h2 class="h4 mb-3">S/ {{ number_format($totalGastado, 2) }}</h2>
                    <div class="d-flex justify-content-between small">
                        <span class="text-muted">Registro en la app</span>
                        <strong>{{ $cliente->fecha_registro ? \Illuminate\Support\Carbon::parse($cliente->fecha_registro)->format('d/m/Y') : '—' }}</strong>
                    </div>
                    <hr>
                    <p class="text-muted text-uppercase small mb-1">Cuenta web vinculada</p>
                    @if ($cuentaWeb !== null)
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div>{{ $cuentaWeb->name }}</div>
                                <div class="small text-muted">{{ $cuentaWeb->email }}</div>
                            </div>
                            <span class="badge bg-success-subtle text-success">Vinculada</span>
                        </div>
                    @else
                        <p class="mb-0 small text-muted">Esta cuenta solo existe en la app GALY.</p>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h2 class="h6 mb-3">Acceso a la app</h2>
                    <div class="d-flex justify-content-between small mb-2">
                        <span class="text-muted">Rango en GALY</span>
                        <strong>{{ (int) $cliente->es_admin === 1 ? 'Administrador' : 'Cliente' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between small mb-2">
                        <span class="text-muted">Cuenta</span>
                        <strong>{{ (int) ($cliente->estado ?? 1) === 1 ? 'Activa' : 'Inactiva' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between small mb-3">
                        <span class="text-muted">Dispositivo push</span>
                        @if (! empty($cliente->fcm_token))
                            <strong class="text-success">Registrado</strong>
                        @else
                            <strong class="text-muted">Sin token</strong>
                        @endif
                    </div>

                    <form action="{{ route('admin.app-galy.clientes.password', $cliente->id) }}" method="POST"
                        onsubmit="return confirm('¿Cambiar la contraseña de {{ $cliente->nombre }}? Se cerrarán sus sesiones en la app y en el panel de GALY.');">
                        @csrf
                        @method('PUT')
                        <div class="row g-2">
                            <div class="col-12">
                                <label for="password" class="form-label small text-muted mb-1">Nueva contraseña</label>
                                <input type="password" class="form-control form-control-sm" id="password" name="password"
                                    minlength="12" maxlength="72" placeholder="Mínimo 12 caracteres" required>
                            </div>
                            <div class="col-12">
                                <label for="password_confirmation" class="form-label small text-muted mb-1">
                                    Repetir contraseña
                                </label>
                                <input type="password" class="form-control form-control-sm" id="password_confirmation"
                                    name="password_confirmation" minlength="12" maxlength="72" required>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-sm btn-outline-secondary w-100">
                                    <i class="bi bi-key" aria-hidden="true"></i> Cambiar contraseña de la app
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent">
                    <h2 class="h5 mb-0">Últimos pedidos</h2>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Pedido</th>
                                <th>Estado</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pedidos as $pedido)
                                <tr>
                                    <td>#{{ $pedido->id }}</td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-secondary">
                                            {{ $pedido->estado ?: $pedido->estado_orden }}
                                        </span>
                                    </td>
                                    <td class="text-end">S/ {{ number_format((float) $pedido->total, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">Sin pedidos registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
