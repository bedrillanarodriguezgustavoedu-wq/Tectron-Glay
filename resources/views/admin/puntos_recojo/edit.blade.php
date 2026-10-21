@extends('layouts.admin')

@section('content')
    <h1>Editar Punto de Recojo {{ $punto->nombre }}</h1>
    <hr>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4>Modificar el punto</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.puntos_recojo.update', $punto->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        @include('admin.puntos_recojo._campos', ['punto' => $punto])

                        <div class="form-group">
                            <a href="{{ route('admin.puntos_recojo.index') }}" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Actualizar Punto</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
