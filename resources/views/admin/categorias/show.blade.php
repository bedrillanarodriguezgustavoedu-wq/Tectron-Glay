@extends('layouts.admin')

@section('content')
    <h1>Datos de la categoría: {{ $categoria->nombre }}</h1>
    <hr>

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h4>Datos registrados</h4>
                </div>

                <div class="card-body">

                    <div class="row">

                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Nombre de la categoría</label>
                                <p>
                                    <i class="bi bi-tag-fill"></i>
                                    {{ $categoria->nombre }}
                                </p>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Slug</label>
                                <p>
                                    <i class="bi bi-link-45deg"></i>
                                    {{ $categoria->slug }}
                                </p>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Icono</label>
                                <p>
                                    <i class="{{ $categoria->icono ?? 'bi-tag' }} fs-5"></i>
                                    {{ $categoria->icono ?? 'Por defecto (bi-tag)' }}
                                </p>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Descripción</label>
                                <p>
                                    <i class="bi bi-text-paragraph"></i>
                                    {{ $categoria->descripcion ?? 'Sin descripción' }}
                                </p>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Fecha y hora de registro</label>
                                <p>
                                    <i class="bi bi-clock"></i>
                                    {{ $categoria->created_at }}
                                </p>
                            </div>
                        </div>

                    </div>

                    <br>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <a href="{{ url('/admin/categorias') }}" class="btn btn-secondary">
                                    Volver
                                </a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
