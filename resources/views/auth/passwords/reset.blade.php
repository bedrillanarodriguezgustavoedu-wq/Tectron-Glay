@extends('layouts.web')

@section('content')
    <div class="page-title light-background">
        <div class="container d-lg-flex justify-content-between align-items-center">
            <h1 class="mb-2 mb-lg-0">Crear contraseña nueva</h1>
            <nav class="breadcrumbs" aria-label="Ruta de navegación">
                <ol>
                    <li><a href="{{ url('/') }}">Principal</a></li>
                    <li class="current">Restablecer contraseña</li>
                </ol>
            </nav>
        </div>
    </div>

    <section class="login section password-recovery">
        <div class="container" data-aos="fade-up">
            <div class="auth-container password-recovery__card">
                <div class="auth-form active">
                    <div class="form-header">
                        <div class="password-recovery__icon" aria-hidden="true">
                            <i class="bi bi-shield-lock"></i>
                        </div>
                        <h3>Protege tu cuenta</h3>
                        <p>Elige una contraseña nueva para volver a iniciar sesión.</p>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger" role="alert">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.update') }}" class="auth-form-content">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">

                        <label for="email" class="form-label">Correo electrónico</label>
                        <div class="input-group">
                            <span class="input-icon" aria-hidden="true"><i class="bi bi-envelope"></i></span>
                            <input
                                id="email"
                                type="email"
                                class="form-control @error('email') is-invalid @enderror"
                                name="email"
                                value="{{ $email ?? old('email') }}"
                                required
                                autocomplete="email"
                                maxlength="255"
                                readonly
                            >
                        </div>

                        <label for="password" class="form-label">Contraseña nueva</label>
                        <div class="input-group">
                            <span class="input-icon" aria-hidden="true"><i class="bi bi-lock"></i></span>
                            <input
                                id="password"
                                type="password"
                                class="form-control @error('password') is-invalid @enderror"
                                name="password"
                                required
                                minlength="8"
                                autocomplete="new-password"
                                autofocus
                                aria-describedby="password-help"
                            >
                        </div>
                        <p id="password-help" class="password-recovery__help">Usa al menos 8 caracteres y evita contraseñas fáciles de adivinar.</p>

                        <label for="password-confirm" class="form-label mt-3">Confirmar contraseña</label>
                        <div class="input-group">
                            <span class="input-icon" aria-hidden="true"><i class="bi bi-lock-fill"></i></span>
                            <input
                                id="password-confirm"
                                type="password"
                                class="form-control"
                                name="password_confirmation"
                                required
                                minlength="8"
                                autocomplete="new-password"
                            >
                        </div>

                        <button type="submit" class="auth-btn primary-btn mt-4">
                            Guardar contraseña nueva
                            <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </button>
                    </form>

                    <div class="password-recovery__links">
                        <a href="{{ route('web.login') }}"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Volver a iniciar sesión</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <style>
        .password-recovery__card { max-width: 520px; }
        .password-recovery .auth-form { display: block; }
        .password-recovery__icon {
            width: 56px; height: 56px; margin: 0 auto 18px; display: grid; place-items: center;
            border-radius: 16px; background: color-mix(in srgb, var(--color-primary) 10%, white);
            color: var(--color-primary); font-size: 25px;
        }
        .password-recovery .form-header { margin-bottom: 28px; }
        .password-recovery .form-label { font-weight: 600; margin-bottom: 8px; }
        .password-recovery__help { color: #64748b; font-size: .875rem; margin: -8px 0 0; }
        .password-recovery__links { text-align: center; margin-top: 24px; font-weight: 600; }
        .password-recovery__links a { text-decoration: none; }
        .password-recovery input[readonly] { background-color: #f8fafc; }
        @media (max-width: 576px) {
            .password-recovery .auth-form { padding: 30px 24px; }
        }
    </style>
@endsection
