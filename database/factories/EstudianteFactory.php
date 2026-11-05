<?php

namespace Database\Factories;

use App\Models\Docente;
use App\Models\Estudiante;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Estudiante>
 */
class EstudianteFactory extends Factory
{
    public function definition(): array
    {
        return [
            // Carné único; empieza en 20000 para no chocar con C12345 de los datos fijos
            'carne' => 'C'.fake()->unique()->numberBetween(20000, 99999),
            'nombre' => fake()->firstName(),
            'apellidos' => fake()->lastName().' '.fake()->lastName(),
            'consejero_id' => Docente::factory(),
        ];
    }
}