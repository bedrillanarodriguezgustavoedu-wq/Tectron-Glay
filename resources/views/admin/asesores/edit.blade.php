@extends('layouts.admin')

@section('content')
    <h1>Editar Asesor {{ $asesor->nombre }}</h1>
    <hr>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4>Modificar el asesor</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.asesores.update', $asesor->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        @include('admin.asesores._campos', ['asesor' => $asesor])

                        <div class="form-group">
                            <a href="{{ route('admin.asesores.index') }}" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Actualizar Asesor</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
