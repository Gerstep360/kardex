<?php

namespace App\Http\Controllers;

use App\Services\RespaldoService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RespaldoController extends Controller
{
    public function __construct(
        protected RespaldoService $respaldoService
    ) {}

    public function index(): View
    {
        $respaldos = $this->respaldoService->listarRespaldos();
        return view('respaldos.index', compact('respaldos'));
    }

    public function store(): RedirectResponse
    {
        try {
            $nombreArchivo = $this->respaldoService->crearRespaldo();
            return redirect()->route('respaldos.index')
                ->with('success', "Copia de seguridad '{$nombreArchivo}' generada con éxito.");
        } catch (Exception $e) {
            return redirect()->route('respaldos.index')
                ->withErrors(['error' => 'Error al generar la copia de seguridad: ' . $e->getMessage()]);
        }
    }

    public function download(string $archivo): BinaryFileResponse|RedirectResponse
    {
        try {
            $ruta = $this->respaldoService->obtenerRutaRespaldo($archivo);
            return response()->download($ruta, $archivo, [
                'Content-Type' => 'application/sql',
            ]);
        } catch (Exception $e) {
            return redirect()->route('respaldos.index')
                ->withErrors(['error' => 'No se pudo descargar el archivo: ' . $e->getMessage()]);
        }
    }

    public function restore(Request $request, string $archivo): RedirectResponse
    {
        if (!$request->has('confirmar_restauracion')) {
            return redirect()->route('respaldos.index')
                ->withErrors(['error' => 'Debe confirmar explícitamente la acción de restauración.']);
        }

        try {
            $this->respaldoService->restaurarRespaldo($archivo);

            return redirect()->route('respaldos.index')
                ->with('success', "Base de datos restaurada con éxito desde '{$archivo}'. Se ha creado una copia preventiva antes de la restauración.");
        } catch (Exception $e) {
            return redirect()->route('respaldos.index')
                ->withErrors(['error' => 'Error durante la restauración: ' . $e->getMessage()]);
        }
    }
}
