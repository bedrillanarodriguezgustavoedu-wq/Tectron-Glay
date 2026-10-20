@extends('layouts.admin')

@section('content')
    <h1>Datos del producto: {{ $producto->nombre }}
        <div style="float: right;">
            <a href="{{ url('/admin/productos') }}" class="btn btn-secondary">Volver</a>
        </div>
    </h1>
    <hr>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>LLene los campos del formulario

                    </h4>
                </div>
                <div class="card-body">

                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="categoria_id">Categoría</label>
                                <p><i class="bi bi-tags"></i> {{ $producto->categoria?->nombre ?? 'Sin categoría' }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="marca_id">Marca</label>
                                <p><i class="bi bi-award"></i> {{ $producto->marca_nombre ?? 'Sin marca' }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="nombre">Nombre del Producto</label>
                                <p><i class="bi bi-box"></i> {{ $producto->nombre }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="codigo">Código</label>
                                <p><i class="bi bi-upc-scan"></i> {{ $producto->codigo }}</p>
                            </div>
                        </div>

                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="descripcion_corta">Descripción Corta</label>
                                        <p><i class="bi bi-text-left"></i> {{ $producto->descripcion_corta }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="precio_compra">Precio de Compra</label>
                                        <p><i class="bi bi-currency-dollar"></i> {{ $producto->precio_compra }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="precio_venta">Precio de Venta</label>
                                        <p><i class="bi bi-currency-dollar"></i> {{ $producto->precio_venta }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="precio_oferta">Precio de Oferta</label>
                                        <p><i class="bi bi-currency-dollar"></i> {{ $producto->precio_oferta ?? 'Sin oferta' }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="descuento">Descuento (%)</label>
                                        <p><i class="bi bi-percent"></i> {{ $producto->descuento ?? '0' }}%</p>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="stock">Stock</label>
                                        <p><i class="bi bi-box-seam"></i> {{ $producto->stock }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Clasificación</label>
                                        <p class="mt-1">
                                            @if($producto->destacado) <span class="badge bg-warning text-dark"><i class="bi bi-star-fill"></i> Destacado</span> @endif
                                            @if($producto->nuevo) <span class="badge bg-primary"><i class="bi bi-clock-fill"></i> Nuevo</span> @endif
                                            @if($producto->recomendado) <span class="badge bg-success"><i class="bi bi-hand-thumbs-up-fill"></i> Recomendado</span> @endif
                                            @if($producto->mas_vendido) <span class="badge bg-danger"><i class="bi bi-graph-up-arrow"></i> Más Vendido</span> @endif
                                            @if(!$producto->destacado && !$producto->nuevo && !$producto->recomendado && !$producto->mas_vendido)
                                                <span class="text-muted">Ninguna</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="descripcion_larga">Descripción Larga</label>
                                        <p>{!! $producto->descripcion_larga !!} </p>
                                    </div>
                                </div>

                                <div class="col-md-12 mt-3">
                                    <div class="form-group">
                                        <label for="detalles_tecnicos">Detalles Técnicos</label>
                                        <div>
                                            @if(!empty($producto->detalles_tecnicos))
                                                {!! $producto->detalles_tecnicos !!}
                                            @else
                                                <span class="text-muted"><i>No registrado</i></span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <hr>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Imagenes del Producto</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach ($producto->imagenes as $imagen)
                            <div class="col-md-3" style="margin-bottom: 20px;">
                                <div class="card">
                                    <img src="{{ $imagen->url }}" class="card-img-top"
                                        alt="Imagen del producto">
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
