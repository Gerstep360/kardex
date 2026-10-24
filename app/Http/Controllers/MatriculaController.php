<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarMatriculaRequest;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Matricula;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MatriculaController extends Controller
{
    public function index(Request $request): View
    {
        $gestion = $request->input('gestion', date('Y'));
        $cursoId = $request->input('curso_id');

        $matriculas = Matricula::with(['estudiante', 'curso'])
            ->when($gestion, fn($q) => $q->where('gestion', $gestion))
            ->when($cursoId, fn($q) => $q->where('curso_id', $cursoId))
            ->orderBy('gestion', 'desc')
            ->paginate(20)
            ->withQueryString();

        $cursos = Curso::where('gestion', $gestion)->orderBy('nombre')->get();
        $gestiones = Curso::select('gestion')->distinct()->orderBy('gestion', 'desc')->pluck('gestion');

        return view('matriculas.index', compact('matriculas', 'gestion', 'cursoId', 'cursos', 'gestiones'));
    }

    public function create(Request $request): View
    {
        $gestion = $request->input('gestion', date('Y'));
        $estudiantes = Estudiante::where('estado', 'activo')->orderBy('apellidos')->get();
        $cursos = Curso::where('gestion', $gestion)->orderBy('nombre')->get();

        return view('matriculas.create', [
            'matricula' => new Matricula(['gestion' => $gestion]),
            'estudiantes' => $estudiantes,
            'cursos' => $cursos,
            'gestion' => $gestion,
        ]);
    }

    public function store(GuardarMatriculaRequest $request): RedirectResponse
    {
        Matricula::create($request->validated());

        return redirect()->route('matriculas.index', ['gestion' => $request->input('gestion')])
            ->with('success', 'Estudiante matriculado satisfactoriamente.');
    }

    public function edit(Matricula $matricula): View
    {
        $estudiantes = Estudiante::orderBy('apellidos')->get();
        $cursos = Curso::where('gestion', $matricula->gestion)->orderBy('nombre')->get();

        return view('matriculas.edit', compact('matricula', 'estudiantes', 'cursos'));
    }

    public function update(GuardarMatriculaRequest $request, Matricula $matricula): RedirectResponse
    {
        $matricula->update($request->validated());

        return redirect()->route('matriculas.index', ['gestion' => $matricula->gestion])
            ->with('success', 'Matrícula actualizada satisfactoriamente.');
    }

    public function destroy(Matricula $matricula): RedirectResponse
    {
        if ($matricula->notas()->exists()) {
            return back()->withErrors([
                'error' => 'No se puede eliminar la matrícula porque tiene calificaciones registradas.',
            ]);
        }

        $matricula->delete();

        return redirect()->route('matriculas.index')->with('success', 'Matrícula eliminada satisfactoriamente.');
    }
}
