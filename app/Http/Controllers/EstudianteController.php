<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarEstudianteRequest;
use App\Models\Estudiante;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EstudianteController extends Controller
{
    public function index(Request $request): View
    {
        $busqueda = $request->input('buscar');
        $gestion = $request->input('gestion', date('Y'));

        $estudiantes = Estudiante::with(['matriculas' => function ($q) use ($gestion) {
            $q->where('gestion', $gestion)->with('curso');
        }])
            ->when($busqueda, function ($query, $buscar) {
                return $query->where('codigo', 'like', "%{$buscar}%")
                    ->orWhere('nombres', 'like', "%{$buscar}%")
                    ->orWhere('apellidos', 'like', "%{$buscar}%");
            })
            ->orderBy('apellidos', 'asc')
            ->orderBy('nombres', 'asc')
            ->paginate(15)
            ->withQueryString();

        return view('estudiantes.index', compact('estudiantes', 'busqueda', 'gestion'));
    }

    public function create(): View
    {
        return view('estudiantes.create', ['estudiante' => new Estudiante(['estado' => 'activo'])]);
    }

    public function store(GuardarEstudianteRequest $request): RedirectResponse
    {
        Estudiante::create($request->safe()->except('volver_curso'));

        return redirect()->route('estudiantes.index')->with('success', 'Estudiante registrado satisfactoriamente.');
    }

    public function show(Estudiante $estudiante): View
    {
        $estudiante->load(['matriculas.curso', 'matriculas.notas.asignacion.materia', 'matriculas.notas.periodo']);

        return view('estudiantes.show', compact('estudiante'));
    }

    public function edit(Estudiante $estudiante): View
    {
        return view('estudiantes.edit', compact('estudiante'));
    }

    public function update(GuardarEstudianteRequest $request, Estudiante $estudiante): RedirectResponse
    {
        $estudiante->update($request->safe()->except('volver_curso'));
        if ($request->filled('volver_curso') && $estudiante->matriculas()->where('curso_id', $request->integer('volver_curso'))->exists()) {
            return redirect()->route('boletines.centralizador', $request->integer('volver_curso'))
                ->with('success', 'Datos del estudiante guardados. Ya puedes continuar con el centralizador.');
        }

        return redirect()->route('estudiantes.index')->with('success', 'Datos del estudiante actualizados.');
    }

    public function destroy(Estudiante $estudiante): RedirectResponse
    {
        if ($estudiante->matriculas()->exists()) {
            return back()->withErrors([
                'error' => 'No se puede eliminar el estudiante porque tiene historial de matrículas. En su lugar cambie el estado a inactivo.',
            ]);
        }

        $estudiante->delete();

        return redirect()->route('estudiantes.index')->with('success', 'Estudiante eliminado satisfactoriamente.');
    }
}
