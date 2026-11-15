<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EsLocal
{
    /** Deja pasar solo a dueños de local (usuarios con restaurante_id). */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || empty($request->user()->restaurante_id)) {
            abort(403, 'Solo para dueños de local.');
        }

        return $next($request);
    }
}
