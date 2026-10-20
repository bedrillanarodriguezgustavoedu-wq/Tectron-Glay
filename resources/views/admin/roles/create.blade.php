@extends('layouts.admin')

@section('content')
    <div class="mb-4">
        <h1 class="h3 mb-1">Crear rol</h1>
        <p class="text-muted mb-0">Los permisos se asignan después de crear el perfil.</p>
    </div>

    <div class="card border-0 shadow-sm" style="max-width: 680px;">
        <div class="card-body p-4">
            <form action="{{ route('admin.roles.store') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label for="name" class="form-label">Nombre del rol</label>
                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name') }}" maxlength="255" autocomplete="off" required autofocus>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">El nombre se guardará en mayúsculas. Por ejemplo: SUPERVISOR.</div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Crear rol
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
