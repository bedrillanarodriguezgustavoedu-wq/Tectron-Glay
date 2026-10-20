@extends('layouts.web')

@section('content')
    <div class="page-title light-background">
        <div class="container d-lg-flex justify-content-between align-items-center">
            <h1 class="mb-2 mb-lg-0">Recuperar contraseña</h1>
            <nav class="breadcrumbs" aria-label="Ruta de navegación">
                <ol>
                    <li><a href="{{ url('/') }}">Principal</a></li>
                    <li class="current">Recuperar contraseña</li>
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
                        <h3>Recupera tu acceso</h3>
                        <p>Te enviaremos un enlace para crear una contraseña nueva.</p>
                    </div>

                    @if (session('status'))
                        <div class="alert alert-success" role="status">
                            <i class="bi bi-check-circle me-2" aria-hidden="true"></i>
                            Si existe una cuenta con ese correo, recibirás instrucciones para restablecer la contraseña.
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger" role="alert">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}" class="auth-form-content">
                        @csrf
                        <label for="email" class="form-label">Correo electrónico</label>
                        <div class="input-group">
                            <span class="input-icon" aria-hidden="true"><i class="bi bi-envelope"></i></span>
                            <input
                                id="email"
                                type="email"
                                class="form-control @error('email') is-invalid @enderror"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="tu@correo.com"
                                required
                                autocomplete="email"
                                maxlength="255"
                                autofocus
                                aria-describedby="recovery-help"
                            >
                        </div>
                        <p id="recovery-help" class="password-recovery__help">
                            Por seguridad, mostraremos el mismo mensaje tanto si el correo está registrado como si no.
                        </p>

                        <button type="submit" class="auth-btn primary-btn mt-3">
                            Enviar enlace de recuperación
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
        @media (max-width: 576px) {
            .password-recovery .auth-form { padding: 30px 24px; }
        }
    </style>
@endsection
