<?php

namespace App\Http\Controllers;

use App\Services\SiiApiService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request, SiiApiService $sii)
    {
        $datos = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        try {
            $jwt = $sii->login($datos['email'], $datos['password']);
        } catch (ConnectionException $e) {
            return response()->json([
                'ok' => false,
                'message' => 'El servidor del SII no está disponible',
            ], 503);
        }

        if (! $jwt) {
            return response()->json([
                'ok' => false,
                'message' => 'Credenciales incorrectas',
            ], 401);
        }

        // Token propio: el front nunca ve el JWT del SII
        $token = Str::random(64);
        Cache::put('sii_token:' . hash('sha256', $token), $jwt, now()->addMinutes(55));

        return response()->json([
            'ok' => true,
            'message' => 'Sesión iniciada',
            'data' => ['token' => $token],
        ]);
    }

    public function me(Request $request)
    {
        return response()->json(['ok' => true, 'message' => 'Sesión activa']);
    }

    public function logout(Request $request)
    {
        Cache::forget($request->attributes->get('sii_key'));

        return response()->json(['ok' => true, 'message' => 'Sesión cerrada']);
    }
}