<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class EstudianteMatriculado extends Pivot
{
    protected $table = 'estudiante_matriculado';

    public $incrementing = false;

    protected function casts(): array
    {
        return ['nota' => 'float'];
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class);
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }
}