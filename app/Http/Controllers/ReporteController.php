<?php

namespace App\Http\Controllers;

use App\Models\Asignacion;
use App\Models\Curso;
use App\Models\Periodo;
use App\Services\CalculoPromediosService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteController extends Controller
{
    public function __construct(
        protected CalculoPromediosService $calculoService
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $gestion = $request->input('gestion', date('Y'));

        $query = Asignacion::with(['curso', 'materia', 'docente'])
            ->where('gestion', $gestion);

        if ($user->esDocente()) {
            $query->where('docente_id', $user->id);
        }

        $asignaciones = $query->get();
        $gestiones = Curso::select('gestion')->distinct()->orderBy('gestion', 'desc')->pluck('gestion');

        return view('reportes.index', compact('asignaciones', 'gestion', 'gestiones'));
    }

    public function consolidado(Request $request): View
    {
        $data = $this->prepararDatosReporte($request);

        return view('reportes.consolidado', $data);
    }

    public function imprimir(Request $request): View
    {
        $data = $this->prepararDatosReporte($request);

        return view('reportes.imprimir', $data);
    }

    public function exportar(Request $request): StreamedResponse
    {
        $data = $this->prepararDatosReporte($request);
        $asignacion = $data['asignacion'];
        $periodos = $data['periodos'];
        $consolidado = $data['consolidado'];

        $filename = sprintf(
            'reporte_notas_%s_%s_%s_%s.csv',
            str_replace(' ', '_', $asignacion->curso->nombre),
            $asignacion->curso->paralelo,
            $asignacion->materia->sigla,
            $asignacion->gestion
        );

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($asignacion, $periodos, $consolidado) {
            $file = fopen('php://output', 'w');

            // BOM para compatibilidad con Microsoft Excel UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Encabezados institucionales
            fputcsv($file, ['UNIDAD EDUCATIVA TÉCNICO HUMANÍSTICO MAESTRO PITÁGORAS II'], ';');
            fputcsv($file, ['REPORTE CONSOLIDADO DE CALIFICACIONES'], ';');
            fputcsv($file, ['Curso:', $asignacion->curso->nombre . ' "' . $asignacion->curso->paralelo . '"', 'Gestión:', $asignacion->gestion], ';');
            fputcsv($file, ['Materia:', $asignacion->materia->nombre, 'Docente:', $asignacion->docente->nombre], ';');
            fputcsv($file, ['Fecha de emisión:', date('d/m/Y H:i:s')], ';');
            fputcsv($file, [], ';');

            // Encabezados de columnas
            $headerCols = ['Nº', 'Código', 'Estudiante'];
            foreach ($periodos as $p) {
                $headerCols[] = $p->nombre;
            }
            $headerCols[] = 'Promedio';
            $headerCols[] = 'Estado';
            fputcsv($file, $headerCols, ';');

            // Filas de datos protegidas contra Formula Injection
            $i = 1;
            foreach ($consolidado as $fila) {
                $estudiante = $fila['estudiante'];
                $calculo = $fila['calculo'];

                // Sanitizar texto que empiece con carácteres de fórmula (+, -, =, @)
                $codigo = $this->sanitizarCsv($estudiante->codigo);
                $nombreCompleto = $this->sanitizarCsv($estudiante->apellidos . ', ' . $estudiante->nombres);

                $row = [$i++, $codigo, $nombreCompleto];

                foreach ($periodos as $p) {
                    $notaDetalle = $calculo['notas_detalle'][$p->id] ?? null;
                    $row[] = ($notaDetalle && $notaDetalle['nota'] !== null)
                        ? number_format($notaDetalle['nota'], 2, ',', '')
                        : 'Sin nota';
                }

                $row[] = $calculo['promedio'] !== null
                    ? number_format($calculo['promedio'], 2, ',', '')
                    : 'Sin promedio';

                $row[] = ucfirst($calculo['estado']);

                fputcsv($file, $row, ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function prepararDatosReporte(Request $request): array
    {
        $user = $request->user();
        $asignacionId = $request->input('asignacion_id');

        $query = Asignacion::with(['curso', 'materia', 'docente']);
        if ($user->esDocente()) {
            $query->where('docente_id', $user->id);
        }

        $asignacion = $query->findOrFail($asignacionId);

        $periodos = Periodo::where('gestion', $asignacion->gestion)
            ->orderBy('numero', 'asc')
            ->get();

        $consolidado = $this->calculoService->obtenerConsolidadoAsignacion($asignacion);

        return [
            'asignacion' => $asignacion,
            'periodos' => $periodos,
            'consolidado' => $consolidado,
        ];
    }

    /**
     * Previene Formula Injection en hojas de cálculo prependiendo una comilla simple.
     */
    protected function sanitizarCsv(string $valor): string
    {
        if (in_array(substr($valor, 0, 1), ['=', '+', '-', '@'], true)) {
            return "'" . $valor;
        }

        return $valor;
    }
}
