@extends('layouts.admin')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <div class="text-uppercase text-primary fw-semibold small mb-1">App GALY</div>
            <h1 class="mb-1">Dispositivos push</h1>
            <p class="text-muted mb-0">
                Teléfonos que tienen el token de notificaciones registrado. Al desvincular uno, la app vuelve a
                registrarse sola cuando el cliente la vuelve a abrir.
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.app-galy.notificaciones') }}" class="btn btn-outline-primary">
                <i class="bi bi-bell" aria-hidden="true"></i> Enviar anuncio
            </a>
            <a href="{{ route('admin.app-galy.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left" aria-hidden="true"></i> Resumen
            </a>
        </div>
    </div>

    @if (session('mensaje'))
        <div class="alert alert-success alert-dismissible fade show" role="status">
            {{ session('mensaje') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    @if ($sinColumna)
        <div class="alert alert-warning" role="status">
            Esta base todavía no tiene la columna de tokens de la app, así que la lista sale vacía. No se perdió
            nada: cuando la app GALY registre un equipo, los dispositivos aparecerán aquí solos.
        </div>
    @endif

    @if ($errors->has('dispositivo'))
        <div class="alert alert-danger" role="alert">{{ $errors->first('dispositivo') }}</div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-12 col-sm-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted text-uppercase small mb-1">Con token</p>
                    <h2 class="h4 mb-0">{{ number_format($dispositivos->total()) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted text-uppercase small mb-1">Sin token</p>
                    <h2 class="h4 mb-0">{{ number_format(max(0, $sinToken)) }}</h2>
                    <small class="text-muted">Nunca abrieron la app o desvincularon el equipo.</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted text-uppercase small mb-1">Cuentas de la app</p>
                    <h2 class="h4 mb-0">{{ number_format($totalClientes) }}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Token (recortado)</th>
                            <th class="text-center">Rango</th>
                            <th>Última actividad</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($dispositivos as $dispositivo)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.app-galy.cliente', $dispositivo->id) }}"
                                        class="text-decoration-none">{{ $dispositivo->nombre }}</a>
                                    <div class="small text-muted">{{ $dispositivo->email }}</div>
                                </td>
                                <td><code class="small">{{ $dispositivo->token_corto }}</code></td>
                                <td class="text-center">
                                    @if ((int) $dispositivo->es_admin === 1)
                                        <span class="badge bg-dark-subtle text-dark">Admin</span>
                                    @else
                                        <span class="text-muted small">Cliente</span>
                                    @endif
                                </td>
                                <td class="small">
                                    {{ $dispositivo->updated_at ? \Illuminate\Support\Carbon::parse($dispositivo->updated_at)->diffForHumans() : '—' }}
                                </td>
                                <td class="text-end">
                                    <form action="{{ route('admin.app-galy.dispositivos.revocar', $dispositivo->id) }}"
                                        method="POST"
                                        onsubmit="return confirm('¿Desvincular el dispositivo de {{ $dispositivo->nombre }}? Dejará de recibir anuncios hasta que abra la app de nuevo.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-x-circle" aria-hidden="true"></i> Desvincular
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    Ningún dispositivo tiene token FCM registrado todavía.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($dispositivos->hasPages())
                <div class="mt-3">{{ $dispositivos->links() }}</div>
            @endif
        </div>
    </div>
@endsection
