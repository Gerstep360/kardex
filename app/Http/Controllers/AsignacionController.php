<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarAsignacionRequest;
use App\Models\Asignacion;
use App\Models\Curso;
use App\Models\Materia;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AsignacionController extends Controller
{
    public function index(Request $request): View
    {
        $gestion = $request->input('gestion', date('Y'));
        $docenteId = $request->input('docente_id');

        $asignaciones = Asignacion::with(['docente', 'materia', 'curso'])
            ->when($gestion, fn($q) => $q->where('gestion', $gestion))
            ->when($docenteId, fn($q) => $q->where('docente_id', $docenteId))
            ->orderBy('gestion', 'desc')
            ->paginate(20)
            ->withQueryString();

        $docentes = Usuario::where('rol', 'docente')->where('activo', true)->orderBy('nombre')->get();
        $gestiones = Curso::select('gestion')->distinct()->orderBy('gestion', 'desc')->pluck('gestion');

        return view('asignaciones.index', compact('asignaciones', 'gestion', 'docenteId', 'docentes', 'gestiones'));
    }

    public function create(Request $request): View
    {
        $gestion = $request->input('gestion', date('Y'));
        $docentes = Usuario::where('rol', 'docente')->where('activo', true)->orderBy('nombre')->get();
        $materias = Materia::orderBy('nombre')->get();
        $cursos = Curso::where('gestion', $gestion)->orderBy('nombre')->get();

        return view('asignaciones.create', [
            'asignacion' => new Asignacion(['gestion' => $gestion]),
            'docentes' => $docentes,
            'materias' => $materias,
            'cursos' => $cursos,
            'gestion' => $gestion,
        ]);
    }

    public function store(GuardarAsignacionRequest $request): RedirectResponse
    {
        Asignacion::create($request->validated());

        return redirect()->route('asignaciones.index', ['gestion' => $request->input('gestion')])
            ->with('success', 'Asignación docente registrada satisfactoriamente.');
    }

    public function edit(Asignacion $asignacion): View
    {
        $docentes = Usuario::where('rol', 'docente')->orderBy('nombre')->get();
        $materias = Materia::orderBy('nombre')->get();
        $cursos = Curso::where('gestion', $asignacion->gestion)->orderBy('nombre')->get();

        return view('asignaciones.edit', compact('asignacion', 'docentes', 'materias', 'cursos'));
    }

    public function update(GuardarAsignacionRequest $request, Asignacion $asignacion): RedirectResponse
    {
        $asignacion->update($request->validated());

        return redirect()->route('asignaciones.index', ['gestion' => $asignacion->gestion])
            ->with('success', 'Asignación docente actualizada satisfactoriamente.');
    }

    public function destroy(Asignacion $asignacion): RedirectResponse
    {
        if ($asignacion->notas()->exists()) {
            return back()->withErrors([
                'error' => 'No se puede eliminar la asignación porque ya contiene notas registradas.',
            ]);
        }

        $asignacion->delete();

        return redirect()->route('asignaciones.index')->with('success', 'Asignación eliminada satisfactoriamente.');
    }
}
