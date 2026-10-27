<?php

namespace App\Http\Controllers;

use App\Http\Requests\CargarCentralizadorRequest;
use App\Http\Requests\ConfirmarImportacionRequest;
use App\Models\Importacion;
use App\Services\CentralizadorPdfService;
use App\Services\ImportacionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportacionController extends Controller
{
    public function __construct(private CentralizadorPdfService $lector, private ImportacionService $importador) {}

    public function index(): View
    {
        return view('importaciones.index', ['importaciones' => Importacion::with('usuario')->latest()->paginate(10)]);
    }

    public function store(CargarCentralizadorRequest $request): RedirectResponse
    {
        $archivo = $request->file('archivo');
        $hash = hash_file('sha256', $archivo->getRealPath());
        $existente = Importacion::where('sha256', $hash)->first();
        if ($existente) {
            return redirect()->route('importaciones.show', $existente)->with('success', 'Este PDF ya fue cargado. Puedes consultar su revisión y estado.');
        }
        $datos = $this->lector->leer($archivo->getRealPath());
        $ruta = $archivo->store('centralizadores', 'local');
        if (! $ruta) {
            throw ValidationException::withMessages(['archivo' => 'No se pudo guardar el PDF en el servidor. Pide a administración que revise el espacio y los permisos de almacenamiento.']);
        }
        $importacion = Importacion::firstOrCreate(['sha256' => $hash], [
            'usuario_id' => $request->user()->id, 'archivo' => $ruta,
            'nombre_original' => mb_substr(basename($archivo->getClientOriginalName()), 0, 240), 'datos' => $datos,
        ]);
        if (! $importacion->wasRecentlyCreated) {
            Storage::disk('local')->delete($ruta);
        }

        return redirect()->route('importaciones.show', $importacion);
    }

    public function show(Importacion $importacion): View
    {
        return view('importaciones.show', ['importacion' => $importacion, 'revision' => $this->importador->revisar($importacion)]);
    }

    public function archivo(Importacion $importacion): BinaryFileResponse
    {
        abort_unless(Storage::disk('local')->exists($importacion->archivo), 404);

        return response()->download(Storage::disk('local')->path($importacion->archivo), $importacion->nombre_original, ['Content-Type' => 'application/pdf', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }

    public function confirmar(ConfirmarImportacionRequest $request, Importacion $importacion): RedirectResponse
    {
        $this->importador->confirmar($importacion, $request->validated(), $request->user()->id);

        return redirect()->route('importaciones.show', $importacion)->with('success', 'Listo. La importación terminó y las notas están disponibles para los boletines.');
    }
}
