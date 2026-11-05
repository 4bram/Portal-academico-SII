<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HorarioAtencion extends Model
{
    protected $table = 'horarios_atencion';

    protected $fillable = ['profesor_id', 'dia', 'hora_inicio', 'hora_fin'];

    public function profesor()
    {
        return $this->belongsTo(Profesor::class);
    }
}