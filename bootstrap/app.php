<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\RestrictGoogleCustomerAdminAccess;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        // cloudflared (y cualquier proxy delante de la app) habla con Laravel por
        // HTTP plano y avisa del HTTPS real en una cabecera. Sin leerla, `asset()`
        // escribe enlaces `http://` dentro de una página `https://` y el navegador
        // los bloquea como contenido mixto: la tienda se ve sin estilos ni menús.
        // Solo se confía en el proxy local; en un hosting hay que poner aquí su IP.
        $middleware->trustProxies(at: '127.0.0.1');

        $middleware->append(AddSecurityHeaders::class);
        $middleware->web(append: [RestrictGoogleCustomerAdminAccess::class]);

        // Mercado Pago autentica este endpoint mediante su firma HMAC. No puede
        // incluir el token CSRF que usan los formularios internos de la tienda.
        $middleware->validateCsrfTokens(except: [
            'webhooks/mercadopago',
            'api/mobile/payment-receipts',
        ]);

        $middleware->redirectTo(
            function (Request $request) {
                if ($request->is('admin') || $request->is('admin/*')) {
                    return route('login');
                }

                return route('web.login');
            }
        );

        $middleware->redirectUsersTo(
            function (Request $request) {
                if (auth()->check() && auth()->user()->hasRole('ADMINISTRADOR')) {
                    return '/home';
                }

                return '/dashboard';
            }
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->expectsJson() || $request->is('api/*'),
        );
    })->create();
