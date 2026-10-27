<?php

namespace Database\Factories;

use App\Models\Asignacion;
use App\Models\Matricula;
use App\Models\Nota;
use App\Models\Periodo;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Nota> */
class NotaFactory extends Factory
{
    protected $model = Nota::class;

    public function definition(): array
    {
        return ['matricula_id' => Matricula::factory(),
            'asignacion_id' => fn (array $attributes) => Asignacion::factory()->create(['curso_id' => Matricula::findOrFail($attributes['matricula_id'])->curso_id])->id,
            'periodo_id' => Periodo::factory(), 'nota' => 85, ];
    }
}
