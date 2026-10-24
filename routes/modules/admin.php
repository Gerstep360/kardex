<?php

use App\Http\Controllers\AsignacionController;
use App\Http\Controllers\CursoController;
use App\Http\Controllers\EstudianteController;
use App\Http\Controllers\MateriaController;
use App\Http\Controllers\MatriculaController;
use App\Http\Controllers\PeriodoController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'rol:administrador'])->group(function () {
    Route::resource('usuarios', UsuarioController::class);
    Route::resource('estudiantes', EstudianteController::class);
    Route::resource('cursos', CursoController::class);
    Route::resource('materias', MateriaController::class);
    Route::resource('matriculas', MatriculaController::class)->except(['show']);
    Route::resource('asignaciones', AsignacionController::class)->parameters(['asignaciones' => 'asignacion'])->except(['show']);
    Route::resource('periodos', PeriodoController::class)->except(['show']);
});
