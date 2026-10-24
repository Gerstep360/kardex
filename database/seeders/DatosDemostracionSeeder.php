<?php

namespace Database\Seeders;

use App\Models\Asignacion;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Materia;
use App\Models\Matricula;
use App\Models\Nota;
use App\Models\Periodo;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

class DatosDemostracionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gestion = 2026;

        // 1. Cursos
        $curso4A = Curso::firstOrCreate(
            ['nombre' => '4.º de Secundaria', 'paralelo' => 'A', 'gestion' => $gestion]
        );
        $curso4B = Curso::firstOrCreate(
            ['nombre' => '4.º de Secundaria', 'paralelo' => 'B', 'gestion' => $gestion]
        );
        $curso5A = Curso::firstOrCreate(
            ['nombre' => '5.º de Secundaria', 'paralelo' => 'A', 'gestion' => $gestion]
        );

        // 2. Materias
        $matMatematicas = Materia::firstOrCreate(
            ['sigla' => 'MAT-401'],
            ['nombre' => 'Matemáticas']
        );
        $matFisica = Materia::firstOrCreate(
            ['sigla' => 'FIS-401'],
            ['nombre' => 'Física']
        );
        $matLenguaje = Materia::firstOrCreate(
            ['sigla' => 'LEN-401'],
            ['nombre' => 'Lenguaje y Literatura']
        );
        $matSistemas = Materia::firstOrCreate(
            ['sigla' => 'SIS-BTH'],
            ['nombre' => 'Sistemas Informáticos BTH']
        );

        // 3. Periodos
        $p1 = Periodo::firstOrCreate(
            ['gestion' => $gestion, 'numero' => 1],
            ['nombre' => 'Primer Trimestre']
        );
        $p2 = Periodo::firstOrCreate(
            ['gestion' => $gestion, 'numero' => 2],
            ['nombre' => 'Segundo Trimestre']
        );
        $p3 = Periodo::firstOrCreate(
            ['gestion' => $gestion, 'numero' => 3],
            ['nombre' => 'Tercer Trimestre']
        );

        // 4. Asignaciones Docentes
        $docente1 = Usuario::where('usuario', 'docente1')->first();
        $docente2 = Usuario::where('usuario', 'docente2')->first();

        if ($docente1) {
            $asig1 = Asignacion::firstOrCreate([
                'curso_id' => $curso4A->id,
                'materia_id' => $matMatematicas->id,
                'gestion' => $gestion,
            ], [
                'docente_id' => $docente1->id,
            ]);

            $asig2 = Asignacion::firstOrCreate([
                'curso_id' => $curso4A->id,
                'materia_id' => $matSistemas->id,
                'gestion' => $gestion,
            ], [
                'docente_id' => $docente1->id,
            ]);
        }

        if ($docente2) {
            $asig3 = Asignacion::firstOrCreate([
                'curso_id' => $curso4A->id,
                'materia_id' => $matLenguaje->id,
                'gestion' => $gestion,
            ], [
                'docente_id' => $docente2->id,
            ]);
        }

        // 5. Estudiantes Ficticios
        $alumnos = [
            ['codigo' => 'EST-2026-001', 'apellidos' => 'Quispe Flores', 'nombres' => 'Alejandro'],
            ['codigo' => 'EST-2026-002', 'apellidos' => 'Copa Mamani', 'nombres' => 'Valeria'],
            ['codigo' => 'EST-2026-003', 'apellidos' => 'Rojas Guzmán', 'nombres' => 'Mateo'],
            ['codigo' => 'EST-2026-004', 'apellidos' => 'Torrez Suárez', 'nombres' => 'Camila'],
            ['codigo' => 'EST-2026-005', 'apellidos' => 'Fernandez Pinto', 'nombres' => 'Lucas'],
            ['codigo' => 'EST-2026-006', 'apellidos' => 'Vargas Arteaga', 'nombres' => 'Luciana'],
            ['codigo' => 'EST-2026-007', 'apellidos' => 'Salazar Mercado', 'nombres' => 'Joaquín'],
            ['codigo' => 'EST-2026-008', 'apellidos' => 'Aguilera Peña', 'nombres' => 'Mariana'],
        ];

        $matriculas4A = [];
        foreach ($alumnos as $alumnoData) {
            $estudiante = Estudiante::firstOrCreate(
                ['codigo' => $alumnoData['codigo']],
                [
                    'apellidos' => $alumnoData['apellidos'],
                    'nombres' => $alumnoData['nombres'],
                    'estado' => 'activo',
                ]
            );

            $mat = Matricula::firstOrCreate(
                ['estudiante_id' => $estudiante->id, 'gestion' => $gestion],
                ['curso_id' => $curso4A->id]
            );

            $matriculas4A[] = $mat;
        }

        // 6. Calificaciones de demostración (incluyendo al menos una nota faltante para demostrar cálculo de incompletos)
        if (isset($asig1)) {
            // Estudiante 1: Todas las notas registradas
            Nota::updateOrCreate(
                ['matricula_id' => $matriculas4A[0]->id, 'asignacion_id' => $asig1->id, 'periodo_id' => $p1->id],
                ['nota' => 85.00, 'observacion' => 'Excelente participación']
            );
            Nota::updateOrCreate(
                ['matricula_id' => $matriculas4A[0]->id, 'asignacion_id' => $asig1->id, 'periodo_id' => $p2->id],
                ['nota' => 90.00, 'observacion' => 'Constancia demostrada']
            );
            Nota::updateOrCreate(
                ['matricula_id' => $matriculas4A[0]->id, 'asignacion_id' => $asig1->id, 'periodo_id' => $p3->id],
                ['nota' => 88.00, 'observacion' => 'Cierre satisfactorio']
            );

            // Estudiante 2: Solo P1 y P2 (P3 faltante, para probar estado 'incompleto')
            Nota::updateOrCreate(
                ['matricula_id' => $matriculas4A[1]->id, 'asignacion_id' => $asig1->id, 'periodo_id' => $p1->id],
                ['nota' => 70.00, 'observacion' => 'Buen desempeño']
            );
            Nota::updateOrCreate(
                ['matricula_id' => $matriculas4A[1]->id, 'asignacion_id' => $asig1->id, 'periodo_id' => $p2->id],
                ['nota' => 75.00, 'observacion' => 'Mejora en prácticas']
            );

            // Estudiante 3: Con nota al límite
            Nota::updateOrCreate(
                ['matricula_id' => $matriculas4A[2]->id, 'asignacion_id' => $asig1->id, 'periodo_id' => $p1->id],
                ['nota' => 51.00, 'observacion' => 'Aprobado con esfuerzo']
            );

            // Estudiante 4: P1 sobresaliente
            Nota::updateOrCreate(
                ['matricula_id' => $matriculas4A[3]->id, 'asignacion_id' => $asig1->id, 'periodo_id' => $p1->id],
                ['nota' => 95.00, 'observacion' => 'Destacado en laboratorio']
            );
        }
    }
}
