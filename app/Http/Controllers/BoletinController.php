<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConsultarBoletinRequest;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Periodo;
use App\Services\BoletinService;
use App\Services\DocumentoPdfService;
use Illuminate\Http\Response;
use Illuminate\View\View;

class BoletinController extends Controller
{
    public function __construct(private BoletinService $boletines, private DocumentoPdfService $documentos) {}

    public function index(ConsultarBoletinRequest $request): View
    {
        $gestion = (int) $request->input('gestion', date('Y'));
        $buscar = trim((string) $request->input('buscar', ''));
        $estudiantes = Estudiante::with(['matriculas' => fn ($query) => $query->where('gestion', $gestion)->with('curso')])
            ->when($buscar !== '', fn ($query) => $query->where(function ($query) use ($buscar): void {
                $query->where('codigo', 'like', '%'.$buscar.'%')->orWhere('nombres', 'like', '%'.$buscar.'%')->orWhere('apellidos', 'like', '%'.$buscar.'%');
            }))
            ->when($request->filled('curso_id'), fn ($query) => $query->whereHas('matriculas', fn ($query) => $query->where('gestion', $gestion)->where('curso_id', $request->integer('curso_id'))))
            ->orderBy('apellidos')->orderBy('nombres')->paginate(15)->withQueryString();
        $gestiones = Curso::select('gestion')->distinct()->orderByDesc('gestion')->pluck('gestion');
        $periodos = Periodo::where('gestion', $gestion)->orderBy('numero')->get();
        $cursos = Curso::where('gestion', $gestion)->orderBy('nombre')->orderBy('paralelo')->get();

        return view('boletines.index', compact('gestion', 'buscar', 'estudiantes', 'gestiones', 'periodos', 'cursos'));
    }

    public function show(ConsultarBoletinRequest $request, Estudiante $estudiante): View
    {
        return view('boletines.show', $this->boletines->individual($estudiante, (int) $request->input('gestion', date('Y')), $request->filled('periodo_id') ? $request->integer('periodo_id') : null));
    }

    public function imprimir(ConsultarBoletinRequest $request, Estudiante $estudiante): View
    {
        return view('boletines.print', $this->datosIndividuales($request, $estudiante) + ['imprimir' => true]);
    }

    public function pdf(ConsultarBoletinRequest $request, Estudiante $estudiante): Response
    {
        $datos = $this->datosIndividuales($request, $estudiante);
        abort_unless($datos['matricula'] && $datos['filas']->isNotEmpty() && $datos['periodos']->isNotEmpty(), 422, 'Este estudiante necesita matrícula, materias y periodos para generar su boletín.');
        $contenido = $this->documentos->generar('boletines.print', $datos);
        $codigo = preg_replace('/[^A-Za-z0-9-]/', '-', $estudiante->codigo);

        return response($contenido, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="boletin_'.$codigo.'_'.$datos['gestion'].'.pdf"', 'Cache-Control' => 'private, no-store']);
    }

    public function centralizador(ConsultarBoletinRequest $request, Curso $curso): View
    {
        $this->validarGestionCurso($request, $curso);

        return view('boletines.centralizador', $this->boletines->centralizador($curso, $request->filled('periodo_id') ? $request->integer('periodo_id') : null));
    }

    public function centralizadorPdf(ConsultarBoletinRequest $request, Curso $curso): Response
    {
        $this->validarGestionCurso($request, $curso);
        $datos = $this->boletines->centralizador($curso, $request->filled('periodo_id') ? $request->integer('periodo_id') : null);
        abort_unless($datos['asignaciones']->isNotEmpty() && $datos['periodos']->isNotEmpty(), 422, 'Configura las materias y periodos del curso antes de generar el PDF.');
        abort_if($datos['datosPendientes']->isNotEmpty(), 422, 'Faltan datos de estudiantes. Completa sus fichas antes de descargar el centralizador.');
        $contenido = $this->documentos->generar('boletines.centralizador-pdf', $datos, true);

        return response($contenido, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="centralizador_'.$curso->id.'_'.$curso->gestion.'.pdf"', 'Cache-Control' => 'private, no-store']);
    }

    private function datosIndividuales(ConsultarBoletinRequest $request, Estudiante $estudiante): array
    {
        return $this->boletines->individual($estudiante, (int) $request->input('gestion', date('Y')), $request->filled('periodo_id') ? $request->integer('periodo_id') : null);
    }

    private function validarGestionCurso(ConsultarBoletinRequest $request, Curso $curso): void
    {
        abort_if($request->filled('gestion') && $request->integer('gestion') !== $curso->gestion, 422, 'El curso no pertenece a la gestión elegida.');
        if ($request->filled('periodo_id')) {
            abort_unless(Periodo::whereKey($request->integer('periodo_id'))->where('gestion', $curso->gestion)->exists(), 422, 'El trimestre no pertenece a la gestión del curso.');
        }
    }
}
