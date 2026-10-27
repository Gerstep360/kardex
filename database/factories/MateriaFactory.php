<?php

namespace Database\Factories;

use App\Models\Materia;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Materia> */
class MateriaFactory extends Factory
{
    protected $model = Materia::class;

    public function definition(): array
    {
        return ['nombre' => fake()->unique()->words(2, true), 'sigla' => fake()->unique()->bothify('MAT-###??'), 'orden' => 0];
    }
}
