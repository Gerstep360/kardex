<?php

namespace Database\Factories;

use App\Models\Periodo;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Periodo> */
class PeriodoFactory extends Factory
{
    protected $model = Periodo::class;

    public function definition(): array
    {
        return ['numero' => 1, 'nombre' => 'Primer Trimestre', 'gestion' => 2026];
    }
}
