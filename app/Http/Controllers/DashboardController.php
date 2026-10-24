<?php

namespace App\Http\Controllers;

use App\Models\Asignacion;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Materia;
use App\Models\Periodo;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $gestion = (int) date('Y');
        $periodos = Periodo::where('gestion', $gestion)->orderBy('numero')->get();

        if ($user->esAdministrador()) {
            $metricas = [
                'estudiantes' => Estudiante::count(),
                'docentes' => Usuario::where('rol', 'docente')->count(),
                'cursos' => Curso::count(),
                'materias' => Materia::count(),
            ];

            return view('dashboard.index', [
                'user' => $user,
                'metricas' => $metricas,
                'periodos' => $periodos,
                'cursosResumen' => Curso::withCount(['matriculas', 'asignaciones'])->where('gestion', $gestion)->orderBy('nombre')->orderBy('paralelo')->limit(6)->get(),
            ]);
        }

        // Si es docente, mostrar únicamente sus asignaciones
        $asignaciones = Asignacion::with(['curso', 'materia'])
            ->where('docente_id', $user->id)
            ->orderBy('gestion', 'desc')
            ->get();

        $metricas = [
            'total_asignaciones' => $asignaciones->count(),
            'cursos_asignados' => $asignaciones->pluck('curso_id')->unique()->count(),
            'materias_asignadas' => $asignaciones->pluck('materia_id')->unique()->count(),
        ];

        return view('dashboard.index', [
            'user' => $user,
            'metricas' => $metricas,
            'asignaciones' => $asignaciones,
            'periodos' => $periodos,
        ]);
    }
}
