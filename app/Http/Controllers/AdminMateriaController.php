<?php

namespace App\Http\Controllers;

use App\Models\MateriaProfesor;
use App\Models\Profesor;
use Illuminate\Http\Request;

class AdminMateriaController extends Controller
{
    private function reglas(): array
    {
        return [
            'clave_materia' => 'required|string|max:50',
            'nombre_materia' => 'nullable|string|max:255',
            'clave_periodo' => 'nullable|string|max:20',
            'letra_grupo' => 'nullable|string|max:5',
        ];
    }

    public function store(Request $request, Profesor $profesor)
    {
        $materia = $profesor->materias()->create($request->validate($this->reglas()));

        return response()->json(['ok' => true, 'message' => 'Materia asignada', 'data' => $materia], 201);
    }

    public function update(Request $request, MateriaProfesor $materia)
    {
        $materia->update($request->validate($this->reglas()));

        return response()->json(['ok' => true, 'message' => 'Materia actualizada', 'data' => $materia]);
    }

    public function destroy(MateriaProfesor $materia)
    {
        $materia->delete();

        return response()->json(['ok' => true, 'message' => 'Materia eliminada']);
    }
}