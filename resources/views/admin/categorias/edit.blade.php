@extends('layouts.admin')

@section('content') <h1>Modificación de la categoría: {{ $categoria->nombre }}</h1> <hr>

<div class="row">
    <div class="col-md-8">
        <div class="card">

            <div class="card-header">
                <h4>LLene los campos del formulario</h4>
            </div>

            <div class="card-body">

                <form action="{{ url('/admin/categoria/' . $categoria->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="nombre">Nombre (*)</label>

                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-tag"></i>
                                    </span>

                                    <input type="text"
                                        name="nombre"
                                        id="nombre"
                                        class="form-control"
                                        value="{{ old('nombre', $categoria->nombre) }}"
                                        placeholder="Nombre de la categoría"
                                        required>
                                </div>

                                @error('nombre')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="slug">Slug (*)</label>

                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-link-45deg"></i>
                                    </span>

                                    <input type="text"
                                        name="slug"
                                        id="slug"
                                        class="form-control"
                                        value="{{ old('slug', $categoria->slug) }}"
                                        placeholder="slug-de-la-categoria"
                                        readonly
                                        required>
                                </div>

                                @error('slug')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror

                                <small class="text-muted">
                                    URL amigable (ej: electronica, ropa-deportiva)
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="descripcion">Descripción</label>

                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-text-paragraph"></i>
                                    </span>

                                    <textarea name="descripcion"
                                        id="descripcion"
                                        class="form-control"
                                        rows="4"
                                        placeholder="Descripción de la categoría">{{ old('descripcion', $categoria->descripcion) }}</textarea>
                                </div>

                                @error('descripcion')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="icono">Icono (Clase de Bootstrap Icons)</label>

                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-tag"></i>
                                    </span>

                                    <input type="text"
                                        name="icono"
                                        id="icono"
                                        class="form-control"
                                        value="{{ old('icono', $categoria->icono) }}"
                                        placeholder="ej: bi-laptop, bi-cpu, bi-keyboard">
                                </div>

                                @error('icono')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                                <small class="text-muted">
                                    Ingrese la clase de <a href="https://icons.getbootstrap.com/" target="_blank">Bootstrap Icons</a>. Ejemplo: <code>bi-laptop</code>, <code>bi-cpu</code>
                                </small>
                            </div>
                        </div>
                    </div>

                    <br>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <a href="{{ url('/admin/categorias') }}"
                                    class="btn btn-secondary">
                                    Cancelar
                                </a>

                                <button type="submit"
                                    class="btn btn-success">
                                    Actualizar
                                </button>
                            </div>
                        </div>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('nombre').addEventListener('input', function() {

        let nombre = this.value;

        let slug = nombre.toLowerCase()
            .replace(/[áàäâ]/g, 'a')
            .replace(/[éèëê]/g, 'e')
            .replace(/[íìïî]/g, 'i')
            .replace(/[óòöô]/g, 'o')
            .replace(/[úùüû]/g, 'u')
            .replace(/[ñ]/g, 'n')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');

        document.getElementById('slug').value = slug;
    });
</script>

@endsection
