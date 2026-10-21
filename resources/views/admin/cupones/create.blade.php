@extends('layouts.admin')

@section('content')
    <h1>Crear Nuevo Cupón</h1>
    <hr>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4>Llene los campos del formulario</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.cupones.store') }}" method="POST">
                        @csrf

                        <div class="form-group mb-3">
                            <label for="codigo" class="form-label fw-bold">Código del Cupón (*)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-ticket-perforated"></i></span>
                                <input type="text" name="codigo" id="codigo" class="form-control text-uppercase"
                                    value="{{ old('codigo') }}" placeholder="NAVIDAD25" maxlength="20" required>
                            </div>
                            <small class="text-muted">Solo mayúsculas y números, sin espacios. Máx. 20 caracteres.</small>
                            @error('codigo')
                                <small class="text-danger d-block">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="descuento_porcentaje" class="form-label fw-bold">Descuento (%) (*)</label>
                            <div class="input-group">
                                <span class="input-group-text">%</span>
                                <input type="number" name="descuento_porcentaje" id="descuento_porcentaje"
                                    class="form-control" value="{{ old('descuento_porcentaje', 10) }}" min="1" max="99" required>
                            </div>
                            @error('descuento_porcentaje')
                                <small class="text-danger d-block">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="fecha_expiracion" class="form-label fw-bold">Fecha de vencimiento</label>
                            <input type="date" name="fecha_expiracion" id="fecha_expiracion" class="form-control"
                                value="{{ old('fecha_expiracion') }}">
                            <small class="text-muted">Déjalo vacío para que el cupón no caduque nunca.</small>
                            @error('fecha_expiracion')
                                <small class="text-danger d-block">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group mb-4">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1"
                                    @checked(old('activo', true))>
                                <label class="form-check-label fw-bold" for="activo">Cupón Activo (se puede usar de inmediato)</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <a href="{{ route('admin.cupones.index') }}" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Registrar Cupón</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
