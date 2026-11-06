@extends('layouts.admin')

@section('content')
    <h1>Listado de productos</h1>
    <hr>
    <div class="alert alert-info" role="status">
        <strong>Catálogo compartido GALY + Tectron:</strong>
        esta pantalla administra la tabla compartida <code>ecommerce.productos</code> que utilizan la tienda y la app Android.
        @if ($hasProductMap)
            Hay {{ $productosGaly }} productos en GALY, incluidos {{ $productosTectron }} vinculados desde Tectron.
        @else
            Hay {{ $productosGaly }} productos en GALY. El mapa de importación de Tectron aún no está disponible.
        @endif
        Los cambios de productos se reflejan en la app y en la web; los pedidos y demás datos de la tienda mantienen su conexión actual.
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
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Productos registrados
                        <a href="{{ url('/admin/productos/create') }}" style="float: right" class="btn btn-primary"><i
                                class="bi bi-plus"></i> Crear nuevo</a>
                        <a href="{{ route('admin.productos.importar') }}" style="float: right; margin-right: 8px"
                            class="btn btn-success"><i class="bi bi-upload"></i> Importar lista</a>
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <form action="{{ url('/admin/productos') }}" method="GET" class="mt-3">
                                @if ($enPapelera)
                                    <input type="hidden" name="eliminados" value="1">
                                @endif
                                <div class="input-group">
                                    <input type="text" name="buscar" class="form-control" placeholder="Buscar..."
                                        value="{{ $_REQUEST['buscar'] ?? '' }}">
                                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i>
                                        Buscar</button>
                                    @if (isset($_REQUEST['buscar']))
                                        <a href="{{ url('/admin/productos') }}" class="btn btn-success">
                                            <i class="bi bi-x-circle"></i> Limpiar</a>
                                    @endif
                                </div>
                            </form>
                            <br>
                        </div>
                        <div class="col-md-6 text-md-end mt-3 mt-md-0">
                            @if ($enPapelera)
                                <a href="{{ url('/admin/productos') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-arrow-left"></i> Volver al catálogo
                                </a>
                            @else
                                <a href="{{ url('/admin/productos?eliminados=1') }}" class="btn btn-outline-dark btn-sm">
                                    <i class="bi bi-trash3"></i> Papelera ({{ $productosEnPapelera }})
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2 p-2 border rounded bg-light">
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

                        <span class="text-muted small ms-auto">
                            @if ($enPapelera)
                                Desde aquí los devuelves al catálogo; no se borran del historial de pedidos.
                            @else
                                Eliminar manda la fila a la papelera: se puede devolver, no se pierde.
                            @endif
                        </span>
                    </div>

                    <table class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr>
                                <th style="width: 38px">
                                    <input type="checkbox" id="marcarPagina" class="form-check-input" form="masivoForm">
                                </th>
                                <th>Nro</th>
                                <th>Categoría</th>
                                <th>Catálogo</th>
                                <th>Nombre</th>
                                <th>Código</th>
                                <th>Descripción corta</th>
                                <th>Precio de compra</th>
                                <th>Precio de venta</th>
                                <th>Stock</th>
                                <th>Acciones</th>
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
                                    <td>{{ $producto->categoria?->nombre ?? 'Sin categoría' }}</td>
                                    <td>
                                        @if ($producto->es_producto_tectron ?? false)
                                            <span class="badge bg-primary">Tectron + GALY</span>
                                        @else
                                            <span class="badge bg-secondary">GALY</span>
                                        @endif
                                    </td>
                                    <td>{{ $producto->nombre }}</td>
                                    <td>{{ $producto->codigo }}</td>
                                    <td>{{ $producto->descripcion_corta }}</td>
                                    <td>{{ $ajuste->divisa." ".$producto->precio_compra }}</td>
                                    <td>{{ $ajuste->divisa." ".$producto->precio_venta }}</td>
                                    <td style="text-align: center">{{ $producto->stock }}</td>

                                    <td class="text-center">
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
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ url('/admin/producto/' . $producto->id) }}"
                                                class="btn btn-info btn-sm"><i class="bi bi-eye"></i> Ver</a>
                                            <a href="{{ url('/admin/producto/' . $producto->id.'/imagenes') }}"
                                                class="btn btn-warning btn-sm"><i class="bi bi-image"></i> Imágenes</a>
                                            <a href="{{ url('/admin/producto/' . $producto->id . '/edit') }}"
                                                class="btn btn-success btn-sm"><i class="bi bi-pencil"></i> Editar</a>
                                            <button type="submit" class="btn btn-danger btn-sm"
                                                onclick="preguntar{{ $producto->id }}(event)">
                                                <i class="bi bi-trash"></i> Eliminar
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
                        <div class="d-flex justify-content-between align-items-center mt-4 px-3">
                            <div class="text-muted">
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
        </div>
    </div>
    <hr>
    
@endsection
