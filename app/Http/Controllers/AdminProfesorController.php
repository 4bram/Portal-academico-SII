<?php

namespace App\Http\Controllers;

use App\Models\Profesor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminProfesorController extends Controller
{
    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'correo' => 'nullable|email|max:255|unique:profesores,correo',
            'cubiculo' => 'nullable|string|max:255',
            'departamento' => 'nullable|string|max:255',
        ]);

        $profesor = Profesor::create($datos);

        return response()->json([
            'ok' => true,
            'message' => 'Profesor creado',
            'data' => $profesor->load(['horarios', 'materias']),
        ], 201);
    }

    public function update(Request $request, Profesor $profesor)
    {
        $datos = $request->validate([
            'nombre' => 'sometimes|required|string|max:255',
            'correo' => ['sometimes', 'nullable', 'email', 'max:255',
                Rule::unique('profesores', 'correo')->ignore($profesor->id)],
            'cubiculo' => 'sometimes|nullable|string|max:255',
            'departamento' => 'sometimes|nullable|string|max:255',
        ]);

        $profesor->update($datos);

        return response()->json([
            'ok' => true,
            'message' => 'Profesor actualizado',
            'data' => $profesor->load(['horarios', 'materias']),
        ]);
    }

    public function destroy(Profesor $profesor)
    {
        // Sus horarios y materias se borran solos (cascade en la migración)
        $profesor->delete();

        return response()->json(['ok' => true, 'message' => 'Profesor eliminado']);
    }
}