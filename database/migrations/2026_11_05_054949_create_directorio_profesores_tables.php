<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profesores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('correo')->nullable()->unique();
            $table->string('cubiculo')->nullable();
            $table->string('departamento')->nullable();
            $table->timestamps();
        });

        Schema::create('horarios_atencion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profesor_id')->constrained('profesores')->cascadeOnDelete();
            $table->string('dia'); // lunes, martes, miercoles, jueves, viernes, sabado
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->timestamps();
        });

        Schema::create('materias_profesores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profesor_id')->constrained('profesores')->cascadeOnDelete();
            $table->string('clave_materia')->index(); // la misma que usa el SII, ej. IDD-2502
            $table->string('nombre_materia')->nullable();
            $table->string('clave_periodo')->nullable(); // vacio = cualquier periodo
            $table->string('letra_grupo')->nullable();   // vacio = cualquier grupo
            $table->timestamps();
        });

        // Supabase expone las tablas por su API publica; esto la bloquea (Laravel no se ve afectado)
        if (DB::getDriverName() === 'pgsql') {
            foreach (['profesores', 'horarios_atencion', 'materias_profesores'] as $tabla) {
                DB::statement("alter table {$tabla} enable row level security");
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('materias_profesores');
        Schema::dropIfExists('horarios_atencion');
        Schema::dropIfExists('profesores');
    }
};