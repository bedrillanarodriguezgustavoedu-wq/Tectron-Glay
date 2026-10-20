@php
    $catActiva = isset($categoria) ? $categoria->slug : '';
    $categoriasSeleccionadas = (array)request('categoria');
    $mostrarFiltrosTecnicos = false;
    if ($catActiva === 'laptops' || $catActiva === 'computadoras' || in_array('laptops', $categoriasSeleccionadas) || in_array('computadoras', $categoriasSeleccionadas)) {
        $mostrarFiltrosTecnicos = true;
    }
@endphp

<form id="filtrosForm" method="GET" action="{{ url()->current() }}">
    <!-- Hidden fields for search and order -->
    @if(request('buscar'))
        <input type="hidden" name="buscar" value="{{ request('buscar') }}">
    @endif
    @if(request('producto'))
        <input type="hidden" name="producto" value="{{ request('producto') }}">
    @endif
    <input type="hidden" name="orden" id="ordenInput" value="{{ request('orden') }}">

    <!-- Price Range -->
    <div class="filter-group mb-4">
        <h5 class="fw-bold mb-3 text-dark">Rango de Precio</h5>
        <div class="row g-2 align-items-center">
            <div class="col">
                <input type="number" name="precio_min" id="precio_min" class="form-control form-control-sm border-2 rounded-3" placeholder="Mín" value="{{ request('precio_min') }}" min="0">
            </div>
            <div class="col-auto text-muted">-</div>
            <div class="col">
                <input type="number" name="precio_max" id="precio_max" class="form-control form-control-sm border-2 rounded-3" placeholder="Máx" value="{{ request('precio_max') }}" min="0">
            </div>
        </div>
        <button type="button" class="btn btn-primary btn-sm w-100 mt-3 rounded-3 fw-bold py-2 shadow-sm" id="btnAplicarPrecio">
            <i class="bi bi-funnel-fill me-1"></i> Aplicar Rango
        </button>
    </div>

    <!-- Category -->
    @if (!isset($categoria))
    <div class="filter-group mb-4 border-top pt-3">
        <h5 class="fw-bold mb-3 text-dark">Categorías</h5>
        <div class="filter-list" style="max-height: 200px; overflow-y: auto;">
            @foreach($categorias_sidebar as $cat)
                <div class="form-check mb-2">
                    <input class="form-check-input filter-checkbox" type="checkbox" name="categoria[]" value="{{ $cat->slug }}" id="cat-{{ $cat->id }}" {{ in_array($cat->slug, $categoriasSeleccionadas) ? 'checked' : '' }}>
                    <label class="form-check-label d-flex justify-content-between align-items-center w-100" for="cat-{{ $cat->id }}">
                        <span class="text-dark">{{ $cat->nombre }}</span>
                        <span class="badge bg-light text-muted rounded-pill border">{{ $cat->productos_count }}</span>
                    </label>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Brand -->
    <div class="filter-group mb-4 border-top pt-3">
        <h5 class="fw-bold mb-3 text-dark">Marcas</h5>
        <div class="filter-list" style="max-height: 200px; overflow-y: auto;">
            @foreach($marcas_sidebar as $m)
                <div class="form-check mb-2">
                    <input class="form-check-input filter-checkbox" type="checkbox" name="marca[]" value="{{ $m->slug }}" id="marca-{{ $m->id }}" {{ in_array($m->slug, (array)request('marca')) ? 'checked' : '' }}>
                    <label class="form-check-label d-flex justify-content-between align-items-center w-100" for="marca-{{ $m->id }}">
                        <span class="d-flex align-items-center text-dark">
                            <x-marca-logo :marca="$m" width="32px" height="26px" class="me-2" />
                            {{ $m->nombre }}
                        </span>
                        <span class="badge bg-light text-muted rounded-pill border">{{ $m->productos_count }}</span>
                    </label>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Availability -->
    <div class="filter-group mb-4 border-top pt-3">
        <h5 class="fw-bold mb-3 text-dark">Disponibilidad</h5>
        <div class="form-check mb-2">
            <input class="form-check-input filter-checkbox" type="checkbox" name="disponibilidad[]" value="in_stock" id="disp-in-stock" {{ in_array('in_stock', (array)request('disponibilidad')) ? 'checked' : '' }}>
            <label class="form-check-label text-dark" for="disp-in-stock">En stock</label>
        </div>
        <div class="form-check mb-2">
            <input class="form-check-input filter-checkbox" type="checkbox" name="disponibilidad[]" value="out_of_stock" id="disp-out-stock" {{ in_array('out_of_stock', (array)request('disponibilidad')) ? 'checked' : '' }}>
            <label class="form-check-label text-dark" for="disp-out-stock">Agotados</label>
        </div>
    </div>

    <!-- Rating -->
    <div class="filter-group mb-4 border-top pt-3">
        <h5 class="fw-bold mb-3 text-dark">Valoración</h5>
        @for ($stars = 5; $stars >= 3; $stars--)
            <div class="form-check mb-2">
                <input class="form-check-input filter-radio" type="radio" name="valoracion" value="{{ $stars }}" id="rating-{{ $stars }}" {{ request('valoracion') == $stars ? 'checked' : '' }}>
                <label class="form-check-label text-warning" for="rating-{{ $stars }}">
                    @for ($i = 1; $i <= 5; $i++)
                        <i class="bi bi-star{{ $i <= $stars ? '-fill' : '' }}"></i>
                    @endfor
                    <span class="text-muted text-sm ms-1">o más</span>
                </label>
            </div>
        @endfor
        @if(request('valoracion'))
            <div class="form-check mb-2">
                <input class="form-check-input filter-radio" type="radio" name="valoracion" value="" id="rating-all" {{ !request('valoracion') ? 'checked' : '' }}>
                <label class="form-check-label text-muted" for="rating-all">Todas las valoraciones</label>
            </div>
        @endif
    </div>

    <!-- Product Status -->
    <div class="filter-group mb-4 border-top pt-3">
        <h5 class="fw-bold mb-3 text-dark">Estado</h5>
        <div class="form-check mb-2">
            <input class="form-check-input filter-checkbox" type="checkbox" name="estado[]" value="nuevo" id="est-nuevo" {{ in_array('nuevo', (array)request('estado')) ? 'checked' : '' }}>
            <label class="form-check-label text-dark" for="est-nuevo">Nuevos</label>
        </div>
        <div class="form-check mb-2">
            <input class="form-check-input filter-checkbox" type="checkbox" name="estado[]" value="en_oferta" id="est-oferta" {{ in_array('en_oferta', (array)request('estado')) ? 'checked' : '' }}>
            <label class="form-check-label text-dark" for="est-oferta">En oferta</label>
        </div>
        <div class="form-check mb-2">
            <input class="form-check-input filter-checkbox" type="checkbox" name="estado[]" value="mas_vendido" id="est-vendido" {{ in_array('mas_vendido', (array)request('estado')) ? 'checked' : '' }}>
            <label class="form-check-label text-dark" for="est-vendido">Más vendidos</label>
        </div>
        <div class="form-check mb-2">
            <input class="form-check-input filter-checkbox" type="checkbox" name="estado[]" value="destacado" id="est-destacado" {{ in_array('destacado', (array)request('estado')) ? 'checked' : '' }}>
            <label class="form-check-label text-dark" for="est-destacado">Destacados</label>
        </div>
    </div>

    <!-- Technical Filters Section (conditional on category) -->
    @if ($mostrarFiltrosTecnicos)
        <div id="tech-filters-section" class="border-top pt-3 mt-3">
            <h4 class="fw-bold mb-3 text-primary"><i class="bi bi-cpu-fill me-1"></i>Filtros Técnicos</h4>
            
            <!-- Procesador -->
            <div class="filter-group mb-4">
                <h5 class="fw-bold mb-2 text-dark">Procesador</h5>
                @foreach(['Intel Core i3' => 'Intel i3', 'Intel Core i5' => 'Intel i5', 'Intel Core i7' => 'Intel i7', 'Intel Ultra 7' => 'Intel Ultra', 'AMD Ryzen 3' => 'Ryzen 3', 'AMD Ryzen 5' => 'Ryzen 5', 'AMD Ryzen 7' => 'Ryzen 7', 'AMD Ryzen 9' => 'Ryzen 9'] as $key => $label)
                    <div class="form-check mb-2">
                        <input class="form-check-input filter-checkbox" type="checkbox" name="procesador[]" value="{{ $key }}" id="proc-{{ Str::slug($key) }}" {{ in_array($key, (array)request('procesador')) ? 'checked' : '' }}>
                        <label class="form-check-label text-dark" for="proc-{{ Str::slug($key) }}">{{ $label }}</label>
                    </div>
                @endforeach
            </div>

            <!-- RAM -->
            <div class="filter-group mb-4 border-top pt-2">
                <h5 class="fw-bold mb-2 text-dark">Memoria RAM</h5>
                @foreach(['8 GB', '16 GB', '32 GB', '64 GB'] as $ram)
                    <div class="form-check mb-2">
                        <input class="form-check-input filter-checkbox" type="checkbox" name="ram[]" value="{{ $ram }}" id="ram-{{ Str::slug($ram) }}" {{ in_array($ram, (array)request('ram')) ? 'checked' : '' }}>
                        <label class="form-check-label text-dark" for="ram-{{ Str::slug($ram) }}">{{ $ram }}</label>
                    </div>
                @endforeach
            </div>

            <!-- Almacenamiento -->
            <div class="filter-group mb-4 border-top pt-2">
                <h5 class="fw-bold mb-2 text-dark">Almacenamiento</h5>
                @foreach(['256 GB SSD' => '256 GB SSD', '512 GB SSD' => '512 GB SSD', '1 TB SSD' => '1 TB SSD', '2 TB SSD' => '2 TB SSD'] as $key => $ssd)
                    <div class="form-check mb-2">
                        <input class="form-check-input filter-checkbox" type="checkbox" name="ssd[]" value="{{ $key }}" id="ssd-{{ Str::slug($key) }}" {{ in_array($key, (array)request('ssd')) ? 'checked' : '' }}>
                        <label class="form-check-label text-dark" for="ssd-{{ Str::slug($key) }}">{{ $ssd }}</label>
                    </div>
                @endforeach
            </div>

            <!-- Tarjeta gráfica -->
            <div class="filter-group mb-4 border-top pt-2">
                <h5 class="fw-bold mb-2 text-dark">Tarjeta Gráfica</h5>
                @foreach(['Integrada' => 'Integrada', 'RTX 3050' => 'RTX 3050', 'RTX 3060' => 'RTX 3060', 'RTX 4060' => 'RTX 4060', 'RTX 4070' => 'RTX 4070', 'RTX 4080' => 'RTX 4080', 'RTX 4090' => 'RTX 4090'] as $key => $label)
                    <div class="form-check mb-2">
                        <input class="form-check-input filter-checkbox" type="checkbox" name="grafica[]" value="{{ $key }}" id="gpu-{{ Str::slug($key) }}" {{ in_array($key, (array)request('grafica')) ? 'checked' : '' }}>
                        <label class="form-check-label text-dark" for="gpu-{{ Str::slug($key) }}">{{ $label }}</label>
                    </div>
                @endforeach
            </div>

            @if($catActiva === 'laptops' || in_array('laptops', $categoriasSeleccionadas))
                <!-- Pantalla (solo laptops) -->
                <div class="filter-group mb-4 border-top pt-2">
                    <h5 class="fw-bold mb-2 text-dark">Pantalla</h5>
                    @foreach(['14"', '15.6"', '16"', '17"'] as $pantalla)
                        <div class="form-check mb-2">
                            <input class="form-check-input filter-checkbox" type="checkbox" name="pantalla[]" value="{{ $pantalla }}" id="screen-{{ Str::slug($pantalla) }}" {{ in_array($pantalla, (array)request('pantalla')) ? 'checked' : '' }}>
                            <label class="form-check-label text-dark" for="screen-{{ Str::slug($pantalla) }}">{{ $pantalla }}</label>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Sistema Operativo -->
            <div class="filter-group mb-4 border-top pt-2">
                <h5 class="fw-bold mb-2 text-dark">Sistema Operativo</h5>
                @foreach(['Windows 11 Home' => 'Windows', 'Linux Ubuntu' => 'Linux', 'Sin Sistema Operativo' => 'Sin SO'] as $key => $label)
                    <div class="form-check mb-2">
                        <input class="form-check-input filter-checkbox" type="checkbox" name="so[]" value="{{ $key }}" id="so-{{ Str::slug($key) }}" {{ in_array($key, (array)request('so')) ? 'checked' : '' }}>
                        <label class="form-check-label text-dark" for="so-{{ Str::slug($key) }}">{{ $label }}</label>
                    </div>
                @endforeach
            </div>

            <!-- Color -->
            <div class="filter-group mb-4 border-top pt-2">
                <h5 class="fw-bold mb-2 text-dark">Color</h5>
                @foreach(['Negro', 'Blanco', 'Plata', 'Azul'] as $color)
                    <div class="form-check mb-2">
                        <input class="form-check-input filter-checkbox" type="checkbox" name="color[]" value="{{ $color }}" id="color-{{ Str::slug($color) }}" {{ in_array($color, (array)request('color')) ? 'checked' : '' }}>
                        <label class="form-check-label text-dark" for="color-{{ Str::slug($color) }}">{{ $color }}</label>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</form>
