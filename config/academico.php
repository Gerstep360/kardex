<?php

return [
    'pdf_memoria' => env('KARDEX_PDF_MEMORIA', '256M'),
    'institucion' => [
        'nombre' => 'MAESTRO PITÁGORAS II',
        'codigo_sie' => '51980097',
        'departamento' => 'SANTA CRUZ',
        'distrito' => 'LA GUARDIA',
        'direccion' => 'URB. CUMBRE DE LAS AMÉRICAS',
    ],
    /*
    |--------------------------------------------------------------------------
    | Parámetros Académicos y Calificaciones (Configuración DEMO y Verificable)
    |--------------------------------------------------------------------------
    |
    | Centraliza la regla de cálculo y validaciones para evitar dispersión
    | en controladores, vistas o servicios.
    |
    */

    'escala_minima' => env('ACADEMICO_ESCALA_MINIMA', 1),
    'escala_maxima' => env('ACADEMICO_ESCALA_MAXIMA', 100),
    'nota_aprobacion' => env('ACADEMICO_NOTA_APROBACION', 51),
    'decimales_permitidos' => env('ACADEMICO_DECIMALES', 2),

    // Método de promedio: 'aritmetico_simple' o 'ponderado'
    'metodo_promedio' => env('ACADEMICO_METODO_PROMEDIO', 'aritmetico_simple'),

    // Cantidad esperada de periodos para considerar el promedio 'completo' (por defecto 3 trimestres/periodos)
    'cantidad_periodos_completo' => env('ACADEMICO_CANTIDAD_PERIODOS', 3),

    // Regla de redondeo: 'redondeo_estandar' o 'sin_redondeo'
    'regla_redondeo' => env('ACADEMICO_REGLA_REDONDEO', 'redondeo_estandar'),
];
