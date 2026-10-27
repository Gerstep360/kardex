<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Importacion;
use App\Models\Materia;
use App\Models\Matricula;
use App\Models\Nota;
use App\Models\Periodo;
use App\Models\Usuario;
use App\Services\CentralizadorPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use Tests\TestCase;

class ImportacionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_lee_el_pdf_real_y_conserva_letras_del_rude_y_celdas_vacias(): void
    {
        $datos = app(CentralizadorPdfService::class)->leer($this->pdfReferencia());

        $this->assertCount(33, $datos['estudiantes']);
        $this->assertCount(13, $datos['materias']);
        $this->assertSame(2026, $datos['cabecera']['gestion']);
        $this->assertSame('SEXTO', $datos['cabecera']['curso']);
        $this->assertSame('B', $datos['cabecera']['paralelo']);
        $this->assertSame(1, $datos['cabecera']['periodo_numero']);
        $this->assertSame('5198005420121062A', $datos['estudiantes'][4]['codigo']);
        $this->assertSame([89.0, 89.0, 84.0, 100.0, 100.0, 100.0, 92.0, 95.0, 90.0, 95.0, 92.0, 92.0, 100.0], $datos['estudiantes'][0]['notas']);
        $this->assertNull($datos['estudiantes'][19]['notas'][7]);
        $this->assertSame(79.0, $datos['estudiantes'][19]['notas'][8]);
    }

    public function test_cargar_el_pdf_solo_prepara_la_revision_sin_importar_notas(): void
    {
        Storage::fake('local');
        $admin = Usuario::factory()->create();
        $this->actingAs($admin)->post(route('importaciones.store'), ['archivo' => $this->archivoReferencia()])->assertRedirect();
        $importacion = Importacion::firstOrFail();

        $this->assertDatabaseCount('notas', 0);
        $this->assertDatabaseCount('estudiantes', 0);
        $this->assertDatabaseCount('cursos', 0);
        Storage::disk('local')->assertExists($importacion->archivo);
        $this->get(route('importaciones.show', $importacion))->assertOk()->assertSee('Revisa antes de guardar')->assertSee('Crear materia: Matemática');
        $this->get(route('importaciones.archivo', $importacion))->assertOk()->assertDownload();
    }

    public function test_importa_el_pdf_completo_una_sola_vez_y_genera_los_documentos(): void
    {
        Storage::fake('local');
        $admin = Usuario::factory()->create();
        $docente = Usuario::factory()->docente()->create();
        $importacion = $this->importacionReferencia($admin);
        $opciones = $this->opciones($docente);

        $this->actingAs($admin)->post(route('importaciones.confirmar', $importacion), $opciones)->assertRedirect(route('importaciones.show', $importacion));

        $this->assertDatabaseCount('estudiantes', 33);
        $this->assertDatabaseCount('matriculas', 33);
        $this->assertDatabaseCount('materias', 13);
        $this->assertDatabaseCount('asignaciones', 13);
        $this->assertDatabaseCount('notas', 428);
        $this->assertDatabaseCount('periodos', 3);
        $this->assertDatabaseHas('estudiantes', ['codigo' => '5198005420121062A']);
        $this->assertDatabaseHas('importaciones', ['id' => $importacion->id, 'estado' => 'completada']);
        $this->get(route('importaciones.show', $importacion))->assertOk()->assertSee('Las notas ya están en Kardex.');
        $this->post(route('importaciones.confirmar', $importacion), $opciones)->assertRedirect();
        $this->assertDatabaseCount('notas', 428);

        $this->post(route('importaciones.store'), ['archivo' => $this->archivoReferencia()])->assertRedirect(route('importaciones.show', $importacion));
        $this->assertDatabaseCount('importaciones', 1);

        $curso = Curso::firstOrFail();
        $periodo = Periodo::where('numero', 1)->firstOrFail();
        $this->get(route('boletines.centralizador', ['curso' => $curso->id, 'gestion' => 2026, 'periodo_id' => $periodo->id]))->assertOk()->assertSee('Datos del estudiante');
        $pdf = $this->get(route('boletines.centralizador.pdf', ['curso' => $curso->id, 'gestion' => 2026, 'periodo_id' => $periodo->id]));
        $pdf->assertOk()->assertHeader('Content-Type', 'application/pdf');
        if (getenv('KARDEX_PDF_PREVIEW')) {
            file_put_contents(storage_path('app/qa-centralizador.pdf'), $pdf->getContent());
        }
        $documento = (new Parser)->parseContent($pdf->getContent());
        $this->assertCount(2, $documento->getPages());
        $this->assertSame(1008.0, (float) $documento->getPages()[0]->getDetails()['MediaBox'][2]);
        $this->assertSame(612.0, (float) $documento->getPages()[0]->getDetails()['MediaBox'][3]);
        $this->assertStringContainsString('51980054201413862', $documento->getText());
        $this->assertStringContainsString('33', $documento->getPages()[1]->getText());
        $anual = $this->get(route('boletines.centralizador.pdf', ['curso' => $curso->id, 'gestion' => 2026]));
        $anual->assertOk()->assertHeader('Content-Type', 'application/pdf');
        if (getenv('KARDEX_PDF_PREVIEW')) {
            file_put_contents(storage_path('app/qa-centralizador-anual.pdf'), $anual->getContent());
        }
        $textoAnual = (new Parser)->parseContent($anual->getContent())->getText();
        $this->assertStringContainsString('T2', $textoAnual);
        $this->assertStringContainsString('T3', $textoAnual);

        $estudiante = Estudiante::where('codigo', '51980054201413862')->firstOrFail();
        $boletin = $this->get(route('boletines.pdf', ['estudiante' => $estudiante->id, 'gestion' => 2026]));
        $boletin->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $texto = (new Parser)->parseContent($boletin->getContent())->getText();
        $this->assertCount(1, (new Parser)->parseContent($boletin->getContent())->getPages());
        $this->assertStringContainsString('Sin nota', $texto);
        $this->assertStringContainsString('89.00', $texto);
        if (getenv('KARDEX_PDF_PREVIEW')) {
            file_put_contents(storage_path('app/qa-centralizador.pdf'), $pdf->getContent());
            file_put_contents(storage_path('app/qa-boletin.pdf'), $boletin->getContent());
        }
    }

    public function test_no_se_crean_registros_si_no_se_autoriza_crear_los_faltantes(): void
    {
        $admin = Usuario::factory()->create();
        $docente = Usuario::factory()->docente()->create();
        $importacion = $this->importacionReferencia($admin);
        $opciones = $this->opciones($docente);
        $opciones['crear_faltantes'] = 0;

        $this->actingAs($admin)->post(route('importaciones.confirmar', $importacion), $opciones)->assertSessionHasErrors('importacion');
        $this->assertDatabaseCount('notas', 0);
        $this->assertDatabaseCount('cursos', 0);
        $this->assertDatabaseCount('estudiantes', 0);
        $this->assertSame('pendiente', $importacion->fresh()->estado);
    }

    public function test_conserva_por_defecto_la_nota_que_ya_estaba_en_kardex(): void
    {
        [$admin, $docente, $importacion, $nota, $materia] = $this->escenarioConNota();
        $opciones = $this->opciones($docente);
        $opciones['materias'][0] = (string) $materia->id;

        $this->actingAs($admin)->post(route('importaciones.confirmar', $importacion), $opciones)->assertRedirect();
        $this->assertSame('66.00', $nota->fresh()->nota);
        $this->assertSame('Corrección del docente', $nota->fresh()->observacion);
        $this->assertDatabaseCount('notas', 428);
        $this->assertDatabaseCount('cursos', 1);
    }

    public function test_actualiza_una_nota_solo_si_se_elige_reemplazar(): void
    {
        [$admin, $docente, $importacion, $nota, $materia] = $this->escenarioConNota();
        $opciones = $this->opciones($docente);
        $opciones['materias'][0] = (string) $materia->id;
        $opciones['politica'] = 'reemplazar';

        $this->actingAs($admin)->post(route('importaciones.confirmar', $importacion), $opciones)->assertRedirect();
        $this->assertSame('89.00', $nota->fresh()->nota);
        $this->assertSame('Corrección del docente', $nota->fresh()->observacion);
        $this->assertSame(1, $importacion->fresh()->resumen['notas_actualizadas']);
    }

    public function test_una_celda_vacia_nunca_borra_una_nota_existente(): void
    {
        $admin = Usuario::factory()->create();
        $docente = Usuario::factory()->docente()->create();
        $importacion = $this->importacionReferencia($admin);
        $fila = $importacion->datos['estudiantes'][19];
        $curso = Curso::factory()->create(['nombre' => 'Sexto de Secundaria']);
        $estudiante = Estudiante::factory()->create(collect($fila)->only(['codigo', 'nombres', 'apellidos'])->all());
        $matricula = Matricula::factory()->create(['estudiante_id' => $estudiante->id, 'curso_id' => $curso->id]);
        $periodo = Periodo::factory()->create();
        $materia = Materia::factory()->create(['nombre' => 'Técnica Especializada']);
        $asignacion = Asignacion::factory()->create(['docente_id' => $docente->id, 'materia_id' => $materia->id, 'curso_id' => $curso->id]);
        $nota = Nota::factory()->create(['matricula_id' => $matricula->id, 'asignacion_id' => $asignacion->id, 'periodo_id' => $periodo->id, 'nota' => 73]);
        $opciones = $this->opciones($docente);
        $opciones['materias'][7] = (string) $materia->id;
        $opciones['politica'] = 'reemplazar';

        $this->actingAs($admin)->post(route('importaciones.confirmar', $importacion), $opciones)->assertRedirect();
        $this->assertSame('73.00', $nota->fresh()->nota);
        $this->assertDatabaseCount('notas', 429);
    }

    public function test_no_cambia_de_curso_a_un_estudiante_ya_matriculado(): void
    {
        $admin = Usuario::factory()->create();
        $docente = Usuario::factory()->docente()->create();
        $importacion = $this->importacionReferencia($admin);
        $fila = $importacion->datos['estudiantes'][0];
        $estudiante = Estudiante::factory()->create(collect($fila)->only(['codigo', 'nombres', 'apellidos'])->all());
        $matricula = Matricula::factory()->create(['estudiante_id' => $estudiante->id]);

        $this->actingAs($admin)->get(route('importaciones.show', $importacion))->assertOk()->assertSee('ya tiene matrícula en otro curso');
        $this->post(route('importaciones.confirmar', $importacion), $this->opciones($docente))->assertSessionHasErrors('importacion');
        $this->assertDatabaseCount('notas', 0);
        $this->assertDatabaseCount('estudiantes', 1);
        $this->assertSame($matricula->curso_id, $matricula->fresh()->curso_id);
    }

    public function test_no_relaciona_dos_columnas_con_la_misma_materia_y_revierte_la_transaccion(): void
    {
        $admin = Usuario::factory()->create();
        $docente = Usuario::factory()->docente()->create();
        $importacion = $this->importacionReferencia($admin);
        $materia = Materia::factory()->create();
        $opciones = $this->opciones($docente);
        $opciones['materias'][0] = (string) $materia->id;
        $opciones['materias'][1] = (string) $materia->id;

        $this->actingAs($admin)->post(route('importaciones.confirmar', $importacion), $opciones)->assertSessionHasErrors('importacion');
        $this->assertDatabaseCount('cursos', 0);
        $this->assertDatabaseCount('asignaciones', 0);
        $this->assertDatabaseCount('notas', 0);
        $this->assertNull($materia->fresh()->campo);
    }

    public function test_rechaza_docentes_inactivos_sin_guardar_notas(): void
    {
        $admin = Usuario::factory()->create();
        $docente = Usuario::factory()->docente()->create(['activo' => false]);
        $importacion = $this->importacionReferencia($admin);

        $this->actingAs($admin)->post(route('importaciones.confirmar', $importacion), $this->opciones($docente))->assertSessionHasErrors('docentes.0');
        $this->assertDatabaseCount('notas', 0);
        $this->assertDatabaseCount('cursos', 0);
    }

    public function test_secretaria_y_docente_no_pueden_confirmar_ni_descargar_el_original(): void
    {
        $admin = Usuario::factory()->create();
        $importacion = $this->importacionReferencia($admin);
        foreach (['secretaria', 'docente'] as $rol) {
            $usuario = Usuario::factory()->create(['rol' => $rol]);
            $this->actingAs($usuario)->get(route('importaciones.show', $importacion))->assertForbidden();
            $this->post(route('importaciones.confirmar', $importacion), [])->assertForbidden();
            $this->get(route('importaciones.archivo', $importacion))->assertForbidden();
        }
        $this->assertDatabaseCount('notas', 0);
    }

    public function test_rechaza_archivos_que_no_sean_pdf_o_superen_el_limite(): void
    {
        Storage::fake('local');
        $admin = Usuario::factory()->create();

        $this->actingAs($admin)->post(route('importaciones.store'), ['archivo' => UploadedFile::fake()->create('datos.txt', 1, 'text/plain')])->assertSessionHasErrors('archivo');
        $this->post(route('importaciones.store'), ['archivo' => UploadedFile::fake()->create('grande.pdf', 10241, 'application/pdf')])->assertSessionHasErrors('archivo');
        $this->assertDatabaseCount('importaciones', 0);
        Storage::disk('local')->assertDirectoryEmpty('centralizadores');
    }

    public function test_un_nombre_distinto_para_el_mismo_rude_requiere_revision(): void
    {
        $admin = Usuario::factory()->create();
        $docente = Usuario::factory()->docente()->create();
        $importacion = $this->importacionReferencia($admin);
        Estudiante::factory()->create(['codigo' => $importacion->datos['estudiantes'][0]['codigo'], 'nombres' => 'Otra persona']);

        $this->actingAs($admin)->get(route('importaciones.show', $importacion))->assertOk()->assertSee('el nombre del PDF no coincide');
        $this->post(route('importaciones.confirmar', $importacion), $this->opciones($docente))->assertSessionHasErrors('importacion');
        $this->assertDatabaseCount('notas', 0);
    }

    public function test_pide_los_datos_personales_que_no_aparecen_en_el_pdf(): void
    {
        $admin = Usuario::factory()->create();
        $docente = Usuario::factory()->docente()->create();
        $importacion = $this->importacionReferencia($admin);
        $opciones = $this->opciones($docente);
        unset($opciones['estudiantes']);

        $this->actingAs($admin)->get(route('importaciones.show', $importacion))->assertOk()
            ->assertSee('Completa los datos que no aparecen en el PDF')
            ->assertSee('name="estudiantes[19][lugar_nacimiento]"', false);
        $this->post(route('importaciones.confirmar', $importacion), $opciones)
            ->assertSessionHasErrors(['estudiantes.19.lugar_nacimiento', 'estudiantes.32.lugar_nacimiento']);
        $this->assertDatabaseCount('estudiantes', 0);
        $this->assertDatabaseCount('notas', 0);
    }

    public function test_no_importa_sin_confirmacion_explicita(): void
    {
        $admin = Usuario::factory()->create();
        $docente = Usuario::factory()->docente()->create();
        $importacion = $this->importacionReferencia($admin);
        $opciones = $this->opciones($docente);
        $opciones['confirmar'] = 0;

        $this->actingAs($admin)->post(route('importaciones.confirmar', $importacion), $opciones)->assertSessionHasErrors('confirmar');
        $this->assertDatabaseCount('notas', 0);
        $this->assertDatabaseCount('estudiantes', 0);
    }

    public function test_un_pdf_danado_muestra_un_mensaje_sin_guardar_archivos(): void
    {
        Storage::fake('local');
        $admin = Usuario::factory()->create();
        $archivo = UploadedFile::fake()->createWithContent('danado.pdf', "%PDF-1.4\nDocumento incompleto\n%%EOF");

        $this->actingAs($admin)->post(route('importaciones.store'), ['archivo' => $archivo])->assertSessionHasErrors('archivo');
        $this->assertDatabaseCount('importaciones', 0);
        $this->assertDatabaseCount('notas', 0);
        Storage::disk('local')->assertDirectoryEmpty('centralizadores');
    }

    private function escenarioConNota(): array
    {
        $admin = Usuario::factory()->create();
        $docente = Usuario::factory()->docente()->create();
        $importacion = $this->importacionReferencia($admin);
        $fila = $importacion->datos['estudiantes'][0];
        $curso = Curso::factory()->create(['nombre' => '6.º de Secundaria']);
        $estudiante = Estudiante::factory()->create(collect($fila)->only(['codigo', 'nombres', 'apellidos'])->all());
        $matricula = Matricula::factory()->create(['estudiante_id' => $estudiante->id, 'curso_id' => $curso->id]);
        $periodo = Periodo::factory()->create();
        $materia = Materia::factory()->create(['nombre' => 'Lenguaje y Literatura']);
        $asignacion = Asignacion::factory()->create(['docente_id' => $docente->id, 'curso_id' => $curso->id, 'materia_id' => $materia->id]);
        $nota = Nota::factory()->create(['matricula_id' => $matricula->id, 'asignacion_id' => $asignacion->id, 'periodo_id' => $periodo->id, 'nota' => 66, 'observacion' => 'Corrección del docente']);

        return [$admin, $docente, $importacion, $nota, $materia];
    }

    private function importacionReferencia(Usuario $usuario): Importacion
    {
        return Importacion::factory()->create(['usuario_id' => $usuario->id, 'sha256' => hash_file('sha256', $this->pdfReferencia()), 'datos' => app(CentralizadorPdfService::class)->leer($this->pdfReferencia())]);
    }

    private function opciones(Usuario $docente): array
    {
        return ['confirmar' => 1, 'crear_faltantes' => 1, 'politica' => 'conservar', 'materias' => array_fill(0, 13, 'nueva'), 'docentes' => array_fill(0, 13, $docente->id), 'estudiantes' => [19 => ['lugar_nacimiento' => 'SC'], 32 => ['lugar_nacimiento' => 'SC']]];
    }

    private function pdfReferencia(): string
    {
        return base_path('docs/boletin_centralizador_51980097_13_Sexto_B_2026.pdf');
    }

    private function archivoReferencia(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('centralizador.pdf', file_get_contents($this->pdfReferencia()));
    }
}
