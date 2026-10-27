<?php

namespace App\Services;

use App\Models\Asignacion;
use App\Models\Matricula;
use App\Models\Nota;
use App\Models\Periodo;
use Illuminate\Support\Collection;

class CalculoPromediosService
{
    public function resumirCalificaciones(array $calificaciones, int $cantidadPeriodos): array
    {
        $cantidadPeriodos = max(1, $cantidadPeriodos);
        $registradas = array_filter($calificaciones, fn ($valor) => $valor !== null && $valor !== '');
        $suma = array_sum($registradas);
        $promedio = $cantidadPeriodos === 1 && $registradas === []
            ? null
            : round($suma / $cantidadPeriodos, (int) config('academico.decimales_permitidos', 2));
        $estado = $promedio === null ? 'sin_notas' : ($promedio >= config('academico.nota_aprobacion', 51) ? 'aprobado' : 'reprobado');

        return [
            'promedio' => $promedio,
            'estado' => $estado,
            'notas_registradas' => count($registradas),
            'periodos_totales' => $cantidadPeriodos,
            'pendientes' => max(0, $cantidadPeriodos - count($registradas)),
        ];
    }

    public function calcularPromedioMatricula(Matricula $matricula, Asignacion $asignacion, ?Collection $periodos = null): array
    {
        $periodos ??= Periodo::where('gestion', $asignacion->gestion)->orderBy('numero')->get();
        $notas = Nota::where('matricula_id', $matricula->id)->where('asignacion_id', $asignacion->id)
            ->whereIn('periodo_id', $periodos->pluck('id'))->get()->keyBy('periodo_id');
        $detalle = $periodos->mapWithKeys(function ($periodo) use ($notas): array {
            $nota = $notas->get($periodo->id);

            return [$periodo->id => [
                'periodo' => $periodo->nombre, 'numero' => $periodo->numero,
                'nota' => $nota ? (float) $nota->nota : null, 'observacion' => $nota?->observacion,
            ]];
        })->all();

        return $this->resumirCalificaciones(
            array_column($detalle, 'nota'),
            max((int) config('academico.cantidad_periodos_completo', 3), $periodos->count()),
        ) + ['notas_detalle' => $detalle];
    }

    public function obtenerConsolidadoAsignacion(Asignacion $asignacion): Collection
    {
        $periodos = Periodo::where('gestion', $asignacion->gestion)->orderBy('numero')->get();
        $matriculas = Matricula::with([
            'estudiante',
            'notas' => fn ($query) => $query->where('asignacion_id', $asignacion->id)->whereIn('periodo_id', $periodos->pluck('id')),
        ])->where('curso_id', $asignacion->curso_id)->where('gestion', $asignacion->gestion)->get()
            ->sortBy(fn ($matricula) => $matricula->estudiante->nombre_completo);

        return $matriculas->map(function ($matricula) use ($periodos): array {
            $porPeriodo = $matricula->notas->keyBy('periodo_id');
            $detalle = $periodos->mapWithKeys(function ($periodo) use ($porPeriodo): array {
                $nota = $porPeriodo->get($periodo->id);

                return [$periodo->id => ['periodo' => $periodo->nombre, 'numero' => $periodo->numero, 'nota' => $nota ? (float) $nota->nota : null, 'observacion' => $nota?->observacion]];
            })->all();

            return [
                'matricula' => $matricula,
                'estudiante' => $matricula->estudiante,
                'calculo' => $this->resumirCalificaciones(array_column($detalle, 'nota'), max((int) config('academico.cantidad_periodos_completo', 3), $periodos->count())) + ['notas_detalle' => $detalle],
            ];
        });
    }
}
