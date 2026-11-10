@extends('layouts.admin')

@section('title', ($enPapelera ? 'Papelera de productos' : ($enAgotados ? 'Productos agotados' : ($enBajoStock ? 'Productos con bajo stock' : 'Productos'))) . ' · ' . ($ajuste->nombre ?? 'TECTRON'))

@section('content')
    <div class="ga-page-head">
        <div class="ga-page-head-main">
            <div class="ga-eyebrow">Catálogo compartido</div>
            <h1 class="ga-page-title">{{ $enPapelera ? 'Papelera de productos' : ($enAgotados ? 'Productos agotados' : ($enBajoStock ? 'Productos con bajo stock' : 'Productos')) }}</h1>
            <p class="ga-page-sub">
                @if ($enPapelera)
                    Productos apartados del catálogo. Puedes devolverlos cuando quieras; no se borra su historial de pedidos.
                @else
                    @if ($enAgotados)
                        Productos sin stock disponible. Repón el stock para que vuelvan a aparecer en la tienda y la app.
                    @else
                        Administra el catálogo que comparten la tienda web y la app Android. Los cambios se publican al instante en ambos canales.
                    @endif
                @endif
            </p>
        </div>
        <div class="ga-head-actions">
            @if ($enPapelera || $enAgotados || $enBajoStock)
                <a href="{{ url('/admin/productos') }}" class="btn-ga-ghost">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i> Volver al catálogo
                </a>
            @else
                <a href="{{ url('/admin/productos?agotados=1') }}" class="btn-ga-ghost">
                    <i class="bi bi-exclamation-triangle" aria-hidden="true"></i> Agotados ({{ number_format($productosAgotados) }})
                </a>
                <a href="{{ url('/admin/productos?bajo_stock=1') }}" class="btn-ga-ghost">
                    <i class="bi bi-graph-down" aria-hidden="true"></i> Bajo stock ({{ number_format($productosBajoStock) }})
                </a>
                <a href="{{ url('/admin/productos?eliminados=1') }}" class="btn-ga-ghost">
                    <i class="bi bi-trash3" aria-hidden="true"></i> Papelera ({{ number_format($productosEnPapelera) }})
                </a>
            @endif
            <span class="ga-count-pill">
                <i class="bi bi-box-seam" aria-hidden="true"></i>
                <strong>{{ number_format($productos->total()) }}</strong> {{ $enPapelera ? 'en papelera' : 'productos' }}
            </span>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="ga-kpi">
                <div class="ga-kpi-top">
                    <div>
                        <p class="ga-kpi-label">En GALY</p>
                        <p class="ga-kpi-value">{{ number_format($productosGaly) }}</p>
                    </div>
                    <span class="ga-kpi-icon"><i class="bi bi-database-fill" aria-hidden="true"></i></span>
                </div>
                <span class="ga-kpi-foot"><i class="bi bi-shop" aria-hidden="true"></i> Web y app</span>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="ga-kpi">
                <div class="ga-kpi-top">
                    <div>
                        <p class="ga-kpi-label">Importados</p>
                        <p class="ga-kpi-value">{{ number_format($productosTectron) }}</p>
                    </div>
                    <span class="ga-kpi-icon is-info"><i class="bi bi-cloud-arrow-down-fill" aria-hidden="true"></i></span>
                </div>
                <span class="ga-kpi-foot"><i class="bi bi-check2-circle" aria-hidden="true"></i> Desde TECTRON</span>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="ga-kpi">
                <div class="ga-kpi-top">
                    <div>
                        <p class="ga-kpi-label">En papelera</p>
                        <p class="ga-kpi-value">{{ number_format($productosEnPapelera) }}</p>
                    </div>
                    <span class="ga-kpi-icon is-danger"><i class="bi bi-trash3-fill" aria-hidden="true"></i></span>
                </div>
                <span class="ga-kpi-foot"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Se pueden recuperar</span>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="ga-kpi">
                <div class="ga-kpi-top">
                    <div>
                        <p class="ga-kpi-label">Mostrando</p>
                        <p class="ga-kpi-value">{{ number_format($productos->count()) }}</p>
                    </div>
                    <span class="ga-kpi-icon is-success"><i class="bi bi-list-ul" aria-hidden="true"></i></span>
                </div>
                <span class="ga-kpi-foot"><i class="bi bi-layers" aria-hidden="true"></i> En esta página</span>
            </div>
        </div>
    </div>

    @if ($productosAgotados > 0 && ! $enAgotados && ! $enPapelera)
        <div class="alert alert-warning d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
            <span>
                Hay <strong>{{ number_format($productosAgotados) }}</strong> producto(s) agotado(s) que no se muestran en la tienda.
                <a href="{{ url('/admin/productos?agotados=1') }}" class="alert-link">Ver agotados</a> para reponer stock.
            </span>
        </div>
    @endif
    @if ($productosBajoStock > 0 && ! $enBajoStock && ! $enAgotados && ! $enPapelera)
        <div class="alert alert-info d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="bi bi-graph-down" aria-hidden="true"></i>
            <span>
                Hay <strong>{{ number_format($productosBajoStock) }}</strong> producto(s) con bajo stock (≤ 10 unidades).
                <a href="{{ url('/admin/productos?bajo_stock=1') }}" class="alert-link">Ver bajo stock</a>.
            </span>
        </div>
    @endif
    <div class="ga-note mb-3">
        <i class="bi bi-info-circle" aria-hidden="true"></i>
        <span>
            <strong>Catálogo GALY:</strong> esta pantalla administra la tabla compartida
            <code>galy.productos</code> que utilizan la tienda y la app Android.
            @if ($hasProductMap)
                Hay {{ number_format($productosGaly) }} productos en GALY, incluidos {{ number_format($productosTectron) }} importados.
            @else
                Hay {{ number_format($productosGaly) }} productos en GALY. El historial de importación aún no está disponible.
            @endif
            Los pedidos y demás datos de la tienda mantienen su conexión actual.
        </span>
    </div>
    @if (session('importacion_motivos'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>Importación:</strong> estas filas no se guardaron.
            <ul class="mb-0 mt-2">
                @foreach (session('importacion_motivos') as $motivo)
                    <li>{{ $motivo }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif
    <div class="ga-surface ga-table-card">
        <div class="ga-card-head">
            <div>
                <h2>{{ $enPapelera ? 'Productos en papelera' : 'Productos registrados' }}</h2>
                <p>{{ $enPapelera ? 'Devuélvelos al catálogo cuando los necesites' : 'Precios en ' . ($ajuste->divisa ?? 'S/') . ' · cambios visibles en la web y la app' }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if (!$enPapelera)
                    <a href="{{ route('admin.productos.importar') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-upload" aria-hidden="true"></i> Importar lista
                    </a>
                    <a href="{{ url('/admin/productos/create') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i> Crear producto
                    </a>
                @endif
            </div>
        </div>
        <div class="card-body">
            <form action="{{ url('/admin/productos') }}" method="GET" class="ga-toolbar mb-3" role="search">
                @if ($enPapelera)
                    <input type="hidden" name="eliminados" value="1">
                @endif
                <div class="ga-search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <label class="visually-hidden" for="buscar-productos">Buscar productos</label>
                    <input type="search" id="buscar-productos" name="buscar" class="form-control"
                        placeholder="Buscar por nombre, código o categoría" value="{{ $_REQUEST['buscar'] ?? '' }}">
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search" aria-hidden="true"></i> Buscar
                </button>
                @if (isset($_REQUEST['buscar']))
                    <a href="{{ url('/admin/productos' . ($enPapelera ? '?eliminados=1' : '')) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg" aria-hidden="true"></i> Limpiar
                    </a>
                @endif
            </form>

            <div class="d-flex flex-wrap align-items-center gap-2 mb-3 p-3 border rounded" style="background: var(--ga-bg); border-color: var(--ga-line) !important;">
                <span class="badge bg-secondary" id="contadorSeleccion">0 seleccionados</span>

                @if ($enPapelera)
                    <button type="button" class="btn btn-sm btn-success"
                        onclick="enviarMasivo(event, 'restaurar')">
                        <i class="bi bi-arrow-counterclockwise"></i> Devolver al catálogo
                    </button>
                @else
                    <button type="button" class="btn btn-sm btn-danger"
                        onclick="enviarMasivo(event, 'eliminar')">
                        <i class="bi bi-trash"></i> Eliminar seleccionados
                    </button>
                    @if ($productos->total() > $productos->count())
                        <button type="button" class="btn btn-sm btn-outline-danger"
                            onclick="enviarMasivo(event, 'eliminar', true)">
                            <i class="bi bi-trash3"></i> Eliminar los {{ $productos->total() }} del listado
                        </button>
                    @endif
                @endif

                <span class="ga-muted small ms-auto">
                    <i class="bi bi-info-circle" aria-hidden="true"></i>
                    @if ($enPapelera)
                        Desde aquí los devuelves al catálogo; no se borran del historial de pedidos.
                    @else
                        Eliminar manda la fila a la papelera: se puede devolver, no se pierde.
                    @endif
                </span>
            </div>

            <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 38px">
                            <input type="checkbox" id="marcarPagina" class="form-check-input" form="masivoForm" aria-label="Marcar todos los productos de la página">
                        </th>
                        <th>Nro</th>
                        <th>Categoría</th>
                        <th>Catálogo</th>
                        <th>Nombre</th>
                        <th>Código</th>
                        <th>Descripción corta</th>
                        <th>Precio de compra</th>
                        <th>Precio de venta</th>
                        <th class="text-center">Stock</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                        <tbody>
                            @php
                                $nro = ($productos->currentPage() - 1) * $productos->perPage() + 1;
                            @endphp
                            @foreach ($productos as $producto)
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" name="ids[]" value="{{ $producto->id }}"
                                            class="form-check-input chkFila" form="masivoForm">
                                    </td>
                                    <td style="text-align: center">{{ $nro++ }}</td>
                                    <td><span class="badge bg-secondary">{{ $producto->categoria?->nombre ?? 'Sin categoría' }}</span></td>
                                    <td>
                                        @if ($producto->es_producto_tectron ?? false)
                                            <span class="badge bg-primary">GALY (importado)</span>
                                        @else
                                            <span class="badge bg-info">GALY</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong class="d-block">{{ $producto->nombre }}</strong>
                                        <small class="ga-muted">ID {{ $producto->id }}</small>
                                    </td>
                                    <td><code>{{ $producto->codigo }}</code></td>
                                    <td>
                                        <span class="d-block text-truncate" style="max-width: 220px;" title="{{ $producto->descripcion_corta }}">
                                            {{ $producto->descripcion_corta ?: '—' }}
                                        </span>
                                    </td>
                                    <td class="ga-nowrap">{{ $ajuste->divisa." ".$producto->precio_compra }}</td>
                                    <td class="ga-nowrap"><strong>{{ $ajuste->divisa." ".$producto->precio_venta }}</strong></td>
                                    <td style="text-align: center">
                                        @if ((int) $producto->stock <= 0)
                                            <span class="badge bg-dark">Agotado</span>
                                        @elseif ((int) $producto->stock <= 10)
                                            <span class="badge bg-danger">{{ $producto->stock }}</span>
                                        @else
                                            <span class="badge bg-success">{{ $producto->stock }}</span>
                                        @endif
                                    </td>

                                    <td class="text-end">
                                        @if ($enPapelera)
                                            <form action="{{ url('/admin/productos/masivo') }}" method="POST"
                                                class="d-inline">
                                                @csrf
                                                <input type="hidden" name="accion" value="restaurar">
                                                <input type="hidden" name="alcance" value="seleccion">
                                                <input type="hidden" name="ids[]" value="{{ $producto->id }}">
                                                <button type="submit" class="btn btn-success btn-sm">
                                                    <i class="bi bi-arrow-counterclockwise"></i> Devolver
                                                </button>
                                            </form>
                                        @else
                                        <div class="btn-group btn-group-sm" role="group" aria-label="Acciones del producto">
                                            <a href="{{ url('/admin/producto/' . $producto->id) }}"
                                                class="btn btn-outline-secondary" title="Ver producto">
                                                <i class="bi bi-eye" aria-hidden="true"></i>
                                                <span class="visually-hidden">Ver</span>
                                            </a>
                                            <a href="{{ url('/admin/producto/' . $producto->id.'/imagenes') }}"
                                                class="btn btn-outline-secondary" title="Gestionar imágenes">
                                                <i class="bi bi-image" aria-hidden="true"></i>
                                                <span class="visually-hidden">Imágenes</span>
                                            </a>
                                            <a href="{{ url('/admin/producto/' . $producto->id . '/edit') }}"
                                                class="btn btn-outline-primary" title="Editar producto">
                                                <i class="bi bi-pencil" aria-hidden="true"></i>
                                                <span class="visually-hidden">Editar</span>
                                            </a>
                                            <form action="{{ url('/admin/producto/' . $producto->id . '/reponer-stock') }}"
                                                method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="cantidad" value="10">
                                                <button type="submit" class="btn btn-outline-success" title="Reponer 10 unidades">
                                                    <i class="bi bi-plus-circle" aria-hidden="true"></i>
                                                    <span class="visually-hidden">Reponer</span>
                                                </button>
                                            </form>
                                            <button type="submit" class="btn btn-outline-danger" title="Eliminar producto"
                                                onclick="preguntar{{ $producto->id }}(event)">
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                                <span class="visually-hidden">Eliminar</span>
                                            </button>
                                        </div>

                                        <form action="{{ url('/admin/producto/' . $producto->id) }}" method="post"
                                            id="miFormulario{{ $producto->id }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                        </form>

                                        <script>
                                            function preguntar{{ $producto->id }}(event) {
                                                event.preventDefault();
                                                Swal.fire({
                                                    title: '¿Desea eliminar este registro?',
                                                    text: 'Pasa a la papelera y se puede devolver al catálogo.',
                                                    icon: 'question',
                                                    showDenyButton: true,
                                                    confirmButtonText: 'Eliminar',
                                                    confirmButtonColor: '#a5161d',
                                                    denyButtonColor: '#270a0a',
                                                    denyButtonText: 'Cancelar',
                                                }).then((result) => {
                                                    if (result.isConfirmed) {
                                                        // JavaScript puro para enviar el formulario
                                                        document.getElementById('miFormulario{{ $producto->id }}').submit();
                                                    }
                                                });
                                            }
                                        </script>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
            </div>

                    <form action="{{ url('/admin/productos/masivo') }}" method="POST" id="masivoForm"
                        class="d-none">
                        @csrf
                        <input type="hidden" name="accion" id="masivoAccion" value="eliminar">
                        <input type="hidden" name="alcance" id="masivoAlcance" value="seleccion">
                        <input type="hidden" name="confirmar" id="masivoConfirmar" value="">
                        <input type="hidden" name="buscar" value="{{ $_REQUEST['buscar'] ?? '' }}">
                        @if ($enPapelera)
                            <input type="hidden" name="eliminados" value="1">
                        @endif
                    </form>

                    @if ($errors->has('confirmar') || $errors->has('ids'))
                        <div class="alert alert-warning" role="alert">
                            {{ $errors->first('confirmar') ?: $errors->first('ids') }}
                        </div>
                    @endif

                    <script>
                        (function() {
                            const enPapelera = @json($enPapelera);
                            const marcarPagina = document.getElementById('marcarPagina');
                            const contador = document.getElementById('contadorSeleccion');
                            const filas = () => Array.from(document.querySelectorAll('.chkFila'));

                            function actualizar() {
                                const n = filas().filter((caja) => caja.checked).length;
                                contador.textContent = n === 1 ? '1 producto seleccionado' : n + ' productos seleccionados';
                                marcarPagina.checked = n > 0 && n === filas().length;
                            }

                            marcarPagina.addEventListener('change', () => {
                                filas().forEach((caja) => caja.checked = marcarPagina.checked);
                                actualizar();
                            });

                            filas().forEach((caja) => caja.addEventListener('change', actualizar));
                            actualizar();

                            window.enviarMasivo = function(event, accion, todoElListado) {
                                event.preventDefault();

                                const n = filas().filter((caja) => caja.checked).length;
                                const total = {{ $productos->total() }};
                                const objetivo = todoElListado ? total : n;

                                if (!todoElListado && n === 0) {
                                    Swal.fire({
                                        icon: 'warning',
                                        title: 'Marca al menos un producto',
                                        text: 'Usa la casilla de cada fila o la del encabezado para marcar toda la página.',
                                    });

                                    return;
                                }

                                const form = document.getElementById('masivoForm');
                                document.getElementById('masivoAccion').value = accion;
                                document.getElementById('masivoAlcance').value = todoElListado ? 'filtro' : 'seleccion';
                                document.getElementById('masivoConfirmar').value = '';

                                if (accion === 'restaurar') {
                                    Swal.fire({
                                        icon: 'question',
                                        title: '¿Devolver ' + objetivo + ' al catálogo?',
                                        showDenyButton: true,
                                        confirmButtonText: 'Devolver',
                                        denyButtonText: 'Cancelar',
                                    }).then((resultado) => {
                                        if (resultado.isConfirmed) {
                                            form.submit();
                                        }
                                    });

                                    return;
                                }

                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Eliminar ' + objetivo + ' producto' + (objetivo === 1 ? '' : 's'),
                                    html: 'Pasan a la <strong>papelera</strong> y se pueden devolver al catálogo.<br>'
                                        + 'Escribe <strong>ELIMINAR</strong> para confirmar.',
                                    input: 'text',
                                    inputPlaceholder: 'ELIMINAR',
                                    showDenyButton: true,
                                    confirmButtonText: 'Eliminar',
                                    confirmButtonColor: '#a5161d',
                                    denyButtonText: 'Cancelar',
                                    preConfirm: (valor) => {
                                        if ((valor || '').toUpperCase() !== 'ELIMINAR') {
                                            Swal.showValidationMessage('Tienes que escribir ELIMINAR');
                                        }

                                        return (valor || '').toUpperCase();
                                    }
                                }).then((resultado) => {
                                    if (resultado.isConfirmed && resultado.value === 'ELIMINAR') {
                                        document.getElementById('masivoConfirmar').value = 'ELIMINAR';
                                        form.submit();
                                    }
                                });
                            };
                        })();
                    </script>

                    @if ($productos->hasPages())
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-4 pt-3 border-top" style="border-color: var(--ga-line) !important;">
                            <div class="ga-muted small">
                                <i class="bi bi-list-ol" aria-hidden="true"></i>
                                Mostrando {{ $productos->firstItem() }} a {{ $productos->lastItem() }} de
                                {{ $productos->total() }} registros
                            </div>
                            <div>
                                {{ $productos->links('pagination::bootstrap-4') }}
                            </div>
                        </div>
                    @endif
        </div>
    </div>
@endsection
