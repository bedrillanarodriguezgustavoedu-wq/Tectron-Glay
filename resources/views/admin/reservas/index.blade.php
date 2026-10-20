@extends('layouts.admin')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="mb-1">Reservas de productos</h1>
            <p class="text-muted mb-0">Apartados compartidos entre GALY y la web. Las reservas vencen a las 24 horas y el inventario se libera.</p>
        </div>
        <span class="badge bg-primary-subtle text-primary fs-6">{{ $reservations->total() }} reservas</span>
    </div>

    @if (session('mensaje'))
        <div class="alert alert-success" role="status">{{ session('mensaje') }}</div>
    @endif

    @if ($reservations->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-bookmark-check fs-1 text-muted" aria-hidden="true"></i>
                <h2 class="h5 mt-3">No hay reservas registradas</h2>
            </div>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Reserva</th>
                        <th>Cliente</th>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Estado</th>
                        <th>Vencimiento</th>
                        <th>Pedido</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reservations as $reservation)
                        <tr>
                            <td>#{{ $reservation->id }}</td>
                            <td>
                                <strong>{{ $reservation->cliente_nombre ?: 'Usuario GALY' }}</strong>
                                <div class="small text-muted">{{ $reservation->cliente_email }}</div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if ($reservation->imagen_url)
                                        <img src="{{ $imagesUrl . '/' . rawurlencode(basename($reservation->imagen_url)) }}"
                                            alt="" width="42" height="42" class="rounded object-fit-contain">
                                    @endif
                                    <span>{{ $reservation->producto_nombre }}</span>
                                </div>
                            </td>
                            <td>{{ $reservation->cantidad }}</td>
                            <td><span class="badge text-bg-{{ $reservation->estado === 'activa' ? 'success' : ($reservation->estado === 'convertida' ? 'primary' : 'secondary') }}">{{ ucfirst($reservation->estado) }}</span></td>
                            <td>{{ \Illuminate\Support\Carbon::parse($reservation->expira_at)->format('d/m/Y H:i') }}</td>
                            <td>{{ $reservation->pedido_id ? '#'.$reservation->pedido_id : '—' }}</td>
                            <td>
                                @if ($reservation->estado === 'activa')
                                    @can('Cancelar Reservas')
                                        <form action="{{ route('admin.reservas.cancel', $reservation->id) }}" method="POST"
                                            onsubmit="return confirm('¿Cancelar esta reserva y liberar el inventario?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Liberar</button>
                                        </form>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $reservations->links() }}
    @endif
@endsection
