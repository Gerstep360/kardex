<?php

use App\Http\Controllers\PromedioController;
use App\Http\Controllers\ReporteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'rol:administrador,docente'])->group(function () {
    Route::get('/promedios', [PromedioController::class, 'index'])->name('promedios.index');
    Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
    Route::get('/reportes/consolidado', [ReporteController::class, 'consolidado'])->name('reportes.consolidado');
    Route::get('/reportes/imprimir', [ReporteController::class, 'imprimir'])->name('reportes.imprimir');
    Route::get('/reportes/exportar', [ReporteController::class, 'exportar'])->name('reportes.exportar');
});
