<?php

namespace Database\Factories;

use App\Models\Estudiante;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Estudiante> */
class EstudianteFactory extends Factory
{
    protected $model = Estudiante::class;

    public function definition(): array
    {
        return [
            'codigo' => fake()->unique()->numerify('#################'),
            'nombres' => fake()->firstName(),
            'apellido_paterno' => fake()->lastName(),
            'apellido_materno' => fake()->lastName(),
            'apellidos' => fn (array $atributos) => $atributos['apellido_paterno'].' '.$atributos['apellido_materno'],
            'genero' => 'F', 'fecha_nacimiento' => '2009-02-15', 'lugar_nacimiento' => 'SC',
            'documento_identidad' => fake()->numerify('########'), 'estado' => 'activo',
        ];
    }
}
