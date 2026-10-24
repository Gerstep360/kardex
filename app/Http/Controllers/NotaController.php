<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarPlanillaNotasRequest;
use App\Models\Asignacion;
use App\Models\Matricula;
use App\Models\Nota;
use App\Models\Periodo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class NotaController extends Controller
{
    /**
     * M06: Listado / selector de asignaciones docentes y periodos.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $gestion = $request->input('gestion', date('Y'));

        $asignaciones = Asignacion::with(['curso', 'materia'])
            ->where('docente_id', $user->id)
            ->when($gestion, fn($q) => $q->where('gestion', $gestion))
            ->get();

        $gestiones = Asignacion::where('docente_id', $user->id)
            ->select('gestion')
            ->distinct()
            ->orderBy('gestion', 'desc')
            ->pluck('gestion');

        return view('notas.index', compact('asignaciones', 'gestion', 'gestiones'));
    }

    /**
     * M06: Planilla de notas para ingresar o editar calificaciones.
     */
    public function planilla(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $asignacionId = $request->input('asignacion_id');
        $periodoId = $request->input('periodo_id');

        if (!$asignacionId) {
            return redirect()->route('notas.index')->withErrors(['error' => 'Debe seleccionar una asignación.']);
        }

        // 1. Obtener la asignación perteneciente al docente autenticado
        $asignacion = Asignacion::with(['curso', 'materia'])
            ->where('id', $asignacionId)
            ->where('docente_id', $user->id)
            ->first();

        if (!$asignacion) {
            abort(403, 'No tiene autorización para calificar en esta asignación.');
        }

        // Obtener los periodos correspondientes a la gestión de la asignación
        $periodos = Periodo::where('gestion', $asignacion->gestion)
            ->orderBy('numero', 'asc')
            ->get();

        if ($periodos->isEmpty()) {
            return redirect()->route('notas.index')->withErrors([
                'error' => "No existen periodos configurados para la gestión {$asignacion->gestion}.",
            ]);
        }

        $periodoActual = $periodoId ? $periodos->firstWhere('id', $periodoId) : $periodos->first();
        if (!$periodoActual) {
            $periodoActual = $periodos->first();
        }

        // 2. Obtener la nómina de estudiantes matriculados en ese curso y gestión
        $matriculas = Matricula::with('estudiante')
            ->where('curso_id', $asignacion->curso_id)
            ->where('gestion', $asignacion->gestion)
            ->get()
            ->sortBy(fn($m) => $m->estudiante->apellidos . ' ' . $m->estudiante->nombres);

        // 3. Obtener las notas ya registradas para este periodo y asignación
        $notasExistentes = Nota::where('asignacion_id', $asignacion->id)
            ->where('periodo_id', $periodoActual->id)
            ->whereIn('matricula_id', $matriculas->pluck('id'))
            ->get()
            ->keyBy('matricula_id');

        return view('notas.planilla', compact('asignacion', 'periodos', 'periodoActual', 'matriculas', 'notasExistentes'));
    }

    /**
     * M06: Guardar o actualizar la planilla de notas dentro de una transacción.
     */
    public function guardarPlanilla(GuardarPlanillaNotasRequest $request): RedirectResponse
    {
        $user = $request->user();
        $asignacionId = $request->input('asignacion_id');
        $periodoId = $request->input('periodo_id');

        $asignacion = Asignacion::where('id', $asignacionId)
            ->where('docente_id', $user->id)
            ->firstOrFail();

        $periodo = Periodo::where('id', $periodoId)
            ->where('gestion', $asignacion->gestion)
            ->firstOrFail();

        $matriculasIdsValidas = Matricula::where('curso_id', $asignacion->curso_id)
            ->where('gestion', $asignacion->gestion)
            ->pluck('id')
            ->all();

        $notasInput = $request->input('notas', []);
        $observacionesInput = $request->input('observaciones', []);

        DB::transaction(function () use ($asignacion, $periodo, $matriculasIdsValidas, $notasInput, $observacionesInput) {
            foreach ($matriculasIdsValidas as $matriculaId) {
                $valorNota = isset($notasInput[$matriculaId]) && trim((string)$notasInput[$matriculaId]) !== ''
                    ? (float)$notasInput[$matriculaId]
                    : null;

                $observacion = isset($observacionesInput[$matriculaId]) ? trim((string)$observacionesInput[$matriculaId]) : null;

                $notaExistente = Nota::where('matricula_id', $matriculaId)
                    ->where('asignacion_id', $asignacion->id)
                    ->where('periodo_id', $periodo->id)
                    ->first();

                if ($valorNota !== null) {
                    if ($notaExistente) {
                        $notaExistente->update([
                            'nota' => $valorNota,
                            'observacion' => $observacion,
                        ]);
                    } else {
                        Nota::create([
                            'matricula_id' => $matriculaId,
                            'asignacion_id' => $asignacion->id,
                            'periodo_id' => $periodo->id,
                            'nota' => $valorNota,
                            'observacion' => $observacion,
                        ]);
                    }
                } elseif ($notaExistente && empty($observacion)) {
                    // Si ya existía nota pero llegó en blanco, según regla 512: no borrar accidentalmente salvo explícito
                    // o conservar sin cambios si se dejó en blanco.
                }
            }
        });

        return redirect()->route('notas.planilla', [
            'asignacion_id' => $asignacion->id,
            'periodo_id' => $periodo->id,
        ])->with('success', 'Planilla de calificaciones guardada satisfactoriamente.');
    }
}
