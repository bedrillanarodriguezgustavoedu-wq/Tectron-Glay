@extends('layouts.admin')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <div class="text-uppercase text-primary fw-semibold small mb-1">App GALY</div>
            <h1 class="mb-1">Notificaciones push</h1>
            <p class="text-muted mb-0">
                Envía un aviso a todos los dispositivos que tienen la app GALY instalada.
            </p>
        </div>
        <span class="badge bg-info-subtle text-info fs-6">
            <i class="bi bi-phone" aria-hidden="true"></i>
            {{ number_format($totalDispositivos) }} dispositivos
        </span>
    </div>

    @if (session('mensaje'))
        <div class="alert alert-success alert-dismissible fade show" role="status">
            {{ session('mensaje') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    @if ($errors->has('notificacion'))
        <div class="alert alert-danger" role="alert">{{ $errors->first('notificacion') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent">
                    <h2 class="h5 mb-0">Nuevo anuncio</h2>
                </div>
                <div class="card-body">
                    @if ($totalDispositivos === 0)
                        <div class="alert alert-warning mb-3" role="alert">
                            Todavía no hay dispositivos con token FCM. La notificación se enviará cuando la app se
                            registre, pero por ahora no llegará a nadie.
                        </div>
                    @endif

                    <form action="{{ route('admin.app-galy.notificaciones.enviar') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" maxlength="120"
                                value="{{ old('titulo') }}" placeholder="Ej. Ofertas de la semana" required>
                        </div>
                        <div class="mb-3">
                            <label for="mensaje" class="form-label">Mensaje</label>
                            <textarea class="form-control" id="mensaje" name="mensaje" rows="4" maxlength="500"
                                placeholder="Escribe el anuncio que verán los clientes en la app." required>{{ old('mensaje') }}</textarea>
                            <div class="form-text">Máximo 500 caracteres. Se envía a todos los dispositivos activos.</div>
                        </div>
                        <button type="submit" class="btn btn-primary"
                            onclick="return confirm('¿Enviar esta notificación a todos los dispositivos de la app GALY?');">
                            <i class="bi bi-send" aria-hidden="true"></i> Enviar notificación
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5">Buenas prácticas</h2>
                    <ul class="text-muted small mb-0 ps-3">
                        <li class="mb-2">Usa títulos cortos y directos: se muestran en la barra de notificaciones.</li>
                        <li class="mb-2">Evita enviar varios anuncios seguidos para no saturar a los clientes.</li>
                        <li class="mb-2">Incluye la acción esperada: “revisa las ofertas”, “tu pedido está listo”.</li>
                        <li>Las notificaciones llegan solo a dispositivos con sesión o token FCM registrado.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
