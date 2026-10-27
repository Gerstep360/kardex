<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\Nota;
use App\Models\Periodo;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Smalot\PdfParser\Parser;
use Tests\TestCase;

class BoletinesTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('rolesDeConsulta')]
    public function test_los_roles_autorizados_consultan_boletines(string $rol): void
    {
        $usuario = Usuario::factory()->create(['rol' => $rol]);
        $estudiante = Estudiante::factory()->create(['codigo' => '51980000123456789']);

        $this->actingAs($usuario)->get(route('boletines.index', ['buscar' => $estudiante->codigo, 'gestion' => 2026]))
            ->assertOk()->assertSee('Código RUDE')->assertSee($estudiante->codigo)->assertSee('Ver boletín');
    }

    public static function rolesDeConsulta(): array
    {
        return ['administrador' => ['administrador'], 'secretaria' => ['secretaria']];
    }

    public function test_las_visitas_sin_sesion_deben_iniciar_sesion(): void
    {
        $this->get(route('boletines.index'))->assertRedirect(route('login'));
    }

    public function test_el_docente_no_accede_a_boletines_de_todos_los_estudiantes(): void
    {
        $docente = Usuario::factory()->docente()->create();
        $estudiante = Estudiante::factory()->create();

        $this->actingAs($docente)->get(route('boletines.index'))->assertForbidden();
        $this->get(route('boletines.pdf', $estudiante))->assertForbidden();
    }

    public function test_secretaria_tiene_un_inicio_sin_edicion_y_no_puede_escribir_notas(): void
    {
        $secretaria = Usuario::factory()->secretaria()->create();

        $this->actingAs($secretaria)->get(route('dashboard'))->assertOk()->assertSee('Buscar y generar boletín')->assertDontSee('Administración')->assertDontSee('Cargar centralizador PDF');
        $this->post(route('notas.guardar'), [])->assertForbidden();
        $this->post(route('estudiantes.store'), [])->assertForbidden();
        $this->post(route('usuarios.store'), [])->assertForbidden();
        $this->get(route('importaciones.index'))->assertForbidden();
        $this->post(route('importaciones.store'), [])->assertForbidden();
        $this->assertDatabaseCount('notas', 0);
    }

    public function test_se_puede_crear_la_cuenta_de_secretaria(): void
    {
        $admin = Usuario::factory()->create();

        $this->actingAs($admin)->post(route('usuarios.store'), [
            'nombre' => 'Secretaría del colegio', 'usuario' => 'secretaria', 'password' => 'secretaria123', 'rol' => 'secretaria', 'activo' => 1,
        ])->assertRedirect(route('usuarios.index'));

        $this->assertDatabaseHas('usuarios', ['usuario' => 'secretaria', 'rol' => 'secretaria', 'activo' => true]);
    }

    public function test_una_cuenta_inactiva_no_consulta_los_documentos(): void
    {
        $usuario = Usuario::factory()->secretaria()->create(['activo' => false]);

        $this->actingAs($usuario)->get(route('boletines.index'))->assertRedirect(route('login'));
    }

    public function test_reune_materias_y_notas_sin_duplicar_ni_tomar_notas_de_otra_gestion(): void
    {
        [$estudiante, $curso, $periodo, $asignacion, $matricula] = $this->escenario();
        $periodoDos = Periodo::factory()->create(['numero' => 2, 'nombre' => 'Segundo Trimestre']);
        Periodo::factory()->create(['numero' => 3, 'nombre' => 'Tercer Trimestre']);
        Nota::factory()->create(['matricula_id' => $matricula->id, 'asignacion_id' => $asignacion->id, 'periodo_id' => $periodo->id, 'nota' => 85]);
        Nota::factory()->create(['matricula_id' => $matricula->id, 'asignacion_id' => $asignacion->id, 'periodo_id' => $periodoDos->id, 'nota' => 95]);
        $otroCurso = Curso::factory()->create(['gestion' => 2025]);
        Matricula::factory()->create(['estudiante_id' => $estudiante->id, 'curso_id' => $otroCurso->id, 'gestion' => 2025]);

        $this->actingAs(Usuario::factory()->secretaria()->create())->get(route('boletines.show', ['estudiante' => $estudiante->id, 'gestion' => 2026]))
            ->assertOk()->assertSee('85.00')->assertSee('95.00')->assertSee('60.00')->assertSee('Aprobado')->assertSee('Sin nota')
            ->assertViewHas('periodos', fn ($periodos) => $periodos->count() === 3)
            ->assertViewHas('pendientes', 1);

        $this->assertDatabaseCount('notas', 2);
    }

    public function test_el_trimestre_seleccionado_no_mezcla_los_otros_trimestres(): void
    {
        [$estudiante, $curso, $periodo, $asignacion, $matricula] = $this->escenario();
        $periodoDos = Periodo::factory()->create(['numero' => 2]);
        Nota::factory()->create(['matricula_id' => $matricula->id, 'asignacion_id' => $asignacion->id, 'periodo_id' => $periodo->id, 'nota' => 67]);
        Nota::factory()->create(['matricula_id' => $matricula->id, 'asignacion_id' => $asignacion->id, 'periodo_id' => $periodoDos->id, 'nota' => 98]);

        $this->actingAs(Usuario::factory()->create())->get(route('boletines.show', ['estudiante' => $estudiante->id, 'gestion' => 2026, 'periodo_id' => $periodo->id]))
            ->assertOk()->assertSee('67.00')->assertDontSee('98.00')->assertViewHas('periodos', fn ($periodos) => $periodos->count() === 1);
    }

    public function test_rechaza_trimestre_de_otra_gestion(): void
    {
        $estudiante = Estudiante::factory()->create();
        $periodo = Periodo::factory()->create(['gestion' => 2025]);

        $this->actingAs(Usuario::factory()->create())->get(route('boletines.show', ['estudiante' => $estudiante->id, 'gestion' => 2026, 'periodo_id' => $periodo->id]))
            ->assertSessionHasErrors('periodo_id');
    }

    public function test_muestra_falta_de_matricula_y_no_descarga_boletin_vacio(): void
    {
        $estudiante = Estudiante::factory()->create();

        $this->actingAs(Usuario::factory()->secretaria()->create())->get(route('boletines.show', ['estudiante' => $estudiante->id, 'gestion' => 2026]))
            ->assertOk()->assertSee('No tiene matrícula en esta gestión');
        $this->get(route('boletines.pdf', ['estudiante' => $estudiante->id, 'gestion' => 2026]))->assertUnprocessable();
    }

    public function test_el_boletin_pdf_es_un_documento_real_con_rude_y_notas(): void
    {
        [$estudiante, $curso, $periodo, $asignacion, $matricula] = $this->escenario();
        Nota::factory()->create(['matricula_id' => $matricula->id, 'asignacion_id' => $asignacion->id, 'periodo_id' => $periodo->id, 'nota' => 87]);

        $respuesta = $this->actingAs(Usuario::factory()->secretaria()->create())->get(route('boletines.pdf', ['estudiante' => $estudiante->id, 'gestion' => 2026]));
        $respuesta->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $respuesta->getContent());
        $texto = (new Parser)->parseContent($respuesta->getContent())->getText();
        $this->assertStringContainsString($estudiante->codigo, $texto);
        $this->assertStringContainsString('87.00', $texto);
    }

    public function test_el_centralizador_de_otro_anio_usa_los_periodos_del_curso(): void
    {
        $curso = Curso::factory()->create(['gestion' => 2025]);
        $periodo = Periodo::factory()->create(['gestion' => 2025]);

        $this->actingAs(Usuario::factory()->create())->get(route('boletines.centralizador', ['curso' => $curso->id, 'periodo_id' => $periodo->id]))
            ->assertOk()->assertSee('2025');
    }

    public function test_el_nombre_del_estudiante_se_escapa_en_el_boletin(): void
    {
        $estudiante = Estudiante::factory()->create(['nombres' => '<script>alert(1)</script>']);

        $this->actingAs(Usuario::factory()->create())->get(route('boletines.show', $estudiante))
            ->assertOk()->assertSee('<script>alert(1)</script>')->assertDontSee('<script>alert(1)</script>', false);
    }

    private function escenario(): array
    {
        $estudiante = Estudiante::factory()->create();
        $curso = Curso::factory()->create();
        $periodo = Periodo::factory()->create();
        $asignacion = Asignacion::factory()->create(['curso_id' => $curso->id]);
        $matricula = Matricula::factory()->create(['curso_id' => $curso->id, 'estudiante_id' => $estudiante->id]);

        return [$estudiante, $curso, $periodo, $asignacion, $matricula];
    }
}
