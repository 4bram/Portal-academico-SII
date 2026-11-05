<?php

namespace App\Http\Controllers;

use App\Services\SiiApiService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AcademicoController extends Controller
{
    public function perfil(Request $request, SiiApiService $sii)
    {
        $data = $this->consultar($request, $sii, '/movil/estudiante');
        if ($data instanceof JsonResponse) {
            return $data;
        }

        return response()->json(['ok' => true, 'data' => $data]);
    }

    // Pide un endpoint al SII con el JWT (guardado) y traduce los errores
    private function consultar(Request $request, SiiApiService $sii, string $path)
    {
        try {
            $res = $sii->get($path, $request->attributes->get('sii_jwt'));
        } catch (ConnectionException $e) {
            return response()->json([
                'ok' => false,
                'message' => 'El servidor del SII no esta disponible',
            ], 503);
        }

        if ($res->status() === 401) {
            Cache::forget($request->attributes->get('sii_key'));

            return response()->json([
                'ok' => false,
                'message' => 'La sesion expiro, inicia sesion de nuevo',
            ], 401);
        }

        if ($res->failed()) {
            return response()->json([
                'ok' => false,
                'message' => 'El SII respondio con un error',
            ], 502);
        }

        return $res->json('data');
    }
}