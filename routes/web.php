<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Web Modulares
|--------------------------------------------------------------------------
| Cada archivo maneja un módulo o responsabilidad específica:
| - auth.php      -> Login, logout y panel dashboard
| - admin.php     -> Gestión administrativa (usuarios, estudiantes, cursos, materias, matriculas, asignaciones, periodos)
| - notas.php     -> Planillas de notas para docentes
| - reportes.php  -> Consolidado, promedios, impresión y exportación CSV
| - respaldos.php -> Gestión y restauración de copias de seguridad de BD
|
*/

require __DIR__ . '/modules/auth.php';
require __DIR__ . '/modules/admin.php';
require __DIR__ . '/modules/notas.php';
require __DIR__ . '/modules/reportes.php';
require __DIR__ . '/modules/respaldos.php';
