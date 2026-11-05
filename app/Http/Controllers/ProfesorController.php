<?php

namespace App\Http\Controllers;

use App\Models\Profesor;
use Illuminate\Http\Request;

class ProfesorController extends Controller
{
    public function index(Request $request)
    {
        $query = Profesor::with(['horarios', 'materias'])->orderBy('nombre');

        // ?buscar=laura  -> filtra por nombre o correo
        if ($buscar = $request->query('buscar')) {
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'ilike', "%{$buscar}%")
                  ->orWhere('correo', 'ilike', "%{$buscar}%");
            });
        }

        // ?clave_materia=IDD-2502 -> profesores que dan esa materia
        if ($clave = $request->query('clave_materia')) {
            $query->whereHas('materias', fn ($q) => $q->where('clave_materia', $clave));
        }

        return response()->json(['ok' => true, 'data' => $query->get()]);
    }

    public function show(int $id)
    {
        $profesor = Profesor::with(['horarios', 'materias'])->find($id);

        if (! $profesor) {
            return response()->json([
                'ok' => false,
                'message' => 'Profesor no encontrado',
            ], 404);
        }

        return response()->json(['ok' => true, 'data' => $profesor]);
    }
}