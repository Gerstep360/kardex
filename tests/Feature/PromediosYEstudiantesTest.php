<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\Nota;
use App\Models\Periodo;
use App\Models\Usuario;
use App\Services\CalculoPromediosService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PromediosYEstudiantesTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('notasAnuales')]
    public function test_el_promedio_anual_cuenta_los_tres_trimestres_y_muestra_el_resultado(array $notas, float $esperado, string $resultado): void
    {
        [$estudiante, $curso, $matricula, $asignacion, $periodos] = $this->cursoConEstudiante();
        foreach ($notas as $indice => $valor) {
            if ($valor !== null) {
                Nota::factory()->create(['matricula_id' => $matricula->id, 'asignacion_id' => $asignacion->id, 'periodo_id' => $periodos[$indice]->id, 'nota' => $valor]);
            }
        }
        $this->actingAs(Usuario::factory()->create());
        $this->get(route('promedios.index', ['gestion' => 2026, 'asignacion_id' => $asignacion->id]))
            ->assertOk()->assertSee(number_format($esperado, 2))->assertSee($resultado)->assertSee('Resultado')->assertDontSee('Incompleto');
        $this->get(route('boletines.show', ['estudiante' => $estudiante->id, 'gestion' => 2026]))
            ->assertOk()->assertSee(number_format($esperado, 2))->assertSee($resultado)->assertSee('Promedio anual');
        $calculo = app(CalculoPromediosService::class)->calcularPromedioMatricula($matricula, $asignacion);
        $this->assertSame($esperado, $calculo['promedio']);
        $this->assertSame(strtolower($resultado), $calculo['estado']);
        $this->assertDatabaseCount('notas', count(array_filter($notas, fn ($nota) => $nota !== null)));
    }

    public static function notasAnuales(): array
    {
        return [
            'solo primer trimestre' => [[90, null, null], 30.0, 'Reprobado'],
            'dos trimestres aprobados' => [[90, 90, null], 60.0, 'Aprobado'],
            'tres trimestres' => [[85, 90, 89], 88.0, 'Aprobado'],
            'sin notas' => [[null, null, null], 0.0, 'Reprobado'],
            'limite de aprobacion' => [[51, 51, 51], 51.0, 'Aprobado'],
            'debajo del limite' => [[50.99, 50.99, 50.99], 50.99, 'Reprobado'],
        ];
    }

    public function test_el_trimestre_seleccionado_usa_su_propia_nota_y_resultado(): void
    {
        [$estudiante, $curso, $matricula, $asignacion, $periodos] = $this->cursoConEstudiante();
        Nota::factory()->create(['matricula_id' => $matricula->id, 'asignacion_id' => $asignacion->id, 'periodo_id' => $periodos[0]->id, 'nota' => 90]);

        $this->actingAs(Usuario::factory()->create())->get(route('boletines.show', ['estudiante' => $estudiante->id, 'gestion' => 2026, 'periodo_id' => $periodos[0]->id]))
            ->assertOk()->assertSee('90.00')->assertSee('Aprobado')->assertSee('Nota del trimestre')->assertDontSee('30.00');
        $this->get(route('boletines.show', ['estudiante' => $estudiante->id, 'gestion' => 2026, 'periodo_id' => $periodos[1]->id]))
            ->assertOk()->assertSee('Sin nota')->assertDontSee('Reprobado')->assertDontSee('Aprobado');
    }

    public function test_no_guarda_una_ficha_con_datos_personales_faltantes(): void
    {
        $this->actingAs(Usuario::factory()->create())->post(route('estudiantes.store'), [
            'codigo' => 'RUDE-123', 'nombres' => 'María', 'apellidos' => 'Flores Pérez', 'estado' => 'activo',
        ])->assertSessionHasErrors(['apellido_paterno', 'apellido_materno', 'genero', 'fecha_nacimiento', 'lugar_nacimiento', 'documento_identidad']);

        $this->assertDatabaseCount('estudiantes', 0);
    }

    public function test_la_ficha_muestra_todos_los_datos_sin_ocultarlos_como_opcionales(): void
    {
        $this->actingAs(Usuario::factory()->create())->get(route('estudiantes.create'))
            ->assertOk()->assertSee('Código RUDE')->assertSee('Apellido paterno')->assertSee('Apellido materno')
            ->assertSee('Fecha de nacimiento')->assertSee('Lugar de nacimiento')->assertSee('Número de C.I.')
            ->assertDontSee('opcionales')->assertDontSee('optional-fields');
    }

    public function test_guarda_el_rude_y_los_datos_completos_del_estudiante(): void
    {
        $datos = $this->datosPersonales();

        $this->actingAs(Usuario::factory()->create())->post(route('estudiantes.store'), $datos)->assertRedirect(route('estudiantes.index'));
        $this->assertDatabaseHas('estudiantes', [
            'codigo' => '5198005420121062A', 'apellidos' => 'Flores Pérez', 'nombres' => 'María Elena',
            'genero' => 'F', 'documento_identidad' => '12345678', 'lugar_nacimiento' => 'SC',
        ]);
        $this->assertSame('2009-05-20', Estudiante::where('codigo', '5198005420121062A')->firstOrFail()->fecha_nacimiento->format('Y-m-d'));
    }

    public function test_una_fecha_de_nacimiento_futura_no_se_guarda(): void
    {
        $datos = $this->datosPersonales();
        $datos['fecha_nacimiento'] = now()->addYear()->format('Y-m-d');

        $this->actingAs(Usuario::factory()->create())->post(route('estudiantes.store'), $datos)
            ->assertSessionHasErrors(['fecha_nacimiento' => 'La fecha de nacimiento no puede ser posterior a hoy.']);
        $this->assertDatabaseCount('estudiantes', 0);
    }

    public function test_completar_una_ficha_devuelve_al_centralizador_del_mismo_curso(): void
    {
        [$estudiante, $curso] = $this->cursoConEstudiante();
        $estudiante->update(['lugar_nacimiento' => null]);
        $datos = $this->datosPersonales();
        $datos['codigo'] = $estudiante->codigo;
        $datos['volver_curso'] = $curso->id;

        $this->actingAs(Usuario::factory()->create())->put(route('estudiantes.update', $estudiante), $datos)
            ->assertRedirect(route('boletines.centralizador', $curso));
        $this->assertSame([], $estudiante->fresh()->datosFaltantes());
    }

    public function test_no_borra_los_datos_guardados_si_la_edicion_esta_incompleta(): void
    {
        $estudiante = Estudiante::factory()->create();
        $anterior = $estudiante->documento_identidad;
        $datos = $this->datosPersonales();
        $datos['codigo'] = $estudiante->codigo;
        $datos['documento_identidad'] = '';

        $this->actingAs(Usuario::factory()->create())->put(route('estudiantes.update', $estudiante), $datos)
            ->assertSessionHasErrors('documento_identidad');
        $this->assertSame($anterior, $estudiante->fresh()->documento_identidad);
    }

    public function test_el_centralizador_indica_los_datos_faltantes_antes_de_descargar(): void
    {
        [$estudiante, $curso] = $this->cursoConEstudiante();
        $estudiante->update(['documento_identidad' => null]);

        $this->actingAs(Usuario::factory()->create())->get(route('boletines.centralizador', ['curso' => $curso->id, 'gestion' => 2026]))
            ->assertOk()->assertSee('Faltan datos de un estudiante')->assertSee('Número de C.I.')->assertSee('Completar ficha')
            ->assertDontSee('Descargar centralizador PDF');
        $this->get(route('boletines.centralizador.pdf', ['curso' => $curso->id, 'gestion' => 2026]))->assertUnprocessable();
    }

    public function test_secretaria_ve_que_faltan_datos_sin_recibir_accesos_de_edicion(): void
    {
        [$estudiante, $curso] = $this->cursoConEstudiante();
        $estudiante->update(['genero' => null]);

        $this->actingAs(Usuario::factory()->secretaria()->create())->get(route('boletines.centralizador', ['curso' => $curso->id, 'gestion' => 2026]))
            ->assertOk()->assertSee('Pide a administración')->assertDontSee('Completar ficha');
    }

    public function test_la_descarga_para_excel_usa_el_mismo_promedio_y_resultado(): void
    {
        [$estudiante, $curso, $matricula, $asignacion, $periodos] = $this->cursoConEstudiante();
        Nota::factory()->create(['matricula_id' => $matricula->id, 'asignacion_id' => $asignacion->id, 'periodo_id' => $periodos[0]->id, 'nota' => 90]);

        $respuesta = $this->actingAs(Usuario::factory()->create())->get(route('reportes.exportar', ['asignacion_id' => $asignacion->id]));
        $respuesta->assertOk();
        $contenido = $respuesta->streamedContent();
        $this->assertStringContainsString('Resultado', $contenido);
        $this->assertStringContainsString('30,00;Reprobado', $contenido);
    }

    private function datosPersonales(): array
    {
        return ['codigo' => '5198005420121062a', 'nombres' => 'María Elena', 'apellido_paterno' => 'Flores', 'apellido_materno' => 'Pérez', 'genero' => 'F', 'fecha_nacimiento' => '2009-05-20', 'lugar_nacimiento' => 'SC', 'documento_identidad' => '12345678', 'estado' => 'activo'];
    }

    private function cursoConEstudiante(): array
    {
        $estudiante = Estudiante::factory()->create();
        $curso = Curso::factory()->create();
        $matricula = Matricula::factory()->create(['estudiante_id' => $estudiante->id, 'curso_id' => $curso->id]);
        $asignacion = Asignacion::factory()->create(['curso_id' => $curso->id]);
        $periodos = [];
        foreach ([1, 2, 3] as $numero) {
            $periodos[] = Periodo::factory()->create(['numero' => $numero, 'nombre' => 'Trimestre '.$numero]);
        }

        return [$estudiante, $curso, $matricula, $asignacion, $periodos];
    }
}
