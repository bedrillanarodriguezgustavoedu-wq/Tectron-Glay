@extends('layouts.admin')
@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-6 order-md-1 order-last">
                    <h3>Perfil de la cuenta</h3>
                    <p class="text-subtitle text-muted">Una pagina donde los usuarios pueden cambiar la información del
                        perfil</p>
                </div>
            </div>
        </div>
        <section class="section">
            <div class="row">
                <div class="col-12 col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-center align-items-center flex-column">
                                <div class="avatar avatar-2xl">
                                    <img src="{{ asset('storage/' . $ajuste->logo) }}" alt="Avatar">
                                </div>

                                <h3 class="mt-3">{{ $usuario->name }}</h3>
                                <p class="text-small">Role: {{ Auth::user()->roles->pluck('name')->implode(', ') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-8">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <form action="{{ url('/admin/usuario/' . $usuario->id . '/update_perfil') }}"
                                        method="post">
                                        @csrf
                                        @method('PUT')
                                        <div class="form-group">
                                            <label for="name" class="form-label">Nombre del usuario</label>
                                            <input type="text" name="name" id="name" class="form-control"
                                                placeholder="Your Name" value="{{ $usuario->name }}">
                                        </div>
                                        <div class="form-group">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="text" name="email" id="email" class="form-control"
                                                placeholder="Your Email" value="{{ $usuario->email }}">
                                        </div>
                                        <hr>
                                        <div class="form-group">
                                            <button type="submit" class="btn btn-primary">Guardar cambios</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Cambiar contraseña</h5>
                                </div>
                                <div class="card-body">
                                    <form action="{{ url('/admin/usuario/' . $usuario->id . '/update_password') }}"
                                        method="post">
                                        @csrf
                                        @method('PUT')
                                        <div class="form-group my-2">
                                            <label for="current_password" class="form-label">Contraseña actual</label>
                                            <input type="password" name="current_password" id="current_password"
                                                class="form-control" value="">
                                            @error('current_password')
                                                <div role="alert">
                                                    <small style="color: red">{{ $message }}</strong>
                                                </div>
                                            @enderror
                                        </div>
                                        <div class="form-group my-2">
                                            <label for="password" class="form-label">Nueva contraseña</label>
                                            <input type="password" name="password" id="password" class="form-control"
                                                value="">
                                            @error('password')
                                                <div role="alert">
                                                    <small style="color: red">{{ $message }}</strong>
                                                </div>
                                            @enderror
                                        </div>
                                        <div class="form-group my-2">
                                            <label for="confirm_password" class="form-label">Confirmar contraseña</label>
                                            <input type="password" name="password_confirmation" id="confirm_password"
                                                class="form-control" value="">
                                            @error('password_confirmation')
                                                <div role="alert">
                                                    <small style="color: red">{{ $message }}</strong>
                                                </div>
                                            @enderror
                                        </div>
                                        <hr>

                                        <div class="form-group my-2 d-flex ">
                                            <button type="submit" class="btn btn-primary">Guardar cambios</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </section>
    </div>
@endsection
