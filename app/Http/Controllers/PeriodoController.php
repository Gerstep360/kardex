<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarPeriodoRequest;
use App\Models\Periodo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PeriodoController extends Controller
{
    public function index(Request $request): View
    {
        $gestion = $request->input('gestion', date('Y'));

        $periodos = Periodo::withCount('notas')
            ->when($gestion, fn($q) => $q->where('gestion', $gestion))
            ->orderBy('gestion', 'desc')
            ->orderBy('numero', 'asc')
            ->paginate(15)
            ->withQueryString();

        $gestiones = Periodo::select('gestion')->distinct()->orderBy('gestion', 'desc')->pluck('gestion');

        return view('periodos.index', compact('periodos', 'gestion', 'gestiones'));
    }

    public function create(Request $request): View
    {
        $gestion = $request->input('gestion', date('Y'));
        return view('periodos.create', [
            'periodo' => new Periodo(['gestion' => $gestion, 'numero' => 1]),
            'gestion' => $gestion,
        ]);
    }

    public function store(GuardarPeriodoRequest $request): RedirectResponse
    {
        Periodo::create($request->validated());

        return redirect()->route('periodos.index', ['gestion' => $request->input('gestion')])
            ->with('success', 'Periodo académico registrado satisfactoriamente.');
    }

    public function edit(Periodo $periodo): View
    {
        return view('periodos.edit', compact('periodo'));
    }

    public function update(GuardarPeriodoRequest $request, Periodo $periodo): RedirectResponse
    {
        $periodo->update($request->validated());

        return redirect()->route('periodos.index', ['gestion' => $periodo->gestion])
            ->with('success', 'Periodo académico actualizado satisfactoriamente.');
    }

    public function destroy(Periodo $periodo): RedirectResponse
    {
        if ($periodo->notas()->exists()) {
            return back()->withErrors([
                'error' => 'No se puede eliminar el periodo porque contiene calificaciones registradas.',
            ]);
        }

        $periodo->delete();

        return redirect()->route('periodos.index')->with('success', 'Periodo eliminado satisfactoriamente.');
    }
}
