<?php

use App\Http\Controllers\BoletinController;
use App\Http\Controllers\ImportacionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'rol:administrador,secretaria'])->group(function (): void {
    Route::get('/boletines', [BoletinController::class, 'index'])->name('boletines.index');
    Route::get('/boletines/centralizador/{curso}', [BoletinController::class, 'centralizador'])->name('boletines.centralizador');
    Route::get('/boletines/centralizador/{curso}/pdf', [BoletinController::class, 'centralizadorPdf'])->name('boletines.centralizador.pdf');
    Route::get('/estudiantes/{estudiante}/boletin', [BoletinController::class, 'show'])->name('boletines.show');
    Route::get('/estudiantes/{estudiante}/boletin/pdf', [BoletinController::class, 'pdf'])->name('boletines.pdf');
    Route::get('/estudiantes/{estudiante}/boletin/imprimir', [BoletinController::class, 'imprimir'])->name('boletines.imprimir');
});

Route::middleware(['auth', 'rol:administrador'])->prefix('importaciones')->name('importaciones.')->group(function (): void {
    Route::get('/', [ImportacionController::class, 'index'])->name('index');
    Route::post('/', [ImportacionController::class, 'store'])->middleware('throttle:10,1')->name('store');
    Route::get('/{importacion}', [ImportacionController::class, 'show'])->name('show');
    Route::get('/{importacion}/archivo', [ImportacionController::class, 'archivo'])->name('archivo');
    Route::post('/{importacion}/confirmar', [ImportacionController::class, 'confirmar'])->name('confirmar');
});
