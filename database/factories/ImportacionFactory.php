<?php

namespace Database\Factories;

use App\Models\Importacion;
use App\Models\Usuario;
use App\Services\CentralizadorPdfService;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Importacion> */
class ImportacionFactory extends Factory
{
    protected $model = Importacion::class;

    public function definition(): array
    {
        return ['usuario_id' => Usuario::factory(), 'archivo' => 'centralizadores/prueba.pdf', 'nombre_original' => 'centralizador.pdf', 'sha256' => fake()->unique()->sha256(), 'estado' => 'pendiente',
            'datos' => ['cabecera' => ['gestion' => 2026, 'curso' => 'SEXTO', 'paralelo' => 'B', 'periodo_numero' => 1, 'nivel' => 'SECUNDARIA COMUNITARIA PRODUCTIVA', 'turno' => 'TARDE', 'codigo_sie' => '51980097', 'unidad_educativa' => 'MAESTRO PITÁGORAS II'], 'materias' => CentralizadorPdfService::AREAS, 'estudiantes' => [], 'paginas' => 1], ];
    }
}
