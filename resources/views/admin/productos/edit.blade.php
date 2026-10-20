@extends('layouts.admin')

@section('content')
    <h1>Modificar datos del producto: {{ $producto->nombre }}</h1>
    <hr>

    <div class="row">
        <div class="col-md-12">
            <div class="card">

                <div class="card-header">
                    <h4>Llena los campos del formulario</h4>
                </div>

                <div class="card-body">

                    <form action="{{ url('/admin/producto/' . $producto->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">

                            {{-- CATEGORÍA --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Categoría (*)</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-tags"></i></span>
                                        <select name="categoria_id" class="form-control" required>
                                            <option value="">Seleccione una categoría...</option>
                                            @foreach ($categorias as $categoria)
                                                <option value="{{ $categoria->id }}"
                                                    {{ old('categoria_id', $producto->categoria_id) == $categoria->id ? 'selected' : '' }}>
                                                    {{ $categoria->nombre }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('categoria_id')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            {{-- MARCA --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Marca</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-award"></i></span>
                                        <select name="marca_id" class="form-control">
                                            <option value="">Seleccione una marca...</option>
                                            @foreach ($marcas as $marca)
                                                <option value="{{ $marca->id }}"
                                                    {{ old('marca_id', $producto->marca_id) == $marca->id ? 'selected' : '' }}>
                                                    {{ $marca->nombre }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('marca_id')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            {{-- NOMBRE --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Nombre (*)</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-box"></i></span>
                                        <input type="text" name="nombre" class="form-control"
                                            value="{{ old('nombre', $producto->nombre) }}" required>
                                    </div>
                                    @error('nombre')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            {{-- CÓDIGO --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Código (*)</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                                        <input type="text" name="codigo" class="form-control"
                                            value="{{ old('codigo', $producto->codigo) }}" required>
                                    </div>
                                    @error('codigo')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        <div class="row mt-3">

                            {{-- DESCRIPCIÓN CORTA --}}
                            <div class="col-md-4">

                                <label>Descripción corta</label>
                                <input type="text" name="descripcion_corta" class="form-control"
                                    value="{{ old('descripcion_corta', $producto->descripcion_corta) }}"
                                    maxlength="255">

                                @error('descripcion_corta')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror

                            </div>

                            {{-- PRECIO COMPRA --}}
                            <div class="col-md-3">
                                <label>Precio compra (*)</label>
                                <input type="number" step="0.01" name="precio_compra" id="precio_compra" class="form-control"
                                    value="{{ old('precio_compra', $producto->precio_compra) }}" required>
                            </div>

                            {{-- PRECIO VENTA --}}
                            <div class="col-md-3">
                                <label>Precio venta (*)</label>
                                <input type="number" step="0.01" name="precio_venta" id="precio_venta" class="form-control"
                                    value="{{ old('precio_venta', $producto->precio_venta) }}" required>
                            </div>

                            {{-- PRECIO OFERTA --}}
                            <div class="col-md-3">
                                <label>Precio oferta</label>
                                <input type="number" step="0.01" name="precio_oferta" id="precio_oferta" class="form-control"
                                    value="{{ old('precio_oferta', $producto->precio_oferta) }}">
                            </div>

                            {{-- DESCUENTO --}}
                            <div class="col-md-3">
                                <label>Descuento (%)</label>
                                <input type="number" name="descuento" id="descuento" class="form-control"
                                    value="{{ old('descuento', $producto->descuento) }}" min="0" max="100">
                            </div>

                        </div>

                        <div class="row mt-3">

                            {{-- STOCK --}}
                            <div class="col-md-4">
                                <label>Stock (*)</label>
                                <input type="number" name="stock" class="form-control"
                                    value="{{ old('stock', $producto->stock) }}" required>
                            </div>

                            {{-- CLASIFICACION --}}
                            <div class="col-md-8">
                                <label class="form-label d-block fw-bold mb-2">Clasificación del Producto</label>
                                <div class="d-flex flex-wrap gap-3 mt-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="destacado" id="destacado" value="1" {{ old('destacado', $producto->destacado) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="destacado"><i class="bi bi-star-fill text-warning me-1"></i> Destacado</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="nuevo" id="nuevo" value="1" {{ old('nuevo', $producto->nuevo) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="nuevo"><i class="bi bi-clock-fill text-primary me-1"></i> Nuevo</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="recomendado" id="recomendado" value="1" {{ old('recomendado', $producto->recomendado) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="recomendado"><i class="bi bi-hand-thumbs-up-fill text-success me-1"></i> Recomendado</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="mas_vendido" id="mas_vendido" value="1" {{ old('mas_vendido', $producto->mas_vendido) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="mas_vendido"><i class="bi bi-graph-up-arrow text-danger me-1"></i> Más Vendido</label>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="row mt-3">

                            {{-- DESCRIPCIÓN LARGA --}}
                            <div class="col-md-12">
                                <label>Descripción larga</label>
                                <textarea name="descripcion_larga" id="descripcion_larga" class="form-control">
                                    {{ old('descripcion_larga', $producto->descripcion_larga) }}
                                </textarea>
                            </div>

                        </div>

                        <div class="row mt-3">

                            {{-- DETALLES TÉCNICOS --}}
                            <div class="col-md-12">
                                <label>Detalles Técnicos (Opcional)</label>
                                <textarea name="detalles_tecnicos" id="detalles_tecnicos" class="form-control">
                                    {{ old('detalles_tecnicos', $producto->detalles_tecnicos) }}
                                </textarea>
                                @error('detalles_tecnicos')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                        </div>

                        <br>

                        {{-- BOTONES --}}
                        <div class="row">
                            <div class="col-md-12">
                                <a href="{{ url('/admin/productos') }}" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-success">Actualizar</button>
                            </div>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>

    {{-- CKEDITOR --}}
    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            ClassicEditor
                .create(document.querySelector('#descripcion_larga'))
                .catch(error => console.error(error));

            ClassicEditor
                .create(document.querySelector('#detalles_tecnicos'))
                .catch(error => console.error(error));

            // Cálculo automático de descuentos y precios de ofertas
            const precioVenta = document.getElementById('precio_venta');
            const precioOferta = document.getElementById('precio_oferta');
            const descuento = document.getElementById('descuento');

            function calcularDescuento() {
                const pv = parseFloat(precioVenta.value) || 0;
                const po = parseFloat(precioOferta.value) || 0;
                if (pv > 0 && po > 0 && po < pv) {
                    const desc = Math.round(((pv - po) / pv) * 100);
                    descuento.value = desc;
                } else if (po === 0 || po >= pv) {
                    descuento.value = '';
                }
            }

            function calcularPrecioOferta() {
                const pv = parseFloat(precioVenta.value) || 0;
                const desc = parseFloat(descuento.value) || 0;
                if (pv > 0 && desc > 0 && desc <= 100) {
                    const po = pv * (1 - (desc / 100));
                    precioOferta.value = po.toFixed(2);
                } else if (desc === 0) {
                    precioOferta.value = '';
                }
            }

            precioVenta.addEventListener('input', calcularDescuento);
            precioOferta.addEventListener('input', calcularDescuento);
            descuento.addEventListener('input', calcularPrecioOferta);
        });
    </script>

@endsection