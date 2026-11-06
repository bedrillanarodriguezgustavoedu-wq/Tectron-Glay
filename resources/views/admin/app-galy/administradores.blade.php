@extends('layouts.admin')

@section('content')
    @php
        $yo = auth()->user();
        $puedoAbrirPanel = $yo->galy_user_id !== null && $yo->galy_es_admin
            && $yo->hasAnyRole(['SUPER ADMIN', 'ADMINISTRADOR']);
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <div class="text-uppercase text-primary fw-semibold small mb-1">App GALY</div>
            <h1 class="mb-1">Administradores de la app</h1>
            <p class="text-muted mb-0">
                Las cuentas con rango de administrador en GALY. Desde aquí creas administradores, les quitas el rango
                y abres el panel de la app con tu propia cuenta.
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.app-galy.clientes') }}" class="btn btn-outline-secondary">
                <i class="bi bi-people" aria-hidden="true"></i> Todos los clientes
            </a>
            @if ($puedoAbrirPanel)
                <a href="{{ route('admin.galy-panel') }}" class="btn btn-primary">
                    <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Abrir el panel de mi cuenta
                </a>
            @endif
        </div>
    </div>

    @if (session('mensaje'))
        <div class="alert alert-success alert-dismissible fade show" role="status">
            {{ session('mensaje') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <strong>No se pudo completar la acción:</strong>
            <ul class="mb-0 mt-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0">Cuentas con acceso administrativo</h2>
                    <span class="badge bg-dark-subtle text-dark">{{ number_format($administradores->count()) }} activas</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Administrador</th>
                                <th class="text-center">Pedidos</th>
                                <th class="text-center">Sesiones GALY</th>
                                <th class="text-center">Cuenta web</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($administradores as $admin)
                                @php
                                    $vinculo = $vinculosWeb->get((int) $admin->id);
                                    $sesiones = $sesionesPorAdmin[(int) $admin->id] ?? 0;
                                    $pedidos = $pedidosPorAdmin->get((int) $admin->id);
                                    $esCuentaActual = (int) $admin->id === (int) $yo->galy_user_id;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="rounded-circle bg-dark-subtle text-dark d-inline-flex align-items-center justify-content-center"
                                                style="width:36px;height:36px;">
                                                <i class="bi bi-shield-check" aria-hidden="true"></i>
                                            </span>
                                            <div>
                                                <strong>{{ $admin->nombre }}</strong>
                                                @if ($esCuentaActual)
                                                    <span class="badge bg-primary-subtle text-primary ms-1">Tu cuenta</span>
                                                @endif
                                                <div class="small text-muted">{{ $admin->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        {{ number_format((int) ($pedidos->cantidad ?? 0)) }}
                                        <div class="small text-muted">S/ {{ number_format((float) ($pedidos->monto ?? 0), 2) }}</div>
                                    </td>
                                    <td class="text-center">
                                        @if ($sesiones > 0)
                                            <span class="badge bg-success-subtle text-success">{{ $sesiones }}</span>
                                        @else
                                            <span class="text-muted small">Cerradas</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($vinculo !== null)
                                            <span class="badge {{ $vinculo->galy_es_admin ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-dark' }}">
                                                {{ $vinculo->galy_es_admin ? 'Vinculada' : 'Sin rango web' }}
                                            </span>
                                        @else
                                            <span class="text-muted small">Solo en la app</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2 flex-wrap">
                                            <a href="{{ route('admin.app-galy.cliente', $admin->id) }}"
                                                class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-eye" aria-hidden="true"></i> Ficha
                                            </a>
                                            <form action="{{ route('admin.app-galy.administradores.alternar', $admin->id) }}"
                                                method="POST"
                                                onsubmit="return confirm('¿Quitarle el rango de administrador a {{ $admin->nombre }}? Perderá el acceso al panel de la app y se cerrarán sus sesiones.');">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="es_admin" value="0">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-person-x" aria-hidden="true"></i> Quitar rango
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">
                                        Ninguna cuenta de la app tiene rango de administrador ahora mismo.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-transparent small text-muted">
                    La app tiene {{ number_format($totalClientes) }} cuentas en total. Quitar el rango no borra la
                    cuenta: el cliente sigue comprando, solo pierde el acceso administrativo.
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent">
                    <h2 class="h5 mb-0">Crear un administrador</h2>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.app-galy.administradores.crear') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" maxlength="100"
                                value="{{ old('nombre') }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Correo</label>
                            <input type="email" class="form-control" id="email" name="email" maxlength="100"
                                value="{{ old('email') }}" required>
                            <div class="form-text">Será el usuario con el que entrará a la app y al panel de GALY.</div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password"
                                minlength="12" maxlength="72" required>
                            <div class="form-text">Mínimo 12 caracteres.</div>
                        </div>
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Repetir contraseña</label>
                            <input type="password" class="form-control" id="password_confirmation"
                                name="password_confirmation" minlength="12" maxlength="72" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-person-add" aria-hidden="true"></i> Crear administrador GALY
                        </button>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body">
                    <h2 class="h6">Cómo se conectan los dos administradores</h2>
                    <ul class="text-muted small mb-0 ps-3">
                        <li class="mb-2">La tienda web y la app usan la misma base: una cuenta con rango
                            <em>administrador</em> puede entrar a ambos paneles.</li>
                        <li class="mb-2">Al pulsar <strong>Abrir el panel de mi cuenta</strong> se firma un enlace de
                            un solo uso (2 minutos) con tu propia cuenta de GALY; no se abre el panel con otras cuentas.</li>
                        <li class="mb-2">Si quitas el rango, se cierran las sesiones abiertas de esa cuenta en la app
                            y en el panel.</li>
                        <li>El rango se sincroniza con la cuenta web vinculada por correo.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
