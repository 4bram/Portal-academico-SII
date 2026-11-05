<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profesor extends Model
{
    protected $table = 'profesores';

    protected $fillable = ['nombre', 'correo', 'cubiculo', 'departamento'];

    public function horarios()
    {
        return $this->hasMany(HorarioAtencion::class);
    }

    public function materias()
    {
        return $this->hasMany(MateriaProfesor::class);
    }
}