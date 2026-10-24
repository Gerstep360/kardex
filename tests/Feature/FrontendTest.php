<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Materia;
use App\Models\Matricula;
use App\Models\Periodo;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FrontendTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_is_available_to_guests(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Hola de nuevo.')->assertSee('Tu nombre de usuario');
    }

    #[DataProvider('administrativePages')]
    public function test_administrator_can_open_each_screen(string $route, ?string $modelClass = null, bool $queryParameter = false): void
    {
        $admin = $this->seedDemonstration('admin');
        $record = $modelClass ? $modelClass::query()->firstOrFail() : null;
        $parameters = $record ? ($queryParameter ? ['asignacion_id' => $record->id] : [$record]) : [];

        $this->actingAs($admin)->get(route($route, $parameters))->assertOk()->assertSee('kardex');
    }

    public static function administrativePages(): array
    {
        return [
            'inicio' => ['dashboard'],
            'estudiantes' => ['estudiantes.index'],
            'nuevo estudiante' => ['estudiantes.create'],
            'editar estudiante' => ['estudiantes.edit', Estudiante::class],
            'perfil estudiante' => ['estudiantes.show', Estudiante::class],
            'usuarios' => ['usuarios.index'],
            'nuevo usuario' => ['usuarios.create'],
            'editar usuario' => ['usuarios.edit', Usuario::class],
            'perfil usuario' => ['usuarios.show', Usuario::class],
            'cursos' => ['cursos.index'],
            'nuevo curso' => ['cursos.create'],
            'editar curso' => ['cursos.edit', Curso::class],
            'detalle curso' => ['cursos.show', Curso::class],
            'materias' => ['materias.index'],
            'nueva materia' => ['materias.create'],
            'editar materia' => ['materias.edit', Materia::class],
            'detalle materia' => ['materias.show', Materia::class],
            'matriculas' => ['matriculas.index'],
            'nueva matricula' => ['matriculas.create'],
            'editar matricula' => ['matriculas.edit', Matricula::class],
            'asignaciones' => ['asignaciones.index'],
            'nueva asignacion' => ['asignaciones.create'],
            'editar asignacion' => ['asignaciones.edit', Asignacion::class],
            'periodos' => ['periodos.index'],
            'nuevo periodo' => ['periodos.create'],
            'editar periodo' => ['periodos.edit', Periodo::class],
            'promedios' => ['promedios.index'],
            'reportes' => ['reportes.index'],
            'consolidado' => ['reportes.consolidado', Asignacion::class, true],
            'impresion' => ['reportes.imprimir', Asignacion::class, true],
            'respaldos' => ['respaldos.index'],
        ];
    }

    public function test_empty_dashboard_and_empty_student_results_are_useful(): void
    {
        $admin = Usuario::create(['nombre' => 'Dirección', 'usuario' => 'direccion', 'password' => 'test-password', 'rol' => 'administrador', 'activo' => true]);

        $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertSee('Empecemos por el primer curso.');
        $this->get(route('estudiantes.index', ['buscar' => 'inexistente']))->assertOk()->assertSee('Aquí empieza una nueva historia');
    }

    public function test_teacher_has_a_focused_workspace_and_cannot_access_administration(): void
    {
        $teacher = $this->seedDemonstration('docente1');

        $this->actingAs($teacher)->get(route('dashboard'))->assertOk()->assertSee('Mis materias')->assertDontSee('Nuevo estudiante')->assertDontSee('Administración');
        $this->get(route('notas.index'))->assertOk()->assertSee('Matemáticas');
        $this->get(route('estudiantes.index'))->assertForbidden();
    }

    public function test_teacher_can_save_and_reopen_the_grade_sheet(): void
    {
        $teacher = $this->seedDemonstration('docente1');
        $assignment = Asignacion::where('docente_id', $teacher->id)->firstOrFail();
        $enrollment = Matricula::where('curso_id', $assignment->curso_id)->firstOrFail();
        $period = Periodo::where('gestion', $assignment->gestion)->firstOrFail();

        $response = $this->actingAs($teacher)->post(route('notas.guardar'), [
            'asignacion_id' => $assignment->id,
            'periodo_id' => $period->id,
            'notas' => [$enrollment->id => '79.25'],
            'observaciones' => [$enrollment->id => 'Buen avance en prácticas.'],
        ]);

        $response->assertRedirect(route('notas.planilla', ['asignacion_id' => $assignment->id, 'periodo_id' => $period->id]));
        $this->assertDatabaseHas('notas', ['asignacion_id' => $assignment->id, 'matricula_id' => $enrollment->id, 'periodo_id' => $period->id, 'nota' => 79.25, 'observacion' => 'Buen avance en prácticas.']);
        $this->get($response->headers->get('Location'))->assertOk()->assertSee('79.25')->assertSee('Buen avance en prácticas.');
    }

    public function test_grade_validation_is_displayed_without_losing_input(): void
    {
        $teacher = $this->seedDemonstration('docente1');
        $assignment = Asignacion::where('docente_id', $teacher->id)->firstOrFail();
        $enrollment = Matricula::where('curso_id', $assignment->curso_id)->firstOrFail();
        $period = Periodo::where('gestion', $assignment->gestion)->firstOrFail();
        $sheetUrl = route('notas.planilla', ['asignacion_id' => $assignment->id, 'periodo_id' => $period->id]);

        $response = $this->actingAs($teacher)->from($sheetUrl)->post(route('notas.guardar'), [
            'asignacion_id' => $assignment->id, 'periodo_id' => $period->id,
            'notas' => [$enrollment->id => '101'],
        ]);

        $response->assertSessionHasErrors('notas.'.$enrollment->id);
        $sessionCookie = $response->getCookie(config('session.cookie'));
        $this->withCookie(config('session.cookie'), $sessionCookie->getValue())
            ->get($sheetUrl)->assertOk()->assertSee('La calificación máxima permitida es 100.')->assertSee('value="101"', false);
        $this->assertDatabaseMissing('notas', ['matricula_id' => $enrollment->id, 'nota' => 101]);
    }

    public function test_assignment_update_binds_and_updates_the_requested_assignment(): void
    {
        $admin = $this->seedDemonstration('admin');
        $assignment = Asignacion::firstOrFail();
        $replacement = Usuario::where('usuario', 'docente2')->firstOrFail();

        $this->actingAs($admin)->put(route('asignaciones.update', $assignment), [
            'docente_id' => $replacement->id, 'materia_id' => $assignment->materia_id,
            'curso_id' => $assignment->curso_id, 'gestion' => $assignment->gestion,
        ])->assertRedirect(route('asignaciones.index', ['gestion' => $assignment->gestion]));

        $this->assertDatabaseHas('asignaciones', ['id' => $assignment->id, 'docente_id' => $replacement->id]);
    }

    public function test_student_input_is_escaped_in_the_directory(): void
    {
        $admin = $this->seedDemonstration('admin');
        Estudiante::create(['codigo' => 'XSS-TEST', 'nombres' => '<script>alert(1)</script>', 'apellidos' => 'Prueba', 'estado' => 'activo']);

        $this->actingAs($admin)->get(route('estudiantes.index'))
            ->assertOk()->assertSee('<script>alert(1)</script>')->assertDontSee('<script>alert(1)</script>', false);
    }

    private function seedDemonstration(string $username): Usuario
    {
        $this->travelTo(Carbon::create(2026, 10, 17, 10));
        $this->seed(DatabaseSeeder::class);

        return Usuario::where('usuario', $username)->firstOrFail();
    }
}
