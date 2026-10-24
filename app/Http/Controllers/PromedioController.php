<?php

namespace App\Http\Controllers;

use App\Models\Asignacion;
use App\Models\Curso;
use App\Services\CalculoPromediosService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PromedioController extends Controller
{
    public function __construct(
        protected CalculoPromediosService $calculoService
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $gestion = $request->input('gestion', date('Y'));
        $asignacionId = $request->input('asignacion_id');

        // Consultar asignaciones disponibles según el rol
        $queryAsignaciones = Asignacion::with(['curso', 'materia', 'docente'])
            ->where('gestion', $gestion);

        if ($user->esDocente()) {
            $queryAsignaciones->where('docente_id', $user->id);
        }

        $asignaciones = $queryAsignaciones->get();
        $gestiones = Curso::select('gestion')->distinct()->orderBy('gestion', 'desc')->pluck('gestion');

        $asignacionSeleccionada = null;
        $consolidado = null;

        if ($asignacionId) {
            $asignacionSeleccionada = $asignaciones->firstWhere('id', $asignacionId);
            if ($asignacionSeleccionada) {
                $consolidado = $this->calculoService->obtenerConsolidadoAsignacion($asignacionSeleccionada);
            }
        } elseif ($asignaciones->isNotEmpty()) {
            $asignacionSeleccionada = $asignaciones->first();
            $consolidado = $this->calculoService->obtenerConsolidadoAsignacion($asignacionSeleccionada);
        }

        return view('promedios.index', compact('asignaciones', 'asignacionSeleccionada', 'consolidado', 'gestion', 'gestiones'));
    }
}
