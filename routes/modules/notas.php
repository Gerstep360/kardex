<?php

use App\Http\Controllers\NotaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'rol:docente'])->group(function () {
    Route::get('/notas', [NotaController::class, 'index'])->name('notas.index');
    Route::get('/notas/planilla', [NotaController::class, 'planilla'])->name('notas.planilla');
    Route::post('/notas/planilla', [NotaController::class, 'guardarPlanilla'])->name('notas.guardar');
});
