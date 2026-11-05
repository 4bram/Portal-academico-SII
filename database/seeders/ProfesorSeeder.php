<?php

namespace Database\Seeders;

use App\Models\Profesor;
use Illuminate\Database\Seeder;

class ProfesorSeeder extends Seeder
{
    public function run(): void
    {
        $datos = [
            [
                'nombre' => 'Dra. Laura Ramírez Soto',
                'correo' => 'laura.ramirez@celaya.tecnm.mx',
                'cubiculo' => 'Edificio K, cubículo 3',
                'departamento' => 'Sistemas y Computación',
                'horarios' => [['lunes', '10:00', '12:00'], ['miercoles', '10:00', '12:00']],
                'materias' => [['IDD-2502', 'CIBERSEGURIDAD']],
            ],
            [
                'nombre' => 'M.C. Carlos Hernández Vega',
                'correo' => 'carlos.hernandez@celaya.tecnm.mx',
                'cubiculo' => 'Edificio K, cubículo 7',
                'departamento' => 'Sistemas y Computación',
                'horarios' => [['martes', '09:00', '11:00'], ['jueves', '09:00', '11:00']],
                'materias' => [['IDD-2504', 'TÓPICOS AVANZADOS DE DESARROLLO WEB'], ['IDD-2503', 'DESARROLLOS MÓVILES Y TECNOLOGÍAS EN LA NUBE']],
            ],
            [
                'nombre' => 'Ing. Marisol Torres Cruz',
                'correo' => 'marisol.torres@celaya.tecnm.mx',
                'cubiculo' => 'Edificio L, cubículo 2',
                'departamento' => 'Sistemas y Computación',
                'horarios' => [['viernes', '12:00', '14:00']],
                'materias' => [['SCA-1002', 'Administración de Redes']],
            ],
            [
                'nombre' => 'Dr. Jorge Mendoza Ibarra',
                'correo' => 'jorge.mendoza@celaya.tecnm.mx',
                'cubiculo' => 'Edificio M, cubículo 5',
                'departamento' => 'Ciencias Básicas',
                'horarios' => [['lunes', '13:00', '15:00'], ['jueves', '13:00', '15:00']],
                'materias' => [['SCD-1022', 'Simulación']],
            ],
        ];

        foreach ($datos as $d) {
            $profesor = Profesor::updateOrCreate(
                ['correo' => $d['correo']],
                [
                    'nombre' => $d['nombre'],
                    'cubiculo' => $d['cubiculo'],
                    'departamento' => $d['departamento'],
                ]
            );

            $profesor->horarios()->delete();
            foreach ($d['horarios'] as [$dia, $inicio, $fin]) {
                $profesor->horarios()->create([
                    'dia' => $dia,
                    'hora_inicio' => $inicio,
                    'hora_fin' => $fin,
                ]);
            }

            $profesor->materias()->delete();
            foreach ($d['materias'] as [$clave, $nombre]) {
                $profesor->materias()->create([
                    'clave_materia' => $clave,
                    'nombre_materia' => $nombre,
                ]);
            }
        }
    }
}