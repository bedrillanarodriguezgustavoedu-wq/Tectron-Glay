@extends('layouts.admin')

@section('content')
    <h1>Datos del usuario: {{ $usuario->name }}</h1>
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
                                <label>Nombre del usuario</label>
                                <p>
                                    <i class="bi bi-person-badge-fill"></i>
                                    {{ $usuario->name }}
                                </p>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Correo electrónico</label>
                                <p>
                                    <i class="bi bi-envelope-fill"></i>
                                    {{ $usuario->email }}
                                </p>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Rol del usuario</label>
                                <p>
                                    <i class="bi bi-shield-check"></i>
                                    {{ $usuario->getRoleNames()->first() ?? 'Sin rol' }}
                                </p>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Fecha y hora de registro</label>
                                <p>
                                    <i class="bi bi-clock"></i>
                                    {{ $usuario->created_at }}
                                </p>
                            </div>
                        </div>

                    </div>

                    <br>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <a href="{{ url('/admin/usuarios') }}" class="btn btn-secondary">
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