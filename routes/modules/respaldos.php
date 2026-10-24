<?php

use App\Http\Controllers\RespaldoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'rol:administrador'])->group(function () {
    Route::get('/respaldos', [RespaldoController::class, 'index'])->name('respaldos.index');
    Route::post('/respaldos', [RespaldoController::class, 'store'])->name('respaldos.store');
    Route::get('/respaldos/{archivo}/descargar', [RespaldoController::class, 'download'])->name('respaldos.download');
    Route::post('/respaldos/{archivo}/restaurar', [RespaldoController::class, 'restore'])->name('respaldos.restore');
});
