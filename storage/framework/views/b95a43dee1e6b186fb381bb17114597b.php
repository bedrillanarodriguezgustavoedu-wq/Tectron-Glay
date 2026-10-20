<?php $__env->startSection("meta_robots", "noindex, follow"); ?>
<?php $__env->startSection('content'); ?>
    <!-- Page Title -->
    <div class="page-title light-background">
        <div class="container d-lg-flex justify-content-between align-items-center">
            <h1 class="mb-2 mb-lg-0">Acceso</h1>
            <nav class="breadcrumbs">
                <ol>
                    <li><a href="<?php echo e(url('/')); ?>">Principal</a></li>
                    <li class="current">Acceso</li>
                </ol>
            </nav>
        </div>
    </div><!-- End Page Title -->

    <!-- Login Section -->
    <section id="login" class="login section">

        <div class="container" data-aos="fade-up" data-aos-delay="100">

            <div class="row justify-content-center">
                <div class="col-lg-8 col-md-10">
                    <div class="auth-container" data-aos="fade-in" data-aos-delay="200">

                        <!-- Login Form -->
                        <div class="auth-form login-form active">
                            <div class="form-header">
                                <h3>Bienvenido de nuevo</h3>
                                <p>Inicia sesión para continuar con tu cuenta.</p>
                            </div>

                            <form action="<?php echo e(route('web.autenticacion')); ?>" method="POST" class="auth-form-content" autocomplete="on">
                                <?php echo csrf_field(); ?>
                                <?php if(session('status')): ?>
                                    <div class="alert alert-info" role="status"><?php echo e(session('status')); ?></div>
                                <?php endif; ?>
                                <?php if(($galyLoginMode ?? null) === 'connect'): ?>
                                    <div class="alert alert-info" role="status">
                                        Para vincular esta cuenta, inicia sesión con el mismo correo y la contraseña de GALY. Solo GALY puede confirmar los permisos de administrador.
                                    </div>
                                    <input type="hidden" name="galy" value="connect">
                                <?php elseif(($galyLoginMode ?? null) === 'renew'): ?>
                                    <div class="alert alert-info" role="status">
                                        Inicia sesión otra vez con tu cuenta administradora GALY para renovar el acceso al panel compartido.
                                    </div>
                                <?php endif; ?>
                                <?php if($errors->has('login_error')): ?>
                                    <div class="alert alert-danger" role="alert" aria-live="polite"><?php echo e($errors->first('login_error')); ?></div>
                                <?php endif; ?>
                                <?php if($errors->any() && !$errors->has('login_error')): ?>
                                    <div class="alert alert-danger" role="alert" aria-live="polite"><?php echo e($errors->first()); ?></div>
                                <?php endif; ?>
                                <?php if(session('google_login_error')): ?>
                                    <div class="alert alert-warning" role="alert" aria-live="polite"><?php echo e(session('google_login_error')); ?></div>
                                <?php endif; ?>
                                <?php if(filled(config('services.google.client_id')) && filled(config('services.google.client_secret')) && filled(config('services.google.redirect'))): ?>
                                    <a href="<?php echo e(route('auth.google.redirect')); ?>" class="auth-btn social-btn login__google-btn mb-3">
                                        <i class="bi bi-google" aria-hidden="true"></i>
                                        Comprar con Google
                                    </a>
                                    <div class="login__account-hint" role="note">
                                        <i class="bi bi-info-circle" aria-hidden="true"></i>
                                        <span>Entrarás a la tienda como cliente, aunque este Gmail también sea administrador. Para cambiar de Gmail, cierra sesión y elige otra cuenta.</span>
                                    </div>
                                    <div class="login__divider"><span>Acceso con correo y contraseña</span></div>
                                <?php endif; ?>
                                <p class="login__section-hint">
                                    <i class="bi bi-shield-lock me-1" aria-hidden="true"></i>
                                    Administradores: usa aquí tu contraseña GALY para entrar al panel.
                                </p>
                                <div class="input-group mb-3">
                                    <label class="visually-hidden" for="loginEmail">Correo electrónico</label>
                                    <span class="input-icon" aria-hidden="true">
                                        <i class="bi bi-envelope"></i>
                                    </span>
                                    <input
                                        id="loginEmail"
                                        type="email"
                                        name="email"
                                        class="form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                        placeholder="Correo electrónico"
                                        value="<?php echo e(old('email')); ?>"
                                        required
                                        maxlength="100"
                                        autocomplete="username"
                                        inputmode="email"
                                        autocapitalize="none"
                                        aria-describedby="loginEmailError"
                                        <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-invalid="true" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    >
                                    <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <span class="visually-hidden" id="loginEmailError"><?php echo e($message); ?></span>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <div class="input-group mb-3">
                                    <label class="visually-hidden" for="loginPassword">Contraseña</label>
                                    <span class="input-icon" aria-hidden="true">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                    <input
                                        id="loginPassword"
                                        type="password"
                                        name="password"
                                        class="form-control"
                                        placeholder="Contraseña"
                                        required
                                        autocomplete="current-password"
                                        aria-describedby="login-password-hint"
                                    >
                                    <button type="button" class="password-toggle" aria-label="Mostrar contraseña" aria-pressed="false" data-password-toggle="loginPassword">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <p id="login-password-hint" class="login__password-hint">La contraseña de Google no se usa en este formulario.</p>

                                <div class="form-options mb-4">
                                    <div class="remember-me">
                                        <input type="checkbox" id="rememberLogin" name="remember" value="1" <?php echo e(old('remember') ? 'checked' : ''); ?>>
                                        <label for="rememberLogin">Recuérdame</label>
                                    </div>
                                    <a href="<?php echo e(route('password.request')); ?>" class="forgot-password">¿Has olvidado tu contraseña?</a>
                                </div>

                                <button type="submit" class="auth-btn primary-btn mb-3" data-login-submit aria-live="polite">
                                    Iniciar Sesión
                                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                </button>


                                <div class="switch-form">
                                    <span>¿No tienes una cuenta?</span>
                                    <a href="<?php echo e(route('web.registro')); ?>" class="switch-btn">Crear cuenta</a>
                                </div>
                            </form>
                        </div>

                        <!-- Register Form -->

                    </div>
                </div>
            </div>

        </div>

    </section><!-- /Login Section -->

    <style>
        .login__account-hint {
            display: flex; align-items: flex-start; gap: 9px;
            margin: -4px 0 15px; padding: 10px 12px; border-radius: 10px;
            background: #f5f8ff; color: #52627a; font-size: .8rem; line-height: 1.45;
            text-align: left;
        }
        .login__account-hint i { flex: 0 0 auto; margin-top: 2px; color: var(--color-primary, #0d6efd); }
        .login__divider {
            display: flex; align-items: center; gap: 10px; margin: 2px 0 14px;
            color: #78869a; font-size: .75rem; white-space: nowrap;
        }
        .login__divider::before, .login__divider::after {
            height: 1px; flex: 1; background: rgba(100, 116, 139, .2); content: '';
        }
        .login__section-hint {
            margin: 0 0 12px; color: #4b5563; font-size: .8rem; line-height: 1.45;
        }
        .login__password-hint { margin: -10px 0 18px 50px; color: #64748b; font-size: .75rem; line-height: 1.4; }
        .login .input-group .password-toggle {
            width: 42px; height: 42px; display: grid; place-items: center; padding: 0;
            border: 0; border-radius: 8px; background: transparent; font: inherit;
        }
        .login .input-group .password-toggle:focus-visible,
        .login .switch-form .switch-btn:focus-visible,
        .login .form-options a:focus-visible,
        .login .auth-btn:focus-visible {
            outline: 3px solid color-mix(in srgb, var(--color-primary, #0d6efd) 55%, white);
            outline-offset: 3px;
        }
        .login .auth-btn[aria-busy="true"] { cursor: wait; opacity: .82; }
        .login .auth-btn[aria-busy="true"] .spinner-border { margin-left: .45rem; vertical-align: -.1em; }
        .login .switch-form .switch-btn {
            display: inline-block; padding: 4px 7px; border-radius: 6px;
            text-decoration: none; outline: none;
        }
        .login .switch-form .switch-btn:hover { text-decoration: underline; }
        .login .auth-btn.social-btn { text-decoration: none; }
        .login .auth-btn.social-btn:hover { transform: translateY(-1px); }
        @media (max-width: 420px) {
            .login .auth-form { padding: 28px 20px; }
            .login .form-options { align-items: flex-start; }
            .login .form-options .forgot-password { font-size: 13px; }
        }
        @media (prefers-reduced-motion: reduce) {
            .login .auth-form.active { animation: none; }
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

        document.querySelector('.auth-form-content')?.addEventListener('submit', (event) => {
            const button = event.currentTarget.querySelector('[data-login-submit]');
            if (event.currentTarget.checkValidity() && button) {
                button.disabled = true;
                button.setAttribute('aria-busy', 'true');
                button.innerHTML = 'Validando acceso… <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>';
            }
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.web', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\ecommerce (4)\ecommerce\resources\views/web/login.blade.php ENDPATH**/ ?>