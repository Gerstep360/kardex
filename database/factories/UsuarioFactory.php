<?php

namespace Database\Factories;

use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Usuario> */
class UsuarioFactory extends Factory
{
    protected $model = Usuario::class;

    public function definition(): array
    {
        return ['nombre' => fake()->name(), 'usuario' => fake()->unique()->userName(), 'password' => 'password123', 'rol' => 'administrador', 'activo' => true];
    }

    public function docente(): static
    {
        return $this->state(fn () => ['rol' => 'docente']);
    }

    public function secretaria(): static
    {
        return $this->state(fn () => ['rol' => 'secretaria']);
    }
}
