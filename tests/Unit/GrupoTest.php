<?php

namespace Tests\Unit;

use App\Models\Curso;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\EstudianteMatriculado;
use App\Models\Grupo;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrupoTest extends TestCase
{
    use RefreshDatabase;

    private function crearCurso(): Curso
    {
        return Curso::create([
            'sigla' => 'IF6100',
            'nombre_curso' => 'Análisis y Diseño de Sistemas',
            'creditos' => 4,
        ]);
    }

    private function crearDocente(int $codigo, string $nombre, string $apellidos): Docente
    {
        return Docente::create([
            'cod_empleado' => $codigo,
            'nombre_empleado' => $nombre,
            'apellidos_empleado' => $apellidos,
        ]);
    }

    private function crearEstudiantes(int $cantidad, Docente $consejero): array
    {
        $estudiantes = [];
        for ($i = 1; $i <= $cantidad; $i++) {
            $estudiantes[] = Estudiante::create([
                'carne' => sprintf('C%05d', $i),
                'nombre' => "Estudiante $i",
                'apellidos' => 'Prueba Prueba',
                'consejero_id' => $consejero->id,
            ]);
        }
        return $estudiantes;
    }

    public function test_instancia_de_grupo_contiene_toda_su_informacion(): void
    {
        $curso = $this->crearCurso();
        $docente1 = $this->crearDocente(1001, 'Ana', 'Mora Rojas');
        $docente2 = $this->crearDocente(1002, 'Luis', 'Vargas Solís');

        $grupo = Grupo::create([
            'curso_id' => $curso->id,
            'numero_ciclo' => 2,
            'ano' => 2026,
            'numero_grupo' => 1,
            'abierto' => true,
        ]);

        $grupo->asignarDocente($docente1);
        $grupo->asignarDocente($docente2);

        $estudiantes = $this->crearEstudiantes(15, $docente1);
        foreach ($estudiantes as $estudiante) {
            $grupo->matricular($estudiante);
        }

        $grupo->registrarNota($estudiantes[0], 85.5);

        $grupo = Grupo::with(['curso', 'docentes', 'estudiantes.consejero'])->find($grupo->id);

        $this->assertSame(2, $grupo->numero_ciclo);
        $this->assertSame(2026, $grupo->ano);
        $this->assertSame(1, $grupo->numero_grupo);
        $this->assertTrue($grupo->abierto);

        $this->assertSame('IF6100', $grupo->curso->sigla);
        $this->assertSame('Análisis y Diseño de Sistemas', $grupo->curso->nombre_curso);
        $this->assertSame(4, $grupo->curso->creditos);

        $this->assertCount(2, $grupo->docentes);
        $this->assertEqualsCanonicalizing([1001, 1002], $grupo->docentes->pluck('cod_empleado')->all());

        $this->assertCount(15, $grupo->estudiantes);
        $this->assertTrue($grupo->cumpleReglas());

        $matriculado = $grupo->estudiantes->firstWhere('carne', 'C00001');
        $this->assertInstanceOf(EstudianteMatriculado::class, $matriculado->pivot);
        $this->assertEquals(85.5, $matriculado->pivot->nota);

        $this->assertSame('Ana', $matriculado->consejero->nombre_empleado);
    }

    public function test_la_nota_queda_pendiente_hasta_finalizar_el_curso(): void
    {
        $curso = $this->crearCurso();
        $docente = $this->crearDocente(2001, 'Carla', 'Jiménez Soto');
        $grupo = Grupo::create(['curso_id' => $curso->id, 'numero_ciclo' => 1, 'ano' => 2026, 'numero_grupo' => 2, 'abierto' => true]);

        $estudiante = $this->crearEstudiantes(1, $docente)[0];
        $grupo->matricular($estudiante);

        $this->assertNull($grupo->estudiantes()->first()->pivot->nota);
    }

    public function test_un_grupo_no_puede_tener_mas_de_dos_docentes(): void
    {
        $curso = $this->crearCurso();
        $grupo = Grupo::create(['curso_id' => $curso->id, 'numero_ciclo' => 1, 'ano' => 2026, 'numero_grupo' => 3, 'abierto' => true]);

        $grupo->asignarDocente($this->crearDocente(3001, 'Pedro', 'Castro Ruiz'));
        $grupo->asignarDocente($this->crearDocente(3002, 'María', 'Araya León'));

        $this->expectException(DomainException::class);
        $grupo->asignarDocente($this->crearDocente(3003, 'José', 'Mena Ulate'));
    }

    public function test_grupo_con_menos_de_15_estudiantes_no_cumple_las_reglas(): void
    {
        $curso = $this->crearCurso();
        $docente = $this->crearDocente(4001, 'Laura', 'Brenes Quesada');
        $grupo = Grupo::create(['curso_id' => $curso->id, 'numero_ciclo' => 1, 'ano' => 2026, 'numero_grupo' => 4, 'abierto' => true]);
        $grupo->asignarDocente($docente);

        foreach ($this->crearEstudiantes(10, $docente) as $estudiante) {
            $grupo->matricular($estudiante);
        }

        $this->assertFalse($grupo->cumpleReglas());
    }

    public function test_un_curso_puede_tener_varios_grupos_abiertos(): void
    {
        $curso = $this->crearCurso();
        Grupo::create(['curso_id' => $curso->id, 'numero_ciclo' => 2, 'ano' => 2026, 'numero_grupo' => 1, 'abierto' => true]);
        Grupo::create(['curso_id' => $curso->id, 'numero_ciclo' => 2, 'ano' => 2026, 'numero_grupo' => 2, 'abierto' => true]);

        $this->assertCount(2, $curso->grupos);
    }
}