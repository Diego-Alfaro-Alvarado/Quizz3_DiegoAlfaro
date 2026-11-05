<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Validator;

class Curso extends Model
{
    protected $table = 'cursos';

    protected $fillable = ['sigla', 'nombre_curso', 'creditos'];

    protected function casts(): array
    {
        return ['creditos' => 'integer'];
    }

    public function grupos(): HasMany
    {
        return $this->hasMany(Grupo::class);
    }

    public function gruposAbiertos(): Collection
    {
        return $this->grupos()->where('abierto', true)->get();
    }

    public static function registrar(array $datos): self
    {
        $validados = Validator::make($datos, [
            'sigla' => ['required', 'string', 'max:10', 'unique:cursos,sigla'],
            'nombre_curso' => ['required', 'string', 'max:150'],
            'creditos' => ['required', 'integer', 'min:1'],
        ])->validate();

        return self::create($validados);
    }
}