<?php

namespace App\Services;

use App\Models\Asignacion;
use App\Models\Matricula;
use App\Models\Nota;
use App\Models\Periodo;
use Illuminate\Support\Collection;

class CalculoPromediosService
{
    /**
     * Calcula el promedio de una matrícula para una asignación específica a lo largo de los periodos.
     *
     * @param Matricula $matricula
     * @param Asignacion $asignacion
     * @param Collection|null $periodos
     * @return array{
     *     promedio: float|null,
     *     estado: string, // 'completo', 'incompleto', 'sin_notas'
     *     notas_registradas: int,
     *     periodos_totales: int,
     *     notas_detalle: array
     * }
     */
    public function calcularPromedioMatricula(Matricula $matricula, Asignacion $asignacion, ?Collection $periodos = null): array
    {
        if (!$periodos) {
            $periodos = Periodo::where('gestion', $asignacion->gestion)
                ->orderBy('numero', 'asc')
                ->get();
        }

        $totalPeriodos = $periodos->count();
        $cantidadEsperada = config('academico.cantidad_periodos_completo', $totalPeriodos);

        $notas = Nota::where('matricula_id', $matricula->id)
            ->where('asignacion_id', $asignacion->id)
            ->whereIn('periodo_id', $periodos->pluck('id'))
            ->get()
            ->keyBy('periodo_id');

        $notasDetalle = [];
        $sumaNotas = 0;
        $conteoRegistradas = 0;

        foreach ($periodos as $periodo) {
            $notaModel = $notas->get($periodo->id);
            if ($notaModel && $notaModel->nota !== null) {
                $valor = (float) $notaModel->nota;
                $sumaNotas += $valor;
                $conteoRegistradas++;
                $notasDetalle[$periodo->id] = [
                    'periodo' => $periodo->nombre,
                    'numero' => $periodo->numero,
                    'nota' => $valor,
                    'observacion' => $notaModel->observacion,
                ];
            } else {
                $notasDetalle[$periodo->id] = [
                    'periodo' => $periodo->nombre,
                    'numero' => $periodo->numero,
                    'nota' => null,
                    'observacion' => null,
                ];
            }
        }

        if ($conteoRegistradas === 0) {
            return [
                'promedio' => null,
                'estado' => 'sin_notas',
                'notas_registradas' => 0,
                'periodos_totales' => $totalPeriodos,
                'notas_detalle' => $notasDetalle,
            ];
        }

        // Cálculo aritmético simple sobre periodos registrados
        $promedioCalculado = $sumaNotas / $conteoRegistradas;
        $decimales = config('academico.decimales_permitidos', 2);
        $promedioFinal = round($promedioCalculado, $decimales);

        $estado = ($conteoRegistradas >= $cantidadEsperada) ? 'completo' : 'incompleto';

        return [
            'promedio' => $promedioFinal,
            'estado' => $estado,
            'notas_registradas' => $conteoRegistradas,
            'periodos_totales' => $totalPeriodos,
            'notas_detalle' => $notasDetalle,
        ];
    }

    /**
     * Calcula consolidado de promedios para todos los estudiantes matriculados en una asignación.
     */
    public function obtenerConsolidadoAsignacion(Asignacion $asignacion): Collection
    {
        $periodos = Periodo::where('gestion', $asignacion->gestion)
            ->orderBy('numero', 'asc')
            ->get();

        $matriculas = Matricula::with('estudiante')
            ->where('curso_id', $asignacion->curso_id)
            ->where('gestion', $asignacion->gestion)
            ->get()
            ->sortBy(fn($m) => $m->estudiante->apellidos . ' ' . $m->estudiante->nombres);

        return $matriculas->map(function ($matricula) use ($asignacion, $periodos) {
            $calculo = $this->calcularPromedioMatricula($matricula, $asignacion, $periodos);
            return [
                'matricula' => $matricula,
                'estudiante' => $matricula->estudiante,
                'calculo' => $calculo,
            ];
        });
    }
}
