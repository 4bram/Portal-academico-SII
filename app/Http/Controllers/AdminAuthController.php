<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    public function login(Request $request)
    {
        $datos = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $admin = User::where('email', $datos['email'])->first();

        if (! $admin || ! Hash::check($datos['password'], $admin->password)) {
            return response()->json([
                'ok' => false,
                'message' => 'Credenciales incorrectas',
            ], 401);
        }

        return response()->json([
            'ok' => true,
            'data' => ['token' => $admin->createToken('admin')->plainTextToken],
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'ok' => true,
            'data' => $request->user()->only('name', 'email'),
        ]);
    }
}