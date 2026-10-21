@extends('layouts.admin')

@section('content')
    <h1>Crear Nuevo Punto de Recojo</h1>
    <hr>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4>Llene los campos del formulario</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.puntos_recojo.store') }}" method="POST">
                        @csrf

                        @include('admin.puntos_recojo._campos')

                        <div class="form-group">
                            <a href="{{ route('admin.puntos_recojo.index') }}" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Registrar Punto</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
