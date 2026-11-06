<?php

namespace App\Http\Controllers;

use App\Models\HorarioAtencion;
use App\Models\Profesor;
use Illuminate\Http\Request;

class AdminHorarioController extends Controller
{
    private function reglas(): array
    {
        return [
            'dia' => 'required|in:lunes,martes,miercoles,jueves,viernes,sabado',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
        ];
    }

    public function store(Request $request, Profesor $profesor)
    {
        $horario = $profesor->horarios()->create($request->validate($this->reglas()));

        return response()->json(['ok' => true, 'message' => 'Horario creado', 'data' => $horario], 201);
    }

    public function update(Request $request, HorarioAtencion $horario)
    {
        $horario->update($request->validate($this->reglas()));

        return response()->json(['ok' => true, 'message' => 'Horario actualizado', 'data' => $horario]);
    }

    public function destroy(HorarioAtencion $horario)
    {
        $horario->delete();

        return response()->json(['ok' => true, 'message' => 'Horario eliminado']);
    }
}