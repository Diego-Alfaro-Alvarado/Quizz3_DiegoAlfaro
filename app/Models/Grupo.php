<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Grupo extends Model
{
    public const MIN_DOCENTES = 1;
    public const MAX_DOCENTES = 2;
    public const MIN_ESTUDIANTES = 15;

    protected $table = 'grupos';

    protected $fillable = ['curso_id', 'numero_ciclo', 'ano', 'numero_grupo', 'abierto'];

    protected function casts(): array
    {
        return [
            'numero_ciclo' => 'integer',
            'ano' => 'integer',
            'numero_grupo' => 'integer',
            'abierto' => 'boolean',
        ];
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function docentes(): BelongsToMany
    {
        return $this->belongsToMany(Docente::class, 'docente_grupo')->withTimestamps();
    }

    public function estudiantes(): BelongsToMany
    {
        return $this->belongsToMany(Estudiante::class, 'estudiante_matriculado')
            ->using(EstudianteMatriculado::class)
            ->withPivot('nota')
            ->withTimestamps();
    }

    public function asignarDocente(Docente $docente): void
    {
        if ($this->docentes()->count() >= self::MAX_DOCENTES) {
            throw new DomainException('Un grupo solo puede ser impartido por 1 o 2 docentes.');
        }

        $this->docentes()->attach($docente->id);
    }

    public function matricular(Estudiante $estudiante): void
    {
        $this->estudiantes()->attach($estudiante->id);
    }

    public function registrarNota(Estudiante $estudiante, float $nota): void
    {
        $this->estudiantes()->updateExistingPivot($estudiante->id, ['nota' => $nota]);
    }

    public function cumpleReglas(): bool
    {
        $docentes = $this->docentes()->count();

        return $docentes >= self::MIN_DOCENTES
            && $docentes <= self::MAX_DOCENTES
            && $this->estudiantes()->count() >= self::MIN_ESTUDIANTES;
    }
}