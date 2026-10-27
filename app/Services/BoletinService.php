<?php

namespace App\Services;

use App\Models\Asignacion;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\Periodo;
use Illuminate\Support\Collection;

class BoletinService
{
    public function __construct(private CalculoPromediosService $calculos) {}

    public function individual(Estudiante $estudiante, int $gestion, ?int $periodoId = null): array
    {
        $matricula = Matricula::with('curso')->where('estudiante_id', $estudiante->id)->where('gestion', $gestion)->first();
        $periodos = $this->periodos($gestion, $periodoId);
        $asignaciones = $matricula ? $this->asignaciones($matricula->curso) : collect();
        $notas = $matricula ? $matricula->notas()->whereIn('asignacion_id', $asignaciones->pluck('id'))->whereIn('periodo_id', $periodos->pluck('id'))->get() : collect();
        $filas = $this->filas($asignaciones, $periodos, $notas, $periodoId === null);

        return [
            'estudiante' => $estudiante, 'gestion' => $gestion, 'periodoId' => $periodoId,
            'matricula' => $matricula, 'periodos' => $periodos, 'filas' => $filas,
            'pendientes' => $filas->sum('pendientes'),
            'institucion' => config('academico.institucion'),
        ];
    }

    public function centralizador(Curso $curso, ?int $periodoId = null): array
    {
        $periodos = $this->periodos($curso->gestion, $periodoId);
        $asignaciones = $this->asignaciones($curso);
        $matriculas = $curso->matriculas()->with([
            'estudiante',
            'notas' => fn ($query) => $query->whereIn('asignacion_id', $asignaciones->pluck('id'))->whereIn('periodo_id', $periodos->pluck('id')),
        ])->get()->sortBy(fn ($matricula) => $matricula->estudiante->nombre_completo)->values();
        $estudiantes = $matriculas->map(fn ($matricula) => [
            'matricula' => $matricula, 'estudiante' => $matricula->estudiante,
            'filas' => $this->filas($asignaciones, $periodos, $matricula->notas, $periodoId === null),
        ]);
        $campos = $asignaciones->groupBy(fn ($asignacion) => $asignacion->materia->campo ?: 'Áreas de conocimiento')
            ->map(fn ($group, $campo) => ['nombre' => $campo, 'columnas' => $group->count() * max(1, $periodos->count())]);

        return compact('curso', 'periodos', 'asignaciones', 'matriculas', 'estudiantes', 'campos', 'periodoId') + [
            'datosPendientes' => $matriculas->filter(fn ($matricula) => $matricula->estudiante->datosFaltantes() !== [])->map(fn ($matricula) => $matricula->estudiante)->values(),
            'institucion' => config('academico.institucion'),
            'todosPeriodos' => Periodo::where('gestion', $curso->gestion)->orderBy('numero')->get(),
        ];
    }

    private function periodos(int $gestion, ?int $periodoId): Collection
    {
        return Periodo::where('gestion', $gestion)->when($periodoId, fn ($query) => $query->whereKey($periodoId))->orderBy('numero')->get();
    }

    private function asignaciones(Curso $curso): Collection
    {
        $ordenCampos = array_flip(collect(CentralizadorPdfService::AREAS)->pluck('campo')->unique()->values()->all());

        return Asignacion::with(['materia', 'docente'])->where('curso_id', $curso->id)->where('gestion', $curso->gestion)->get()
            ->sortBy(fn ($asignacion) => ($ordenCampos[$asignacion->materia->campo] ?? 9).sprintf('%05d', $asignacion->materia->orden).$asignacion->materia->nombre)->values();
    }

    private function filas(Collection $asignaciones, Collection $periodos, Collection $notas, bool $anual): Collection
    {
        $notasPorCelda = $notas->keyBy(fn ($nota) => $nota->asignacion_id.':'.$nota->periodo_id);

        return $asignaciones->map(function ($asignacion) use ($periodos, $notasPorCelda, $anual): array {
            $valores = $periodos->mapWithKeys(fn ($periodo) => [$periodo->id => $notasPorCelda->get($asignacion->id.':'.$periodo->id)]);
            $resumen = $this->calculos->resumirCalificaciones(
                $valores->map(fn ($nota) => $nota ? (float) $nota->nota : null)->all(),
                $anual ? max((int) config('academico.cantidad_periodos_completo', 3), $periodos->count()) : 1,
            );

            return [
                'materia' => $asignacion->materia, 'docente' => $asignacion->docente,
                'notas' => $valores,
            ] + $resumen;
        });
    }
}
