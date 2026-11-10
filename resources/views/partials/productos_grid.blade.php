@php
    $chips = [];

    if (request('precio_min') || request('precio_max')) {
        $min = request('precio_min') ?? '0';
        $max = request('precio_max') ?? 'MÁX';
        $chips[] = [
            'key' => 'precio_range',
            'label' => "Precio: S/ $min - S/ $max",
            'keys_to_clear' => ['precio_min', 'precio_max']
        ];
    }

    if (request('categoria') && !isset($categoria)) {
        foreach ((array)request('categoria') as $val) {
            $catName = \App\Models\Categoria::where('slug', $val)->value('nombre') ?? $val;
            $chips[] = [
                'key' => 'categoria',
                'value' => $val,
                'label' => "Categoría: $catName"
            ];
        }
    }

    if (request('marca')) {
        foreach ((array)request('marca') as $val) {
            $marcaName = \App\Models\Marca::where('slug', $val)->value('nombre') ?? $val;
            $chips[] = [
                'key' => 'marca',
                'value' => $val,
                'label' => "Marca: $marcaName"
            ];
        }
    }

    if (request('disponibilidad')) {
        foreach ((array)request('disponibilidad') as $val) {
            $label = $val === 'in_stock' ? 'En stock' : 'Agotados';
            $chips[] = [
                'key' => 'disponibilidad',
                'value' => $val,
                'label' => "Disponibilidad: $label"
            ];
        }
    }

    if (request('valoracion')) {
        $chips[] = [
            'key' => 'valoracion',
            'value' => request('valoracion'),
            'label' => "Valoración: " . request('valoracion') . "★ o más"
        ];
    }

    if (request('estado')) {
        foreach ((array)request('estado') as $val) {
            $label = ucfirst(str_replace('_', ' ', $val));
            $chips[] = [
                'key' => 'estado',
                'value' => $val,
                'label' => "Estado: $label"
            ];
        }
    }

    $techKeys = ['procesador', 'ram', 'ssd', 'grafica', 'pantalla', 'so', 'color'];
    foreach ($techKeys as $tk) {
        if (request($tk)) {
            foreach ((array)request($tk) as $val) {
                $label = ucfirst($tk) . ': ' . $val;
                $chips[] = [
                    'key' => $tk,
                    'value' => $val,
                    'label' => $label
                ];
            }
        }
    }
@endphp

<!-- Results Header -->
<div class="g-results-bar">
    <div class="g-results-count">
        <strong>{{ number_format($productos->total()) }}</strong> {{ $productos->total() === 1 ? 'producto encontrado' : 'productos encontrados' }}
    </div>

    <div class="d-flex align-items-center gap-2">
        <label for="ordenSelect" class="text-nowrap mb-0 d-none d-sm-block" style="font-size: .8rem; font-weight: 700; color: var(--g-muted);">Ordenar por</label>
        <select id="ordenSelect" class="form-select form-select-sm">
            <option value="" {{ !request('orden') ? 'selected' : '' }}>Recomendados</option>
            <option value="recientes" {{ request('orden') === 'recientes' ? 'selected' : '' }}>Más recientes</option>
            <option value="mas_vendidos" {{ request('orden') === 'mas_vendidos' ? 'selected' : '' }}>Más vendidos</option>
            <option value="mejor_calificados" {{ request('orden') === 'mejor_calificados' ? 'selected' : '' }}>Mejor calificados</option>
            <option value="precio_menor" {{ request('orden') === 'precio_menor' ? 'selected' : '' }}>Precio: menor a mayor</option>
            <option value="precio_mayor" {{ request('orden') === 'precio_mayor' ? 'selected' : '' }}>Precio: mayor a menor</option>
            <option value="nombre_az" {{ request('orden') === 'nombre_az' ? 'selected' : '' }}>Nombre A-Z</option>
            <option value="nombre_za" {{ request('orden') === 'nombre_za' ? 'selected' : '' }}>Nombre Z-A</option>
        </select>
    </div>
</div>

<!-- Active Filter Chips -->
@if(count($chips) > 0)
    <div class="d-flex flex-wrap align-items-center gap-2 mb-4">
        <span class="text-muted" style="font-size: .76rem; font-weight: 700;">Filtros activos:</span>
        @foreach($chips as $chip)
            <span class="g-chip">
                <span>{{ $chip['label'] }}</span>
                <button type="button" class="btn-close btn-remove-chip" aria-label="Quitar filtro"
                    data-key="{{ $chip['key'] }}"
                    data-value="{{ $chip['value'] ?? '' }}"
                    @if(isset($chip['keys_to_clear'])) data-keys-to-clear='@json($chip['keys_to_clear'])' @endif>
                </button>
            </span>
        @endforeach
        <button type="button" class="g-chip g-chip--clear" id="btnClearAllFilters">
            Limpiar todos <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    </div>
@endif

<!-- Products Grid -->
<div class="row g-3 g-md-4">
    @if ($productos->isEmpty())
        <div class="col-12">
            <div class="empty-state">
                <i class="bi bi-search" aria-hidden="true"></i>
                <h4 class="fw-bold mb-1">Sin resultados</h4>
                <p class="text-muted mb-3">Prueba quitando algunos filtros para encontrar más productos.</p>
                <button type="button" class="btn btn-primary" id="btnEmptyClearFilters">Ver todo el catálogo</button>
            </div>
        </div>
    @else
        @foreach ($productos as $producto)
            <div class="col-6 col-md-6 col-xl-4">
                @include('partials.producto_card', ['producto' => $producto])
            </div>
        @endforeach
    @endif
</div>

<!-- Pagination Links -->
@if ($productos->hasPages())
    <div class="row mt-5">
        <div class="col-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 bg-white p-3 rounded-4 shadow-sm border">
                <div class="text-muted" style="font-size: .82rem;">
                    Mostrando {{ $productos->firstItem() }} a {{ $productos->lastItem() }} de {{ $productos->total() }} productos
                </div>
                <div class="ajax-pagination">
                    {{ $productos->links('partials.paginator') }}
                </div>
            </div>
        </div>
    </div>
@endif
