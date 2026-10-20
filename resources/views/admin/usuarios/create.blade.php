@extends('layouts.admin')

@section('content')
    <h1>Creacion de un nuevo usuario</h1>
    <hr>
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>LLene los campos del formulario</h4>
                </div>
                <div class="card-body">
                    <form action="{{ url('/admin/usuarios/create') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="rol">Roles (*)</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
                                        <select name="rol" id="rol" class="form-control" required>
                                            <option value="">Seleccione un rol...</option>
                                            @foreach ($roles as $rol)
                                                @if (!($rol->name == 'SUPER ADMIN'))
                                                    <option value="{{ $rol->name }}"
                                                        {{ old('rol') == $rol->name ? 'selected' : '' }}>
                                                        {{ $rol->name }}
                                                    </option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('rol')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Nombre (*)</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-person-badge-fill"></i></span>
                                        <input type="text" name="name" id="name" class="form-control"
                                            value="{{ old('name') }}" placeholder="Nombre completo" required>
                                    </div>
                                    @error('name')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">Correo Electrónico (*)</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
                                        <input type="email" name="email" id="email" class="form-control"
                                            value="{{ old('email') }}" placeholder="ejemplo@correo.com" required>
                                    </div>
                                    @error('email')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password">Contraseña (*)</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                        <input type="password" name="password" id="password" class="form-control"
                                            placeholder="Contraseña" required>
                                    </div>
                                    @error('password')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password-confirm">Confirmar Contraseña (*)</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-shield-lock-fill"></i></span>
                                        <input type="password" name="password_confirmation" id="password-confirm"
                                            class="form-control" placeholder="Repita la contraseña" required>
                                    </div>
                                    @error('password_confirmation')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <br>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <a href="{{ url('/admin/usuarios') }}" class="btn btn-secondary">Cancelar</a>
                                    <button type="submit" class="btn btn-primary">Registrar</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
