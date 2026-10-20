<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictGoogleCustomerAdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            $request->session()->get('google_customer_mode', false)
            && $request->is('home', 'admin', 'admin/*')
        ) {
            abort(403);
        }

        return $next($request);
    }
}
