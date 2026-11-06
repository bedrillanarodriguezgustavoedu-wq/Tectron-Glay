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
                    <p class="text-muted mb-0" style="font-size: .85rem;">
                        La imagen marcada como <strong>Principal</strong> es la que se muestra en la ficha de la tienda.
                    </p>
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
                                                    <small class="text-muted">JPEG, PNG, GIF o WebP · máximo 2 MB.</small>
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

                    @if ($errors->any())
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const modal = document.getElementById('exampleModal');
                                if (modal) {
                                    bootstrap.Modal.getOrCreateInstance(modal).show();
                                }
                            });
                        </script>
                    @endif

                    @php
                        $nombrePrincipal = basename((string) ($producto->imagen_url ?? ''));
                        $imagenPrincipal = $producto->imagenes->first(
                            fn ($item) => basename((string) $item->imagen) === $nombrePrincipal
                        ) ?? $producto->imagenes->first();
                    @endphp
                    <div class="row">
                        @foreach ($producto->imagenes as $imagen)
                            @php $esPrincipal = $imagenPrincipal && $imagenPrincipal->id === $imagen->id; @endphp
                            <div class="col-md-3" style="margin-bottom: 20px;">
                                <div class="card position-relative h-100 {{ $esPrincipal ? 'border border-primary' : '' }}">
                                    @if ($esPrincipal)
                                        <span class="badge bg-primary position-absolute top-0 start-0 m-2">
                                            <i class="bi bi-star-fill"></i> Principal
                                        </span>
                                    @endif
                                    <img src="{{ $imagen->url }}" class="card-img-top" alt="Imagen del producto"
                                        style="height: 180px; object-fit: contain; background: #f8f9fa;">
                                    <div class="card-body p-2 d-grid gap-2">
                                        @unless ($esPrincipal)
                                            <form action="{{ route('admin.productos.imagen_principal', $imagen->id) }}"
                                                method="post">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                                                    <i class="bi bi-star"></i> Usar como principal
                                                </button>
                                            </form>
                                        @endunless
                                        <form action="{{ url('/admin/producto/imagen/' . $imagen->id . '/destroy_imagen') }}"
                                            method="post" id="miFormulario{{ $imagen->id }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm w-100"
                                                onclick="preguntar{{ $imagen->id }}(event)">
                                                <i class="bi bi-trash"></i> Borrar imagen
                                            </button>
                                        </form>
                                    </div>
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
