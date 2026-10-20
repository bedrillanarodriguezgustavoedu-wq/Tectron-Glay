@extends('layouts.admin')

@section('content')
    <h1>Editar Marca: {{ $marca->nombre }}</h1>
    <hr>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4>Llene los campos del formulario</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.marcas.update', $marca->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="form-group mb-3">
                            <label for="nombre" class="form-label fw-bold">Nombre de la Marca (*)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-award"></i></span>
                                <input type="text" name="nombre" id="nombre" class="form-control"
                                    value="{{ old('nombre', $marca->nombre) }}" placeholder="ej: ASUS, Logitech, Razer" required>
                            </div>
                            @error('nombre')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="imagen" class="form-label fw-bold">Logotipo de la Marca (Suba uno nuevo para reemplazar)</label>
                            <div class="mb-2">
                                <x-marca-logo :marca="$marca" width="150px" height="80px" class="img-thumbnail" />
                            </div>
                            <input type="file" name="imagen" id="imagen" class="form-control" accept="image/*">
                            <small class="text-muted">Formatos permitidos: JPEG, PNG, JPG, WEBP. Máx. 2MB.</small>
                            @error('imagen')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group mb-4">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1" {{ old('activo', $marca->activo) ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="activo">Marca Activa (Visible en la tienda)</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <a href="{{ route('admin.marcas.index') }}" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-success">Actualizar Marca</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
