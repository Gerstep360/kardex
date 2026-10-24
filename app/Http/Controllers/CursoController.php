<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarCursoRequest;
use App\Models\Curso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CursoController extends Controller
{
    public function index(Request $request): View
    {
        $gestion = $request->input('gestion');

        $cursos = Curso::withCount(['matriculas', 'asignaciones'])
            ->when($gestion, fn($q) => $q->where('gestion', $gestion))
            ->orderBy('gestion', 'desc')
            ->orderBy('nombre', 'asc')
            ->orderBy('paralelo', 'asc')
            ->paginate(15)
            ->withQueryString();

        $gestiones = Curso::select('gestion')->distinct()->orderBy('gestion', 'desc')->pluck('gestion');

        return view('cursos.index', compact('cursos', 'gestion', 'gestiones'));
    }

    public function create(): View
    {
        return view('cursos.create', ['curso' => new Curso(['gestion' => date('Y')])]);
    }

    public function store(GuardarCursoRequest $request): RedirectResponse
    {
        Curso::create($request->validated());

        return redirect()->route('cursos.index')->with('success', 'Curso registrado satisfactoriamente.');
    }

    public function show(Curso $curso): View
    {
        $curso->load([
            'matriculas.estudiante' => fn($q) => $q->orderBy('apellidos')->orderBy('nombres'),
            'asignaciones.materia',
            'asignaciones.docente',
        ]);

        return view('cursos.show', compact('curso'));
    }

    public function edit(Curso $curso): View
    {
        return view('cursos.edit', compact('curso'));
    }

    public function update(GuardarCursoRequest $request, Curso $curso): RedirectResponse
    {
        $curso->update($request->validated());

        return redirect()->route('cursos.index')->with('success', 'Curso actualizado satisfactoriamente.');
    }

    public function destroy(Curso $curso): RedirectResponse
    {
        if ($curso->matriculas()->exists() || $curso->asignaciones()->exists()) {
            return back()->withErrors([
                'error' => 'No se puede eliminar el curso porque tiene matrículas o materias asignadas.',
            ]);
        }

        $curso->delete();

        return redirect()->route('cursos.index')->with('success', 'Curso eliminado satisfactoriamente.');
    }
}
