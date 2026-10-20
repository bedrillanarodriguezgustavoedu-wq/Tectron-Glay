@extends('layouts.admin')

@section('content')
    <h1>Crear Nueva Oferta</h1>
    <hr>

    <form action="{{ route('admin.ofertas.store') }}" method="POST">
        @csrf

        <div class="row">
            <div class="col-md-6">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-light">
                        <h4 class="mb-0 text-dark">Información de la Promoción</h4>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label for="nombre" class="form-label fw-bold">Nombre de la Oferta (*)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-tag-fill"></i></span>
                                <input type="text" name="nombre" id="nombre" class="form-control"
                                    value="{{ old('nombre') }}" placeholder="ej: Cyber Monday Descuentos, Ofertas Navideñas" required>
                            </div>
                            @error('nombre')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="tipo_descuento" class="form-label fw-bold">Tipo de Descuento (*)</label>
                                    <select name="tipo_descuento" id="tipo_descuento" class="form-select" required>
                                        <option value="porcentaje" {{ old('tipo_descuento') === 'porcentaje' ? 'selected' : '' }}>Porcentaje (%)</option>
                                        <option value="monto" {{ old('tipo_descuento') === 'monto' ? 'selected' : '' }}>Monto Fijo (Monto)</option>
                                    </select>
                                    @error('tipo_descuento')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="valor_descuento" class="form-label fw-bold">Valor del Descuento (*)</label>
                                    <div class="input-group">
                                        <span class="input-group-text" id="discount-symbol"><i class="bi bi-percent"></i></span>
                                        <input type="number" step="0.01" name="valor_descuento" id="valor_descuento" class="form-control"
                                            value="{{ old('valor_descuento') }}" placeholder="ej: 15" min="0.01" required>
                                    </div>
                                    @error('valor_descuento')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label fw-bold d-block">Estado Inicial</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1" checked>
                                <label class="form-check-label fw-bold" for="activo">Oferta Activa</label>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="fecha_inicio" class="form-label fw-bold">Fecha y Hora de Inicio (*)</label>
                                    <input type="datetime-local" name="fecha_inicio" id="fecha_inicio" class="form-control"
                                        value="{{ old('fecha_inicio', now()->format('Y-m-d\TH:i')) }}" required>
                                    @error('fecha_inicio')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="fecha_fin" class="form-label fw-bold">Fecha y Hora de Finalización (*)</label>
                                    <input type="datetime-local" name="fecha_fin" id="fecha_fin" class="form-control"
                                        value="{{ old('fecha_fin', now()->addWeek()->format('Y-m-d\TH:i')) }}" required>
                                    @error('fecha_fin')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <a href="{{ route('admin.ofertas.index') }}" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Registrar Oferta</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-light">
                        <h4 class="mb-0 text-dark">Asociar Productos</h4>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Selecciona los productos que aplicarán para esta oferta:</label>
                            <input type="text" id="buscar_producto" class="form-control mb-2" placeholder="Buscar producto en la lista...">
                        </div>

                        <div class="border rounded p-3 bg-light" style="max-height: 380px; overflow-y: auto;">
                            @forelse ($productos as $producto)
                                <div class="form-check producto-item mb-2 p-2 rounded bg-white border-bottom shadow-sm">
                                    <input class="form-check-input ms-1 me-2" type="checkbox" name="productos[]" value="{{ $producto->id }}" id="prod-{{ $producto->id }}">
                                    <label class="form-check-label text-dark fw-semibold" for="prod-{{ $producto->id }}">
                                        {{ $producto->nombre }}
                                        <span class="text-muted d-block" style="font-size: 12px;">Precio regular: ${{ number_format($producto->precio_venta, 2) }}</span>
                                    </label>
                                </div>
                            @empty
                                <div class="text-center py-4 text-muted">
                                    No hay productos disponibles para asociar.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const buscarInput = document.getElementById('buscar_producto');
            if (buscarInput) {
                buscarInput.addEventListener('input', function(e) {
                    const term = e.target.value.toLowerCase();
                    document.querySelectorAll('.producto-item').forEach(function(item) {
                        const text = item.textContent.toLowerCase();
                        if (text.includes(term)) {
                            item.style.setProperty('display', 'block', 'important');
                        } else {
                            item.style.setProperty('display', 'none', 'important');
                        }
                    });
                });
            }

            const tipoDescuento = document.getElementById('tipo_descuento');
            const discountSymbol = document.getElementById('discount-symbol');
            const divisa = "{{ $ajuste->divisa ?? '$' }}";

            function updateSymbol() {
                if (tipoDescuento.value === 'porcentaje') {
                    discountSymbol.innerHTML = '<i class="bi bi-percent"></i>';
                } else {
                    discountSymbol.innerHTML = `<span class="fw-bold">${divisa}</span>`;
                }
            }

            if (tipoDescuento) {
                tipoDescuento.addEventListener('change', updateSymbol);
                updateSymbol();
            }
        });
    </script>
@endsection
