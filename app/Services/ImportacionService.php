<?php

namespace App\Services;

use App\Models\Asignacion;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Importacion;
use App\Models\Materia;
use App\Models\Matricula;
use App\Models\Nota;
use App\Models\Periodo;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ImportacionService
{
    public function revisar(Importacion $importacion): array
    {
        $datos = $this->completarDatos($importacion->datos);
        $cabecera = $datos['cabecera'];
        $curso = $this->curso($cabecera);
        $materias = Materia::orderBy('nombre')->get();
        $existentes = Estudiante::whereIn('codigo', array_column($datos['estudiantes'], 'codigo'))->get()->keyBy('codigo');
        $matriculas = Matricula::whereIn('estudiante_id', $existentes->pluck('id'))->where('gestion', $cabecera['gestion'])->get()->keyBy('estudiante_id');
        $periodo = Periodo::where('gestion', $cabecera['gestion'])->where('numero', $cabecera['periodo_numero'])->first();
        $asignaciones = $curso ? Asignacion::where('curso_id', $curso->id)->where('gestion', $cabecera['gestion'])->get()->keyBy('materia_id') : collect();
        $columnas = collect($datos['materias'])->map(function ($area, $indice) use ($materias, $asignaciones): array {
            $nombres = array_map(CentralizadorPdfService::normalizar(...), [$area['nombre'], $area['encabezado'], ...$area['alias']]);
            $candidatas = $materias->filter(fn ($materia) => in_array(CentralizadorPdfService::normalizar($materia->nombre), $nombres, true));
            $materia = $candidatas->count() === 1 ? $candidatas->first() : null;

            return $area + ['indice' => $indice, 'materia_id' => $materia?->id, 'coincidencias' => $candidatas->count(), 'docente_id' => $materia ? $asignaciones->get($materia->id)?->docente_id : null];
        });
        $conflictos = [];
        $faltanMatriculas = 0;
        foreach ($datos['estudiantes'] as $fila) {
            $estudiante = $existentes->get($fila['codigo']);
            $matricula = $estudiante ? $matriculas->get($estudiante->id) : null;
            if (! $matricula) {
                $faltanMatriculas++;
            } elseif (! $curso || $matricula->curso_id !== $curso->id) {
                $conflictos[] = 'RUDE '.$fila['codigo'].': ya tiene matrícula en otro curso de la gestión '.$cabecera['gestion'].'. No se cambiará su curso automáticamente.';
            }
            if ($estudiante && CentralizadorPdfService::normalizar($estudiante->nombre_completo) !== CentralizadorPdfService::normalizar($fila['apellidos'].', '.$fila['nombres'])) {
                $conflictos[] = 'RUDE '.$fila['codigo'].': el nombre del PDF no coincide con el registrado. Revisa su ficha antes de importar.';
            }
        }
        $notasExistentes = ($periodo && $asignaciones->isNotEmpty()) ? Nota::whereIn('matricula_id', $matriculas->pluck('id'))->whereIn('asignacion_id', $asignaciones->pluck('id'))->where('periodo_id', $periodo->id)->get()->keyBy(fn ($nota) => $nota->matricula_id.':'.$nota->asignacion_id) : collect();
        $diferencias = 0;
        foreach ($datos['estudiantes'] as $fila) {
            $estudiante = $existentes->get($fila['codigo']);
            $matricula = $estudiante ? $matriculas->get($estudiante->id) : null;
            foreach ($columnas as $columna) {
                $asignacion = $columna['materia_id'] ? $asignaciones->get($columna['materia_id']) : null;
                $nota = ($matricula && $asignacion) ? $notasExistentes->get($matricula->id.':'.$asignacion->id) : null;
                $valor = $fila['notas'][$columna['indice']];
                if ($nota && $valor !== null && (float) $nota->nota !== (float) $valor) {
                    $diferencias++;
                }
            }
        }

        return [
            'datosPendientes' => collect($datos['estudiantes'])->map(function ($fila, $indice): array {
                return ['indice' => $indice, 'codigo' => $fila['codigo'], 'nombre' => $fila['apellidos'].', '.$fila['nombres'], 'campos' => (new Estudiante($fila))->datosFaltantes()];
            })->filter(fn ($fila) => $fila['campos'] !== [])->values(),
            'curso' => $curso, 'periodo' => $periodo, 'columnas' => $columnas, 'materias' => $materias,
            'docentes' => Usuario::where('rol', 'docente')->where('activo', true)->orderBy('nombre')->get(),
            'faltanEstudiantes' => count($datos['estudiantes']) - $existentes->count(),
            'faltanMatriculas' => $faltanMatriculas, 'faltanMaterias' => $columnas->whereNull('materia_id')->count(),
            'conflictos' => $conflictos, 'diferencias' => $diferencias,
            'vacias' => collect($datos['estudiantes'])->sum(fn ($fila) => count(array_filter($fila['notas'], fn ($nota) => $nota === null))),
        ];
    }

    public function confirmar(Importacion $importacion, array $opciones, int $usuarioId): array
    {
        return DB::transaction(function () use ($importacion, $opciones, $usuarioId): array {
            $importacion = Importacion::whereKey($importacion->id)->lockForUpdate()->firstOrFail();
            if ($importacion->estado === 'completada') {
                return $importacion->resumen;
            }
            $datos = $this->completarDatos($importacion->datos, $opciones['estudiantes'] ?? []);
            $this->validarDatosEstudiantes($datos['estudiantes']);
            $revision = $this->revisar($importacion);
            if ($revision['conflictos'] !== []) {
                $this->fallar(implode(' ', $revision['conflictos']));
            }
            $esperadas = range(0, count($datos['materias']) - 1);
            foreach (['materias', 'docentes'] as $clave) {
                $indices = array_keys($opciones[$clave]);
                sort($indices);
                if ($indices !== $esperadas) {
                    $this->fallar('Revisa las '.$revision['columnas']->count().' materias del PDF antes de continuar.');
                }
            }
            $crear = (bool) ($opciones['crear_faltantes'] ?? false);
            $resumen = ['estudiantes' => 0, 'matriculas' => 0, 'materias' => 0, 'asignaciones' => 0, 'notas_nuevas' => 0, 'notas_actualizadas' => 0, 'notas_conservadas' => 0, 'celdas_vacias' => 0, 'confirmado_por' => $usuarioId, 'datos_completados' => $opciones['estudiantes'] ?? []];
            $cabecera = $datos['cabecera'];
            $curso = $revision['curso'];
            if (! $curso) {
                $this->exigirCreacion($crear, 'el curso '.$cabecera['curso'].' '.$cabecera['paralelo']);
                $curso = Curso::create(['nombre' => $cabecera['curso'].' de Secundaria', 'paralelo' => $cabecera['paralelo'], 'gestion' => $cabecera['gestion'], 'nivel' => $cabecera['nivel'], 'turno' => $cabecera['turno']]);
            }
            $resumen['curso_id'] = $curso->id;
            if ($crear) {
                for ($numero = 1; $numero <= min(12, (int) config('academico.cantidad_periodos_completo', 3)); $numero++) {
                    Periodo::firstOrCreate(['gestion' => $curso->gestion, 'numero' => $numero], ['nombre' => ($numero <= 3 ? ['Primer', 'Segundo', 'Tercer'][$numero - 1] : 'Periodo '.$numero).' Trimestre']);
                }
            }
            $revision['periodo'] = Periodo::where('gestion', $curso->gestion)->where('numero', $cabecera['periodo_numero'])->first();
            $periodo = $revision['periodo'];
            if (! $periodo) {
                $this->exigirCreacion($crear, 'el trimestre');
                $periodo = Periodo::create(['numero' => $cabecera['periodo_numero'], 'nombre' => ['Primer', 'Segundo', 'Tercer'][$cabecera['periodo_numero'] - 1].' Trimestre', 'gestion' => $cabecera['gestion']]);
            }
            $resumen['periodo_id'] = $periodo->id;
            $asignaciones = [];
            $idsMaterias = [];
            foreach ($datos['materias'] as $indice => $area) {
                $seleccion = $opciones['materias'][$indice];
                $docente = Usuario::whereKey($opciones['docentes'][$indice])->where('rol', 'docente')->where('activo', true)->first();
                if (! $docente) {
                    $this->fallar('Selecciona un docente activo para '.$area['nombre'].'.');
                }
                if ($seleccion === 'nueva') {
                    $this->exigirCreacion($crear, 'la materia '.$area['nombre']);
                    if ($revision['columnas'][$indice]['coincidencias'] > 0) {
                        $this->fallar('Ya existe una materia que coincide con '.$area['nombre'].'. Selecciónala en la lista para evitar duplicados.');
                    }
                    if (Materia::where('sigla', $area['sigla'])->exists()) {
                        $this->fallar('La sigla '.$area['sigla'].' ya existe. Selecciona la materia existente en lugar de crear otra.');
                    }
                    $materia = Materia::create(['nombre' => $area['nombre'], 'sigla' => $area['sigla'], 'campo' => $area['campo'], 'orden' => $indice + 1]);
                    $resumen['materias']++;
                } else {
                    $materia = ctype_digit((string) $seleccion) ? Materia::find($seleccion) : null;
                    if (! $materia) {
                        $this->fallar('Selecciona una materia válida para '.$area['nombre'].'.');
                    }
                    if (! $materia->campo || ! $materia->orden) {
                        $materia->update(['campo' => $materia->campo ?: $area['campo'], 'orden' => $materia->orden ?: $indice + 1]);
                    }
                }
                if (in_array($materia->id, $idsMaterias, true)) {
                    $this->fallar('Seleccionaste la misma materia para dos columnas. Cada columna debe tener una materia distinta.');
                }
                $idsMaterias[] = $materia->id;
                $asignacion = Asignacion::where('curso_id', $curso->id)->where('materia_id', $materia->id)->where('gestion', $curso->gestion)->lockForUpdate()->first();
                if ($asignacion && $asignacion->docente_id !== $docente->id) {
                    $this->fallar('La materia '.$materia->nombre.' ya tiene otro docente asignado. Selecciona al responsable actual o modifica su asignación antes de importar.');
                }
                if (! $asignacion) {
                    $this->exigirCreacion($crear, 'la asignación de '.$materia->nombre);
                    $asignacion = Asignacion::create(['docente_id' => $docente->id, 'materia_id' => $materia->id, 'curso_id' => $curso->id, 'gestion' => $curso->gestion]);
                    $resumen['asignaciones']++;
                }
                $asignaciones[$indice] = $asignacion;
            }
            foreach ($datos['estudiantes'] as $fila) {
                $estudiante = Estudiante::where('codigo', $fila['codigo'])->lockForUpdate()->first();
                if (! $estudiante) {
                    $this->exigirCreacion($crear, 'los estudiantes nuevos');
                    $estudiante = Estudiante::create(collect($fila)->only(['codigo', 'nombres', 'apellidos', 'apellido_paterno', 'apellido_materno', 'genero', 'fecha_nacimiento', 'lugar_nacimiento', 'documento_identidad'])->all() + ['estado' => 'activo']);
                    $resumen['estudiantes']++;
                } else {
                    $campos = collect($fila)->only(['apellido_paterno', 'apellido_materno', 'genero', 'fecha_nacimiento', 'lugar_nacimiento', 'documento_identidad'])
                        ->filter(fn ($valor, $campo) => blank($estudiante->getRawOriginal($campo)));
                    if ($campos->isNotEmpty()) {
                        $estudiante->update($campos->all());
                    }
                }
                $matricula = Matricula::where('estudiante_id', $estudiante->id)->where('gestion', $curso->gestion)->lockForUpdate()->first();
                if ($matricula && $matricula->curso_id !== $curso->id) {
                    $this->fallar('El estudiante con RUDE '.$fila['codigo'].' ya tiene matrícula en otro curso. No se realizaron cambios.');
                }
                if (! $matricula) {
                    $this->exigirCreacion($crear, 'las matrículas nuevas');
                    $matricula = Matricula::create(['estudiante_id' => $estudiante->id, 'curso_id' => $curso->id, 'gestion' => $curso->gestion, 'estado_matricula' => $fila['estado_matricula'] ?: 'EFECTIVO']);
                    $resumen['matriculas']++;
                }
                foreach ($fila['notas'] as $indice => $valor) {
                    if ($valor === null) {
                        $resumen['celdas_vacias']++;

                        continue;
                    }
                    $nota = Nota::where('matricula_id', $matricula->id)->where('asignacion_id', $asignaciones[$indice]->id)->where('periodo_id', $periodo->id)->lockForUpdate()->first();
                    if (! $nota) {
                        Nota::create(['matricula_id' => $matricula->id, 'asignacion_id' => $asignaciones[$indice]->id, 'periodo_id' => $periodo->id, 'nota' => $valor, 'importacion_id' => $importacion->id]);
                        $resumen['notas_nuevas']++;
                    } elseif ($opciones['politica'] === 'reemplazar' && (float) $nota->nota !== (float) $valor) {
                        $nota->update(['nota' => $valor, 'importacion_id' => $importacion->id]);
                        $resumen['notas_actualizadas']++;
                    } else {
                        $resumen['notas_conservadas']++;
                    }
                }
            }
            $importacion->update(['estado' => 'completada', 'resumen' => $resumen, 'importada_at' => now()]);

            return $resumen;
        }, 3);
    }

    private function completarDatos(array $datos, array $completados = []): array
    {
        $existentes = Estudiante::whereIn('codigo', array_column($datos['estudiantes'], 'codigo'))->get()->keyBy('codigo');
        foreach ($datos['estudiantes'] as $indice => &$fila) {
            foreach (Estudiante::DATOS_OBLIGATORIOS as $campo => $etiqueta) {
                if (blank($fila[$campo] ?? null)) {
                    $fila[$campo] = $existentes->get($fila['codigo'])?->getRawOriginal($campo) ?: ($completados[$indice][$campo] ?? null);
                }
            }
        }
        unset($fila);

        return $datos;
    }

    private function validarDatosEstudiantes(array $filas): void
    {
        $errores = [];
        foreach ($filas as $indice => $fila) {
            $reglas = [
                'codigo' => ['required', 'string', 'max:30'], 'nombres' => ['required', 'string', 'max:120'],
                'apellido_paterno' => ['required', 'string', 'max:120'], 'apellido_materno' => ['required', 'string', 'max:120'],
                'genero' => ['required', 'in:M,F'], 'fecha_nacimiento' => ['required', 'date', 'before_or_equal:today'],
                'lugar_nacimiento' => ['required', 'string', 'max:20'], 'documento_identidad' => ['required', 'string', 'max:30'],
            ];
            $validacion = Validator::make($fila, $reglas, [
                'required' => 'Completa :attribute.', 'date' => 'Revisa :attribute.',
                'in' => 'Elige :attribute.', 'before_or_equal' => 'Revisa :attribute: no puede ser posterior a hoy.',
                'max' => 'Revisa :attribute: supera el tamaño permitido.',
            ], Estudiante::DATOS_OBLIGATORIOS);
            foreach ($validacion->errors()->messages() as $campo => $mensajes) {
                $errores['estudiantes.'.$indice.'.'.$campo] = 'RUDE '.$fila['codigo'].': '.$mensajes[0];
            }
        }
        if ($errores !== []) {
            throw ValidationException::withMessages($errores);
        }
    }

    private function curso(array $cabecera): ?Curso
    {
        $candidatos = Curso::where('gestion', $cabecera['gestion'])->where('paralelo', $cabecera['paralelo'])->get()
            ->filter(fn ($curso) => $this->grado($curso->nombre) === $this->grado($cabecera['curso']));
        if ($candidatos->count() > 1) {
            $this->fallar('Hay más de un curso que coincide con '.$cabecera['curso'].' '.$cabecera['paralelo'].'. Revisa los cursos duplicados antes de importar.');
        }

        return $candidatos->first();
    }

    private function grado(string $nombre): string
    {
        $valor = CentralizadorPdfService::normalizar($nombre);
        foreach (['primero' => '1', 'segundo' => '2', 'tercero' => '3', 'cuarto' => '4', 'quinto' => '5', 'sexto' => '6'] as $texto => $numero) {
            $valor = str_replace($texto, $numero, $valor);
        }
        $valor = preg_replace('/^(\d+)[oa]/', '$1', $valor);

        return preg_replace('/(desecundaria|secundaria|comunitaria|productiva|de)/', '', $valor);
    }

    private function exigirCreacion(bool $permitido, string $registro): void
    {
        if (! $permitido) {
            $this->fallar('Falta '.$registro.'. Marca “Agregar los datos que faltan” o agrégalo antes de continuar.');
        }
    }

    private function fallar(string $mensaje): never
    {
        throw ValidationException::withMessages(['importacion' => $mensaje]);
    }
}
