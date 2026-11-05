<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SiiToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        $key = $token ? 'sii_token:' . hash('sha256', $token) : null;
        $jwt = $key ? Cache::get($key) : null;

        if (! $jwt) {
            return response()->json([
                'ok' => false,
                'message' => 'Sesion no valida o ya expirada',
            ], 401);
        }

        // Para que los controladores usen el JWT de la api del SII sin volver a buscarlo
        $request->attributes->set('sii_jwt', $jwt);
        $request->attributes->set('sii_key', $key);

        return $next($request);
    }
}