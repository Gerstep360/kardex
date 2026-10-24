<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarMateriaRequest;
use App\Models\Materia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MateriaController extends Controller
{
    public function index(Request $request): View
    {
        $busqueda = $request->input('buscar');

        $materias = Materia::withCount('asignaciones')
            ->when($busqueda, function ($query, $buscar) {
                return $query->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('sigla', 'like', "%{$buscar}%");
            })
            ->orderBy('nombre', 'asc')
            ->paginate(15)
            ->withQueryString();

        return view('materias.index', compact('materias', 'busqueda'));
    }

    public function create(): View
    {
        return view('materias.create', ['materia' => new Materia()]);
    }

    public function store(GuardarMateriaRequest $request): RedirectResponse
    {
        Materia::create($request->validated());

        return redirect()->route('materias.index')->with('success', 'Materia registrada satisfactoriamente.');
    }

    public function show(Materia $materia): View
    {
        $materia->load(['asignaciones.curso', 'asignaciones.docente']);
        return view('materias.show', compact('materia'));
    }

    public function edit(Materia $materia): View
    {
        return view('materias.edit', compact('materia'));
    }

    public function update(GuardarMateriaRequest $request, Materia $materia): RedirectResponse
    {
        $materia->update($request->validated());

        return redirect()->route('materias.index')->with('success', 'Materia actualizada satisfactoriamente.');
    }

    public function destroy(Materia $materia): RedirectResponse
    {
        if ($materia->asignaciones()->exists()) {
            return back()->withErrors([
                'error' => 'No se puede eliminar la materia porque tiene asignaciones docentes vinculadas.',
            ]);
        }

        $materia->delete();

        return redirect()->route('materias.index')->with('success', 'Materia eliminada satisfactoriamente.');
    }
}
