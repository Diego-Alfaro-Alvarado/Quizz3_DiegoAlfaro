<?php

namespace Tests\Feature;

use App\Models\Curso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CursoControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_crea_un_curso_y_lo_guarda_en_la_base_de_datos(): void
    {
        $response = $this->postJson('/api/cursos', [
            'sigla' => 'IF6100',
            'nombre_curso' => 'Análisis y Diseño de Sistemas',
            'creditos' => 4,
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['sigla' => 'IF6100', 'creditos' => 4]);

        $this->assertDatabaseHas('cursos', [
            'sigla' => 'IF6100',
            'nombre_curso' => 'Análisis y Diseño de Sistemas',
            'creditos' => 4,
        ]);
        $this->assertDatabaseCount('cursos', 1);
    }

    public function test_store_no_guarda_un_curso_sin_datos_obligatorios(): void
    {
        $response = $this->postJson('/api/cursos', [
            'sigla' => 'IF6200',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nombre_curso', 'creditos']);

        $this->assertDatabaseCount('cursos', 0);
    }

    public function test_store_no_permite_siglas_repetidas(): void
    {
        Curso::create([
            'sigla' => 'IF6100',
            'nombre_curso' => 'Análisis y Diseño de Sistemas',
            'creditos' => 4,
        ]);

        $response = $this->postJson('/api/cursos', [
            'sigla' => 'IF6100',
            'nombre_curso' => 'Otro curso',
            'creditos' => 3,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['sigla']);
        $this->assertDatabaseCount('cursos', 1);
    }
}