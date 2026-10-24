<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Administrador por defecto
        Usuario::updateOrCreate(
            ['usuario' => 'admin'],
            [
                'nombre' => 'Administrador General',
                'password' => Hash::make('admin123'),
                'rol' => 'administrador',
                'activo' => true,
            ]
        );

        // Docente de demostración inicial
        Usuario::updateOrCreate(
            ['usuario' => 'docente1'],
            [
                'nombre' => 'Lic. Carlos Mendez Rocha',
                'password' => Hash::make('docente123'),
                'rol' => 'docente',
                'activo' => true,
            ]
        );

        // Docente 2
        Usuario::updateOrCreate(
            ['usuario' => 'docente2'],
            [
                'nombre' => 'Prof. Elena Morales Torrico',
                'password' => Hash::make('docente123'),
                'rol' => 'docente',
                'activo' => true,
            ]
        );
    }
}
