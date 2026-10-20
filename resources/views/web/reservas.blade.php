@extends('layouts.web')

@section('content')
    <section class="section py-5">
        <div class="container">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <h1 class="mb-1">Mis productos apartados</h1>
                    <p class="text-muted mb-0">Cada reserva bloquea inventario durante 24 horas. Puedes cancelarla o comprarla desde el carrito.</p>
                </div>
                <a href="{{ route('web.productos') }}" class="btn btn-outline-primary">Seguir comprando</a>
            </div>

            @if (session('mensaje'))
                <div class="alert alert-{{ session('icono') === 'success' ? 'success' : 'warning' }}" role="status">{{ session('mensaje') }}</div>
            @endif

            @php($activeReservations = $reservations->where('estado', 'activa'))
            @forelse ($reservations as $reservation)
                <article class="card border-0 shadow-sm mb-3">
                    <div class="card-body d-flex flex-wrap align-items-center gap-3">
                        <img src="{{ $imagesUrl . '/' . rawurlencode(basename($reservation->imagen_url ?? '')) }}"
                            alt="{{ $reservation->producto_nombre }}" width="84" height="84" class="rounded object-fit-contain"
                            onerror="this.style.display='none'">
                        <div class="flex-grow-1">
                            <h2 class="h5 mb-1">{{ $reservation->producto_nombre }}</h2>
                            <div class="text-muted small">{{ $reservation->cantidad }} unidad(es) ·
                                @if ($reservation->estado === 'activa')
                                    vence {{ \Illuminate\Support\Carbon::parse($reservation->expira_at)->format('d/m/Y H:i') }}
                                @else
                                    {{ ucfirst($reservation->estado) }}
                                @endif
                            </div>
                        </div>
                        @if ($reservation->estado === 'activa')
                            <form action="{{ route('web.carrito.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="producto_id" value="{{ $reservation->producto_id }}">
                                <input type="hidden" name="cantidad" value="{{ $reservation->cantidad }}">
                                <button class="btn btn-primary">Agregar al carrito</button>
                            </form>
                            <form action="{{ route('web.reservas.cancel', $reservation->id) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-outline-danger">Cancelar</button>
                            </form>
                        @endif
                    </div>
                </article>
            @empty
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-bookmark fs-1 text-muted" aria-hidden="true"></i>
                        <h2 class="h5 mt-3">Aún no tienes reservas</h2>
                        <a href="{{ route('web.productos') }}" class="btn btn-primary mt-2">Explorar productos</a>
                    </div>
                </div>
            @endforelse
        </div>
    </section>
@endsection
