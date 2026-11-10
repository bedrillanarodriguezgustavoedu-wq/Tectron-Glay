@extends('layouts.web')

@section('title', 'Marcas · ' . ($ajuste->nombre ?? 'GALY'))

@section('content')
    @php
        $totalMarcas = $marcas->count();
        $totalProductos = (int) $marcas->sum('productos_count');
    @endphp

    <div class="g-page-head">
        <div class="container">
            <nav class="g-crumbs" aria-label="Ruta de navegación">
                <a href="{{ route('web.index') }}">Inicio</a>
                <i class="bi bi-chevron-right" aria-hidden="true"></i>
                <span aria-current="page">Marcas</span>
            </nav>
            <div class="g-page-head-inner g-brands-head">
                <div>
                    <span class="g-eyebrow">Fabricantes</span>
                    <h1 class="page-title">Nuestras Marcas Aliadas</h1>
                    <p>Los fabricantes que dan vida a nuestro catálogo: tecnología original, garantía y respaldo en cada compra.</p>
                </div>
                <div class="g-brands-stats">
                    <div>
                        <strong>{{ number_format($totalMarcas) }}</strong>
                        <span>marcas</span>
                    </div>
                    <div>
                        <strong>{{ number_format($totalProductos) }}</strong>
                        <span>productos</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="g-brands section py-5">
        <div class="container" data-aos="fade-up" data-aos-delay="50">
            <div class="g-brands-toolbar">
                <div class="g-brands-search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <label class="visually-hidden" for="buscar-marca">Buscar marca</label>
                    <input type="search" id="buscar-marca" class="form-control"
                        placeholder="Buscar marca por nombre…" autocomplete="off">
                </div>
                <span class="g-brands-chip" id="brandsCounter">
                    <i class="bi bi-award" aria-hidden="true"></i>
                    {{ number_format($totalMarcas) }} marcas disponibles
                </span>
            </div>

            <div class="row g-3 g-md-4" id="brandsGrid">
                @forelse ($marcas as $marca)
                    <div class="col-6 col-md-4 col-xl-3 g-brand-col"
                        data-nombre="{{ \Illuminate\Support\Str::lower($marca->nombre) }}">
                        <article class="g-brand-card">
                            <div class="g-brand-card-logo">
                                <x-marca-logo :marca="$marca" width="100%" height="96px" />
                            </div>
                            <div class="g-brand-card-body">
                                <h2 class="g-brand-card-name">{{ $marca->nombre }}</h2>
                                <span class="g-brand-card-count">
                                    <i class="bi bi-box-seam" aria-hidden="true"></i>
                                    {{ number_format($marca->productos_count) }}
                                    {{ $marca->productos_count === 1 ? 'producto' : 'productos' }}
                                </span>
                                <a href="{{ route('web.productos', ['marca' => $marca->slug]) }}" class="g-brand-card-link">
                                    Ver catálogo <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                </a>
                            </div>
                        </article>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="g-brands-empty">
                            <i class="bi bi-award" aria-hidden="true"></i>
                            <strong>Todavía no hay marcas registradas</strong>
                            <p>Estamos preparando el catálogo. Vuelve pronto para conocer nuestros fabricantes.</p>
                        </div>
                    </div>
                @endforelse
            </div>

            <div class="g-brands-empty d-none" id="brandsEmpty">
                <i class="bi bi-search" aria-hidden="true"></i>
                <strong>No encontramos esa marca</strong>
                <p>Prueba con otro nombre o <button type="button" id="brandsReset">muestra todas las marcas</button>.</p>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const input = document.getElementById('buscar-marca');
            const cols = Array.from(document.querySelectorAll('.g-brand-col'));
            const empty = document.getElementById('brandsEmpty');
            const counter = document.getElementById('brandsCounter');
            const reset = document.getElementById('brandsReset');
            if (!input || cols.length === 0) return;

            const total = cols.length;

            const aplicar = function () {
                const q = input.value.trim().toLowerCase();
                let visibles = 0;

                cols.forEach(function (col) {
                    const coincide = col.dataset.nombre.includes(q);
                    col.classList.toggle('d-none', !coincide);
                    if (coincide) visibles++;
                });

                empty.classList.toggle('d-none', visibles !== 0);
                counter.innerHTML = '<i class="bi bi-award" aria-hidden="true"></i> ' +
                    (q ? visibles + ' de ' + total + ' marcas' : total + ' marcas disponibles');
            };

            input.addEventListener('input', aplicar);

            if (reset) {
                reset.addEventListener('click', function () {
                    input.value = '';
                    aplicar();
                    input.focus();
                });
            }
        });
    </script>
@endsection
