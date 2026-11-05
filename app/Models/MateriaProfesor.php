<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MateriaProfesor extends Model
{
    protected $table = 'materias_profesores';

    protected $fillable = ['profesor_id', 'clave_materia', 'nombre_materia', 'clave_periodo', 'letra_grupo'];

    public function profesor()
    {
        return $this->belongsTo(Profesor::class);
    }
}