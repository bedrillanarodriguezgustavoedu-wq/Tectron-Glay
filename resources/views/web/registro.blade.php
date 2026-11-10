@extends('layouts.web')
@section("meta_robots", "noindex, follow")

@section('content')
    <div class="page-title light-background">
        <div class="container d-lg-flex justify-content-between align-items-center">
            <h1 class="mb-2 mb-lg-0">Crear cuenta</h1>
            <nav class="breadcrumbs" aria-label="Ruta de navegación">
                <ol>
                    <li><a href="{{ url('/') }}">Principal</a></li>
                    <li class="current">Crear cuenta</li>
                </ol>
            </nav>
        </div>
    </div>

    <section id="register" class="register section customer-register">
        <div class="container" data-aos="fade-up">
            <div class="registration-form-wrapper">
                <div class="customer-register__content">
                    <div class="form-header text-center">
                        <div class="customer-register__icon" aria-hidden="true">
                            <i class="bi bi-person-plus"></i>
                        </div>
                        <h2>Crea tu cuenta</h2>
                        <p>Regístrate para guardar tus datos y comprar en GALY.</p>
                        <span class="customer-register__badge"><i class="bi bi-bag-check me-1" aria-hidden="true"></i> Cuenta de cliente</span>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger" role="alert" aria-live="polite">
                            <p class="fw-semibold mb-1">Revisa estos datos:</p>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('web.crear_cuenta') }}" method="POST" class="customer-register__form">
                        @csrf

                        <div class="mb-3">
                            <label for="fullName" class="form-label">Nombre completo</label>
                            <div class="input-group">
                                <span class="input-group-text" aria-hidden="true"><i class="bi bi-person"></i></span>
                                <input
                                    type="text"
                                    class="form-control @error('name') is-invalid @enderror"
                                    id="fullName"
                                    name="name"
                                    value="{{ old('name') }}"
                                    placeholder="Escribe tu nombre"
                                    required
                                    maxlength="100"
                                    autocomplete="name"
                                    autofocus
                                    aria-describedby="name-help"
                                >
                            </div>
                            <div id="name-help" class="form-text">Usa el nombre que quieres mostrar en tus pedidos.</div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Correo electrónico</label>
                            <div class="input-group">
                                <span class="input-group-text" aria-hidden="true"><i class="bi bi-envelope"></i></span>
                                <input
                                    type="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    id="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    placeholder="tu@correo.com"
                                    required
                                    maxlength="100"
                                    autocomplete="email"
                                    inputmode="email"
                                    autocapitalize="none"
                                    aria-describedby="email-help"
                                >
                            </div>
                            <div id="email-help" class="form-text">Si ya tienes una cuenta GALY, usa el mismo correo.</div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label">Contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text" aria-hidden="true"><i class="bi bi-lock"></i></span>
                                    <input
                                        type="password"
                                        class="form-control @error('password') is-invalid @enderror"
                                        id="password"
                                        name="password"
                                        placeholder="Mínimo 8 caracteres"
                                        required
                                        minlength="8"
                                        autocomplete="new-password"
                                        aria-describedby="password-help"
                                    >
                                    <button class="btn btn-outline-secondary customer-register__toggle" type="button" data-password-toggle="password" aria-label="Mostrar contraseña" aria-pressed="false">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <div id="password-help" class="form-text">Elige una contraseña larga y que no uses en otros sitios.</div>
                            </div>

                            <div class="col-md-6">
                                <label for="confirmPassword" class="form-label">Confirmar contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text" aria-hidden="true"><i class="bi bi-lock-fill"></i></span>
                                    <input
                                        type="password"
                                        class="form-control @error('password') is-invalid @enderror"
                                        id="confirmPassword"
                                        name="password_confirmation"
                                        placeholder="Escríbela de nuevo"
                                        required
                                        minlength="8"
                                        autocomplete="new-password"
                                        aria-describedby="password-match"
                                    >
                                    <button class="btn btn-outline-secondary customer-register__toggle" type="button" data-password-toggle="confirmPassword" aria-label="Mostrar confirmación" aria-pressed="false">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <div id="password-match" class="form-text" aria-live="polite">Ambas contraseñas deben coincidir.</div>
                            </div>
                        </div>

                        <div class="customer-register__notice">
                            <i class="bi bi-shield-check" aria-hidden="true"></i>
                            <span>Esta opción crea una cuenta de cliente. Los permisos de administrador solo los asigna un administrador autorizado.</span>
                        </div>

                        <button type="submit" class="btn btn-register w-100">
                            Crear cuenta
                            <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i>
                        </button>

                        <p class="customer-register__login text-center mb-0">
                            ¿Ya tienes una cuenta?
                            <a href="{{ route('web.login') }}">Inicia sesión</a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <style>
        .customer-register .registration-form-wrapper {
            position: relative;
            max-width: 920px;
            margin-inline: auto;
            padding: clamp(28px, 5vw, 64px);
            overflow: hidden;
            border: 1px solid color-mix(in srgb, var(--default-color), transparent 90%);
            border-radius: 22px;
            background: var(--surface-color, #fff);
            box-shadow: 0 18px 55px rgba(15, 23, 42, .07);
        }
        .customer-register__content { position: relative; z-index: 1; max-width: 650px; margin-inline: auto; }
        .customer-register .form-header { margin-bottom: 30px; }
        .customer-register__icon {
            display: grid; place-items: center; width: 58px; height: 58px; margin: 0 auto 16px;
            border-radius: 18px; background: color-mix(in srgb, var(--color-primary, #0d6efd) 10%, white);
            color: var(--color-primary, #0d6efd); font-size: 25px;
        }
        .customer-register .form-header h2 { margin-bottom: 8px; font-weight: 750; }
        .customer-register .form-header p { margin-bottom: 14px; color: #64748b; }
        .customer-register__badge {
            display: inline-block; padding: 6px 12px; border-radius: 999px;
            background: #f1f5f9; color: #334155; font-size: .85rem; font-weight: 600;
        }
        .customer-register__form .form-label { margin-bottom: 7px; font-weight: 600; }
        .customer-register__form .form-control,
        .customer-register__form .input-group-text,
        .customer-register__toggle { min-height: 50px; }
        .customer-register__form .input-group-text {
            border-right: 0; background: #f8fafc; color: #64748b;
        }
        .customer-register__form .input-group .form-control { border-left: 0; }
        .customer-register__form .input-group .form-control:focus { border-left: 1px solid var(--bs-primary); }
        .customer-register__form .form-control:focus,
        .customer-register__toggle:focus-visible,
        .customer-register__form button:focus-visible,
        .customer-register__login a:focus-visible {
            box-shadow: 0 0 0 .2rem color-mix(in srgb, var(--color-primary, #0d6efd) 20%, transparent);
        }
        .customer-register__toggle { border-left: 0; background: #fff; }
        .customer-register__notice {
            display: flex; gap: 10px; align-items: flex-start; margin: 8px 0 22px; padding: 13px 15px;
            border-radius: 12px; background: #f8fafc; color: #475569; font-size: .9rem;
        }
        .customer-register__notice i { color: var(--color-primary, #0d6efd); font-size: 1.1rem; }
        .customer-register .btn-register {
            min-height: 54px; border: 0; border-radius: 12px; background: #211f20; color: #fff;
            font-weight: 700; transition: transform .2s ease, filter .2s ease;
        }
        .customer-register .btn-register:hover { color: #fff; filter: brightness(1.12); transform: translateY(-1px); }
        .customer-register .btn-register:focus-visible { outline: 3px solid var(--color-primary, #0d6efd); outline-offset: 3px; }
        .customer-register__login { margin-top: 22px; color: #64748b; }
        .customer-register__login a { margin-left: 4px; font-weight: 700; }
        @media (max-width: 576px) {
            .customer-register .registration-form-wrapper { padding: 28px 20px; border-radius: 16px; }
            .customer-register .form-header { margin-bottom: 24px; }
        }
        @media (prefers-reduced-motion: reduce) {
            .customer-register .btn-register { transition: none; }
        }
    </style>

    <script>
        document.querySelectorAll('[data-password-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const input = document.getElementById(button.dataset.passwordToggle);
                const isVisible = input.type === 'text';
                input.type = isVisible ? 'password' : 'text';
                button.setAttribute('aria-pressed', String(!isVisible));
                button.setAttribute('aria-label', isVisible ? 'Mostrar contraseña' : 'Ocultar contraseña');
                button.querySelector('i').className = isVisible ? 'bi bi-eye' : 'bi bi-eye-slash';
            });
        });

        const password = document.getElementById('password');
        const confirmation = document.getElementById('confirmPassword');
        const matchMessage = document.getElementById('password-match');
        const updatePasswordMatch = () => {
            if (!confirmation.value) {
                matchMessage.textContent = 'Ambas contraseñas deben coincidir.';
                confirmation.setCustomValidity('');
                return;
            }

            const matches = password.value === confirmation.value;
            matchMessage.textContent = matches ? 'Las contraseñas coinciden.' : 'Las contraseñas todavía no coinciden.';
            confirmation.setCustomValidity(matches ? '' : 'Las contraseñas no coinciden.');
        };
        password.addEventListener('input', updatePasswordMatch);
        confirmation.addEventListener('input', updatePasswordMatch);
    </script>
@endsection
