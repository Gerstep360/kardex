<?php

namespace Database\Factories;

use App\Models\Asignacion;
use App\Models\Curso;
use App\Models\Materia;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Asignacion> */
class AsignacionFactory extends Factory
{
    protected $model = Asignacion::class;

    public function definition(): array
    {
        return ['docente_id' => Usuario::factory()->docente(), 'materia_id' => Materia::factory(), 'curso_id' => Curso::factory(), 'gestion' => 2026];
    }
}
