@php
    $ajuste = \App\Models\Ajuste::first();
    $imagen_login = $ajuste->imagen_login ?? null;
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        Login - {{ $ajuste->nombre ?? env('APP_NAME') }}
    </title>

    <link rel="stylesheet" href="{{ asset('assets/compiled/css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/compiled/css/app-dark.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/compiled/css/auth.css') }}">
</head>

<body>

    <script src="{{ asset('assets/static/js/initTheme.js') }}"></script>

    <div id="auth">
        <div class="row h-100">

            <!-- LEFT -->
            <div class="col-lg-5 col-12">
                <div id="auth-left">

                    <a href="{{ route('web.index') }}">
                        @if ($ajuste && $ajuste->logo)
                            <img src="{{ asset('storage/' . $ajuste->logo) }}" style="width:200px;">
                        @else
                            <img src="./assets/compiled/svg/logo.svg" style="width:200px;">
                        @endif
                    </a>

                    <h1 class="auth-title">
                        {{ $ajuste->nombre ?? config('app.name') }}
                    </h1>

                    <p class="auth-subtitle mb-5">
                        Ingreso al sistema
                    </p>

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="form-group mb-4">
                            <input type="email" name="email" class="form-control form-control-xl"
                                placeholder="Email" value="{{ old('email') }}" required>
                        </div>

                        <div class="form-group mb-4">
                            <input type="password" name="password" class="form-control form-control-xl"
                                placeholder="Password" required>
                        </div>

                        <div class="form-check mb-4">
                            <input type="checkbox" name="remember" id="remember"
                                {{ old('remember') ? 'checked' : '' }}>
                            <label for="remember">
                                Mantenme conectado
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block btn-lg shadow-lg mt-3">
                            Acceder
                        </button>
                    </form>

                    <!-- LINKS RESTAURADOS -->
                    <div class="text-center mt-4">

                        <p class="text-muted">
                            ¿No tienes una cuenta?
                            <a href="{{ route('register') }}">
                                Regístrate
                            </a>
                        </p>

                        @if (Route::has('password.request'))
                            <p>
                                <a href="{{ route('password.request') }}">
                                    ¿Has olvidado tu contraseña?
                                </a>
                            </p>
                        @endif

                    </div>

                </div>
            </div>

            <!-- RIGHT -->
            <div class="col-lg-7 d-none d-lg-block">
                <div id="auth-right">

                    @if ($imagen_login)
                        <img src="{{ asset('storage/' . $imagen_login) }}"
                            style="width:100%;height:100%;object-fit:cover;">
                    @else
                        <img src="./assets/compiled/svg/login.svg" style="width:100%;height:100%;object-fit:cover;">
                    @endif

                </div>
            </div>

        </div>
    </div>

</body>

</html>
