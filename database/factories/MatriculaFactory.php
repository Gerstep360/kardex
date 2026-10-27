<?php

namespace Database\Factories;

use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Matricula;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Matricula> */
class MatriculaFactory extends Factory
{
    protected $model = Matricula::class;

    public function definition(): array
    {
        return ['estudiante_id' => Estudiante::factory(), 'curso_id' => Curso::factory(), 'gestion' => 2026, 'estado_matricula' => 'EFECTIVO'];
    }
}
