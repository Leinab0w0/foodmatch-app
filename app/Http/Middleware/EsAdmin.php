<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EsAdmin
{
    /**
     * Deja pasar solo a usuarios con es_admin = true.
     * Cualquier otra persona recibe un 403 (acceso prohibido).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->es_admin) {
            abort(403, 'Solo para administradores.');
        }

        return $next($request);
    }
}
