<?php

namespace Database\Factories;

use App\Models\Curso;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Curso> */
class CursoFactory extends Factory
{
    protected $model = Curso::class;

    public function definition(): array
    {
        return ['nombre' => fake()->unique()->bothify('Curso ##??'), 'paralelo' => 'B', 'gestion' => 2026, 'nivel' => 'SECUNDARIA COMUNITARIA PRODUCTIVA', 'turno' => 'TARDE'];
    }
}
