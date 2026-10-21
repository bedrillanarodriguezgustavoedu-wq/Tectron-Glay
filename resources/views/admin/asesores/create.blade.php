@extends('layouts.admin')

@section('content')
    <h1>Crear Nuevo Asesor</h1>
    <hr>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4>Llene los campos del formulario</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.asesores.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        @include('admin.asesores._campos')

                        <div class="form-group">
                            <a href="{{ route('admin.asesores.index') }}" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Registrar Asesor</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
