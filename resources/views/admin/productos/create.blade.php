@extends('layouts.admin')

@section('content')
    <h1>Creacion de un nuevo producto</h1>
    <hr>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>LLene los campos del formulario</h4>
                </div>
                <div class="card-body">
                    <form action="{{ url('/admin/productos/create') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="categoria_id">Categoría (*)</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-tags"></i></span>
                                        <select name="categoria_id" id="categoria_id" class="form-control" required>
                                            <option value="">Seleccione una categoría...</option>
                                            @foreach ($categorias as $categoria)
                                                <option value="{{ $categoria->id }}"
                                                    {{ old('categoria_id') == $categoria->id ? 'selected' : '' }}>
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
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="marca_id">Marca</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-award"></i></span>
                                        <select name="marca_id" id="marca_id" class="form-control">
                                            <option value="">Seleccione una marca...</option>
                                            @foreach ($marcas as $marca)
                                                <option value="{{ $marca->id }}"
                                                    {{ old('marca_id') == $marca->id ? 'selected' : '' }}>
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
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="nombre">Nombre del Producto (*)</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-box"></i></span>
                                        <input type="text" name="nombre" id="nombre" class="form-control"
                                            value="{{ old('nombre') }}" placeholder="Nombre completo del producto" required>
                                    </div>
                                    @error('nombre')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="codigo">Código (*)</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                                        <input type="text" name="codigo" id="codigo" class="form-control"
                                            value="{{ old('codigo') }}" placeholder="Código único del producto" required>
                                    </div>
                                    @error('codigo')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="descripcion_corta">Descripción Corta (*)</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-text-left"></i></span>
                                                <input name="descripcion_corta" id="descripcion_corta" class="form-control"
                                                    placeholder="Descripción breve del producto (máx. 255 caracteres)"
                                                    maxlength="255" value="{{ old('descripcion_corta') }}" required>
                                            </div>
                                            @error('descripcion_corta')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                            <small class="text-muted"><span id="contador_corta">0</span>/255
                                                caracteres</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="precio_compra">Precio de Compra (*)</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-currency-dollar"></i></span>
                                                <input type="number" name="precio_compra" id="precio_compra"
                                                    class="form-control" value="{{ old('precio_compra') }}"
                                                    placeholder="0.00" step="0.01" min="0" required>
                                            </div>
                                            @error('precio_compra')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="precio_venta">Precio de Venta (*)</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-currency-dollar"></i></span>
                                                <input type="number" name="precio_venta" id="precio_venta"
                                                    class="form-control" value="{{ old('precio_venta') }}"
                                                    placeholder="0.00" step="0.01" min="0" required>
                                            </div>
                                            @error('precio_venta')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="precio_oferta">Precio de Oferta</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-currency-dollar"></i></span>
                                                <input type="number" name="precio_oferta" id="precio_oferta"
                                                    class="form-control" value="{{ old('precio_oferta') }}"
                                                    placeholder="0.00" step="0.01" min="0">
                                            </div>
                                            @error('precio_oferta')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="descuento">Descuento (%)</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-percent"></i></span>
                                                <input type="number" name="descuento" id="descuento"
                                                    class="form-control" value="{{ old('descuento') }}"
                                                    placeholder="ej: 15" min="0" max="100">
                                            </div>
                                            @error('descuento')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="stock">Stock (*)</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-box-seam"></i></span>
                                                <input type="number" name="stock" id="stock" class="form-control"
                                                    value="{{ old('stock') }}" placeholder="Cantidad disponible"
                                                    min="0" required>
                                            </div>
                                            @error('stock')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-2 mb-3">
                                    <div class="col-md-12">
                                        <label class="form-label d-block fw-bold mb-2">Clasificación del Producto</label>
                                        <div class="d-flex flex-wrap gap-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="destacado" id="destacado" value="1" {{ old('destacado') ? 'checked' : '' }}>
                                                <label class="form-check-label" for="destacado"><i class="bi bi-star-fill text-warning me-1"></i> Destacado</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="nuevo" id="nuevo" value="1" {{ old('nuevo') ? 'checked' : '' }}>
                                                <label class="form-check-label" for="nuevo"><i class="bi bi-clock-fill text-primary me-1"></i> Nuevo</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="recomendado" id="recomendado" value="1" {{ old('recomendado') ? 'checked' : '' }}>
                                                <label class="form-check-label" for="recomendado"><i class="bi bi-hand-thumbs-up-fill text-success me-1"></i> Recomendado</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="mas_vendido" id="mas_vendido" value="1" {{ old('mas_vendido') ? 'checked' : '' }}>
                                                <label class="form-check-label" for="mas_vendido"><i class="bi bi-graph-up-arrow text-danger me-1"></i> Más Vendido</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="descripcion_larga">Descripción Larga (*)</label>
                                            <div class="input-group">
                                                <div style="width: 100%">
                                                    <textarea name="descripcion_larga" id="descripcion_larga" class="form-control ckeditor" rows="1"
                                                        placeholder="Descripción detallada del producto">{{ old('descripcion_larga') }}</textarea>
                                                </div>
                                            </div>
                                            @error('descripcion_larga')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-12 mt-3">
                                        <div class="form-group">
                                            <label for="detalles_tecnicos">Detalles Técnicos (Opcional)</label>
                                            <div class="input-group">
                                                <div style="width: 100%">
                                                    <textarea name="detalles_tecnicos" id="detalles_tecnicos" class="form-control ckeditor" rows="1"
                                                        placeholder="Especificaciones técnicas del producto">{{ old('detalles_tecnicos') }}</textarea>
                                                </div>
                                            </div>
                                            @error('detalles_tecnicos')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
                                    <script>
                                        document.addEventListener('DOMContentLoaded', function() {
                                            const editorConfig = {
                                                toolbar: {
                                                    items: [
                                                        'heading', '|',
                                                        'bold', 'italic', 'underline', 'strikethrough', 'subscript', 'superscript', '|',
                                                        'link', 'bulletedList', 'numberedList', '|',
                                                        'outdent', 'indent', '|',
                                                        'alignment', '|',
                                                        'blockQuote', 'insertTable', 'mediaEmbed', '|',
                                                        'undo', 'redo', '|',
                                                        'fontBackgroundColor', 'fontColor', 'fontSize', 'fontFamily', '|',
                                                        'code', 'codeBlock', 'htmlEmbed', '|',
                                                        'sourceEditing'
                                                    ],
                                                    shouldNotGroupWhenFull: true
                                                },
                                                language: 'es'
                                            };

                                            // Editor para descripción larga
                                            ClassicEditor
                                                .create(document.querySelector('#descripcion_larga'), editorConfig)
                                                .catch(error => {
                                                    console.error(error);
                                                });

                                            // Editor para detalles técnicos
                                            ClassicEditor
                                                .create(document.querySelector('#detalles_tecnicos'), editorConfig)
                                                .catch(error => {
                                                    console.error(error);
                                                });

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
                                </div>
                            </div>
                        </div>
                        <br>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <a href="{{ url('/admin/productos') }}" class="btn btn-secondary">Cancelar</a>
                                    <button type="submit" class="btn btn-primary">Registrar</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
