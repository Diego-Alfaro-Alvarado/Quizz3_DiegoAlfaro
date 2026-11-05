<?php

namespace Tests\Unit;

use App\Models\Curso;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Grupo;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CasosDePruebaTest extends TestCase
{
    use RefreshDatabase;

    private function crearCursoIF6100(): Curso
    {
        return Curso::create([
            'sigla' => 'IF6100',
            'nombre_curso' => 'Análisis y Diseño de Sistemas',
            'creditos' => 4,
        ]);
    }

    private function crearGrupo01(Curso $curso, bool $abierto = false): Grupo
    {
        return Grupo::create([
            'curso_id' => $curso->id,
            'numero_ciclo' => 2,
            'ano' => 2026,
            'numero_grupo' => 1,
            'abierto' => $abierto,
        ]);
    }

    private function crearDocente1001(): Docente
    {
        return Docente::create(['cod_empleado' => 1001, 'nombre_empleado' => 'Ana', 'apellidos_empleado' => 'Mora Vargas']);
    }

    private function crearDocente1002(): Docente
    {
        return Docente::create(['cod_empleado' => 1002, 'nombre_empleado' => 'Luis', 'apellidos_empleado' => 'Rojas Solano']);
    }

    private function crearDocente1003(): Docente
    {
        return Docente::create(['cod_empleado' => 1003, 'nombre_empleado' => 'Carla', 'apellidos_empleado' => 'Jiménez Araya']);
    }

    private function matricularEstudiantesFactory(Grupo $grupo, int $cantidad, Docente $consejero): void
    {
        $estudiantes = Estudiante::factory()
            ->count($cantidad)
            ->for($consejero, 'consejero')
            ->create();

        foreach ($estudiantes as $estudiante) {
            $grupo->matricular($estudiante);
        }
    }

    public function test_cp01_registrar_un_grupo_con_toda_su_informacion_y_su_curso(): void
    {
        $curso = $this->crearCursoIF6100();
        $creado = $this->crearGrupo01($curso, abierto: true);
        $grupo = Grupo::find($creado->id);

        $this->assertSame(2, $grupo->numero_ciclo);
        $this->assertSame(2026, $grupo->ano);
        $this->assertSame(1, $grupo->numero_grupo);
        $this->assertTrue($grupo->abierto);
        $this->assertIsBool($grupo->abierto); 

        $this->assertSame('IF6100', $grupo->curso->sigla);
        $this->assertSame(4, $grupo->curso->creditos);

        $this->assertDatabaseCount('grupos', 1);
    }

    public function test_cp02_un_curso_tiene_varios_grupos_y_se_distinguen_los_abiertos(): void
    {
        $curso = $this->crearCursoIF6100();

        foreach ([1 => true, 2 => true, 3 => false] as $numero => $abierto) {
            Grupo::create([
                'curso_id' => $curso->id,
                'numero_ciclo' => 2,
                'ano' => 2026,
                'numero_grupo' => $numero,
                'abierto' => $abierto,
            ]);
        }

        $this->assertCount(3, $curso->grupos);

        $abiertos = $curso->gruposAbiertos();
        $this->assertCount(2, $abiertos);
        $this->assertEqualsCanonicalizing([1, 2], $abiertos->pluck('numero_grupo')->all());

        $this->assertSame(3, Grupo::where('curso_id', $curso->id)->count());
    }

    public function test_cp03_un_grupo_acepta_dos_docentes(): void
    {
        $grupo = $this->crearGrupo01($this->crearCursoIF6100());
        $docente1001 = $this->crearDocente1001();
        $docente1002 = $this->crearDocente1002();

        $grupo->asignarDocente($docente1001);
        $grupo->asignarDocente($docente1002);

        $this->assertCount(2, $grupo->docentes);
        $this->assertEqualsCanonicalizing([1001, 1002], $grupo->docentes->pluck('cod_empleado')->all());

        $this->assertTrue($docente1001->grupos()->whereKey($grupo->id)->exists());

        $this->assertDatabaseCount('grupo_docente', 2);
    }

    public function test_cp04_un_grupo_rechaza_un_tercer_docente(): void
    {
        $grupo = $this->crearGrupo01($this->crearCursoIF6100());
        $grupo->asignarDocente($this->crearDocente1001());
        $grupo->asignarDocente($this->crearDocente1002());
        $docente1003 = $this->crearDocente1003();

        $this->assertThrows(fn () => $grupo->asignarDocente($docente1003), DomainException::class);

        $this->assertCount(2, $grupo->fresh()->docentes);
        $this->assertDatabaseMissing('grupo_docente', ['docente_id' => $docente1003->id]);
    }

    public function test_cp05_un_grupo_con_14_estudiantes_no_puede_abrirse(): void
    {
        $grupo = $this->crearGrupo01($this->crearCursoIF6100(), abierto: false);
        $docente1001 = $this->crearDocente1001();
        $grupo->asignarDocente($docente1001);
        $this->matricularEstudiantesFactory($grupo, 14, $docente1001);

        $this->assertFalse($grupo->puedeAbrirse());

        $this->assertThrows(fn () => $grupo->abrir(), DomainException::class);

        $this->assertFalse($grupo->fresh()->abierto);
        $this->assertDatabaseHas('grupos', ['id' => $grupo->id, 'abierto' => false]);
    }

    public function test_cp06_un_grupo_con_15_estudiantes_y_un_docente_se_abre(): void
    {
        $grupo = $this->crearGrupo01($this->crearCursoIF6100(), abierto: false);
        $docente1001 = $this->crearDocente1001();
        $grupo->asignarDocente($docente1001);
        $this->matricularEstudiantesFactory($grupo, 15, $docente1001);

        $this->assertTrue($grupo->puedeAbrirse());

        $grupo->abrir();

        $this->assertTrue($grupo->fresh()->abierto);

        $this->assertDatabaseHas('grupos', ['id' => $grupo->id, 'abierto' => true]);
    }
}