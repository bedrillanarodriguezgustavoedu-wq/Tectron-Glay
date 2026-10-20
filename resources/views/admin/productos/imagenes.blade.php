@extends('layouts.admin')

@section('content')
    <h1>
        Datos del producto: {{ $producto->nombre }}
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
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="categoria_id">Categoría</label>
                                <p><i class="bi bi-tags"></i> {{ $producto->categoria?->nombre ?? 'Sin categoría' }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="nombre">Nombre del Producto</label>
                                <p><i class="bi bi-box"></i> {{ $producto->nombre }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
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
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="stock">Stock</label>
                                        <p><i class="bi bi-box-seam"></i> {{ $producto->stock }}</p>
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
                    <h4 class="card-title">Imágenes del producto</h4>
                    <div style="float: right">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#exampleModal">
                            <i class="bi bi-plus"></i> Cargar imagen
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalLabel">Cargar imagen del producto</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form action="{{ url('/admin/producto/' . $producto->id . '/upload_imagen') }}"
                                        method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <br>
                                                    <label for="imagen" class="form-label">Imagen de producto (*)</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="bi bi-camera"></i></span>
                                                        <input type="file" name="imagen" id="imagen"
                                                            onchange="mostrarImagen(event)"
                                                            class="form-control @error('imagen') is-invalid @enderror"
                                                            accept="image/*" required>
                                                        @error('imagen')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                                <center>
                                                    <img src="" id="preview2"
                                                        style="max-width: 200px; margin-top:10px" alt="">
                                                </center>
                                                <script>
                                                    const mostrarImagen = e => {
                                                        document.getElementById('preview2').src = URL.createObjectURL(e.target.files[0]);
                                                    }
                                                </script>
                                                <br>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <button type="button" class="btn btn-secondary"
                                                    data-bs-dismiss="modal">Cerrar</button>
                                                <button type="submit" class="btn btn-primary">Guardar imagen</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        @foreach ($producto->imagenes as $imagen)
                            <div class="col-md-3" style="margin-bottom: 20px;">
                                <div class="card">
                                    <img src="{{ $imagen->url }}" class="card-img-top"
                                        alt="Imagen del producto">
                                    <br>
                                    <form action="{{ url('/admin/producto/imagen/' . $imagen->id . '/destroy_imagen') }}"
                                        method="post" id="miFormulario{{ $imagen->id }}" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm btn-block"
                                            onclick="preguntar{{ $imagen->id }}(event)">
                                            <i class="bi bi-trash"></i> Borrar imagen
                                        </button>
                                    </form>
                                    <script>
                                        function preguntar{{ $imagen->id }}(event) {
                                            event.preventDefault();
                                            Swal.fire({
                                                title: '¿Desea eliminar este registro?',
                                                text: '',
                                                icon: 'question',
                                                showDenyButton: true,
                                                confirmButtonText: 'Eliminar',
                                                confirmButtonColor: '#a5161d',
                                                denyButtonColor: '#270a0a',
                                                denyButtonText: 'Cancelar',
                                            }).then((result) => {
                                                if (result.isConfirmed) {
                                                    // JavaScript puro para enviar el formulario
                                                    document.getElementById('miFormulario{{ $imagen->id }}').submit();
                                                }
                                            });
                                        }
                                    </script>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
