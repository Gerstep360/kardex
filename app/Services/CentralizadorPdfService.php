<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Smalot\PdfParser\Config;
use Smalot\PdfParser\Parser;
use Throwable;

class CentralizadorPdfService
{
    public const AREAS = [
        ['encabezado' => 'Com.Lenguaje', 'nombre' => 'Comunicación y Lenguajes', 'sigla' => 'COM-LEN', 'campo' => 'Comunidad y Sociedad', 'alias' => ['Lenguaje y Literatura', 'Lenguaje', 'Comunicación y Lenguaje']],
        ['encabezado' => 'L.Extranj.', 'nombre' => 'Lengua Extranjera', 'sigla' => 'LEN-EXT', 'campo' => 'Comunidad y Sociedad', 'alias' => ['Inglés']],
        ['encabezado' => 'Cs.Sociales', 'nombre' => 'Ciencias Sociales', 'sigla' => 'CS-SOC', 'campo' => 'Comunidad y Sociedad', 'alias' => []],
        ['encabezado' => 'Edu.Musical', 'nombre' => 'Educación Musical', 'sigla' => 'ED-MUS', 'campo' => 'Comunidad y Sociedad', 'alias' => ['Música']],
        ['encabezado' => 'Art.Plástic.V.', 'nombre' => 'Artes Plásticas y Visuales', 'sigla' => 'ART-VIS', 'campo' => 'Comunidad y Sociedad', 'alias' => []],
        ['encabezado' => 'E.Física.D.', 'nombre' => 'Educación Física y Deportes', 'sigla' => 'ED-FIS', 'campo' => 'Comunidad y Sociedad', 'alias' => ['Educación Física']],
        ['encabezado' => 'Matemática', 'nombre' => 'Matemática', 'sigla' => 'MAT', 'campo' => 'Ciencia, Tecnología y Producción', 'alias' => ['Matemáticas']],
        ['encabezado' => 'T.Especial.', 'nombre' => 'Técnica Especializada', 'sigla' => 'TEC-ESP', 'campo' => 'Ciencia, Tecnología y Producción', 'alias' => []],
        ['encabezado' => 'Física', 'nombre' => 'Física', 'sigla' => 'FIS', 'campo' => 'Vida, Tierra y Territorio', 'alias' => []],
        ['encabezado' => 'Química', 'nombre' => 'Química', 'sigla' => 'QUI', 'campo' => 'Vida, Tierra y Territorio', 'alias' => []],
        ['encabezado' => 'Bio y Geo.', 'nombre' => 'Biología y Geografía', 'sigla' => 'BIO-GEO', 'campo' => 'Vida, Tierra y Territorio', 'alias' => []],
        ['encabezado' => 'Cos.Fil y Sic', 'nombre' => 'Cosmovisiones, Filosofía y Psicología', 'sigla' => 'COS-FIL', 'campo' => 'Cosmos y Pensamiento', 'alias' => []],
        ['encabezado' => 'Val.Esp.Rel.', 'nombre' => 'Valores, Espiritualidad y Religiones', 'sigla' => 'VAL-REL', 'campo' => 'Cosmos y Pensamiento', 'alias' => []],
    ];

    public function leer(string $archivo): array
    {
        try {
            $config = new Config;
            $config->setRetainImageContent(false);
            $config->setDecodeMemoryLimit(32 * 1024 * 1024);
            $pdf = (new Parser([], $config))->parseFile($archivo);
            $paginas = $pdf->getPages();
            if (count($paginas) === 0 || count($paginas) > 30) {
                $this->fallar('El PDF debe contener entre 1 y 30 páginas.');
            }
            $cabecera = null;
            $estudiantes = [];
            $rudes = [];
            foreach ($paginas as $numeroPagina => $pagina) {
                $box = $pagina->getDetails()['MediaBox'] ?? [0, 0, 0, 0];
                $ancho = (float) ($box[2] ?? 0);
                $alto = (float) ($box[3] ?? 0);
                if ($ancho <= 0 || $alto <= 0 || abs($ancho / $alto - 1008 / 612) > 0.05) {
                    $this->fallar('La página '.($numeroPagina + 1).' no tiene el formato horizontal del centralizador de referencia.');
                }
                $tokens = collect($pagina->getDataTm())->map(fn ($dato) => [
                    'x' => (float) $dato[0][4] * 1008 / $ancho,
                    'y' => (float) $dato[0][5] * 612 / $alto,
                    'texto' => trim(preg_replace('/\s+/u', ' ', $dato[1])),
                ])->filter(fn ($dato) => $dato['texto'] !== '')->values()->all();
                $meta = $this->cabecera($tokens);
                if ($cabecera !== null && $cabecera !== $meta) {
                    $this->fallar('Las páginas no pertenecen al mismo curso, gestión y trimestre. Carga un centralizador por vez.');
                }
                $cabecera = $meta;
                $anclas = array_values(array_filter($tokens, fn ($token) => $token['x'] >= 17 && $token['x'] < 32 && ctype_digit($token['texto']) && $token['y'] > 110 && $token['y'] < 418));
                usort($anclas, fn ($a, $b) => $b['y'] <=> $a['y']);
                if ($anclas === []) {
                    $this->fallar('No se encontraron estudiantes en la página '.($numeroPagina + 1).'. El PDF debe contener texto seleccionable; no una fotografía.');
                }
                foreach ($anclas as $ancla) {
                    $fila = array_values(array_filter($tokens, fn ($token) => abs($token['y'] - $ancla['y']) < 6.6));
                    $estudiante = $this->estudiante($fila, (int) $ancla['texto']);
                    if (isset($rudes[$estudiante['codigo']])) {
                        $this->fallar('El código RUDE '.$estudiante['codigo'].' aparece más de una vez. Revisa el documento.');
                    }
                    $rudes[$estudiante['codigo']] = true;
                    $estudiantes[] = $estudiante;
                }
            }
            if (count($estudiantes) > 500) {
                $this->fallar('El documento supera el límite de 500 estudiantes.');
            }

            return ['cabecera' => $cabecera, 'materias' => self::AREAS, 'estudiantes' => $estudiantes, 'paginas' => count($paginas)];
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            $this->fallar('No pudimos leer este PDF. Usa el centralizador original con texto seleccionable, sin contraseña y con la estructura de referencia.');
        }
    }

    public static function normalizar(string $texto): string
    {
        return preg_replace('/[^a-z0-9]/', '', Str::lower(Str::ascii($texto)));
    }

    private function cabecera(array $tokens): array
    {
        $gestion = $this->celda($tokens, 100, 240, 491, 501);
        $curso = $this->celda($tokens, 350, 493, 479, 490);
        $paralelo = $this->celda($tokens, 550, 620, 479, 490);
        $sie = $this->celda($tokens, 760, 900, 491, 501);
        $periodos = [];
        foreach ($tokens as $token) {
            if ($token['x'] >= 382 && $token['x'] < 991 && $token['y'] > 418 && $token['y'] < 430 && preg_match('/^T([1-3])$/', $token['texto'], $match)) {
                $periodos[] = (int) $match[1];
            }
        }
        if (! preg_match('/^20\d{2}$/', $gestion) || $curso === '' || $paralelo === '' || count($periodos) !== 13 || count(array_unique($periodos)) !== 1) {
            $this->fallar('No reconocimos la gestión, el curso o las 13 columnas de un trimestre. Usa el mismo formato del centralizador de referencia.');
        }
        foreach (self::AREAS as $indice => $area) {
            $encabezado = $this->celda($tokens, 382.14 + $indice * 46.84, 382.14 + ($indice + 1) * 46.84, 430, 438);
            if (self::normalizar($encabezado) !== self::normalizar($area['encabezado'])) {
                $this->fallar('No reconocimos la columna '.($indice + 1).' de materias. No se importó ninguna nota.');
            }
        }
        if ($sie !== (string) config('academico.institucion.codigo_sie')) {
            $this->fallar('El código SIE del PDF no coincide con el colegio configurado en Kardex.');
        }

        return [
            'gestion' => (int) $gestion, 'curso' => $curso, 'paralelo' => $paralelo, 'codigo_sie' => $sie,
            'periodo_numero' => $periodos[0], 'nivel' => $this->celda($tokens, 100, 250, 479, 490),
            'turno' => $this->celda($tokens, 760, 900, 479, 490),
            'unidad_educativa' => $this->celda($tokens, 100, 250, 467, 478),
        ];
    }

    private function estudiante(array $tokens, int $numero): array
    {
        $paterno = $this->celda($tokens, 32, 82, 0, 612);
        $materno = $this->celda($tokens, 82, 131, 0, 612);
        $nombres = $this->celda($tokens, 131, 180, 0, 612);
        $rude = $this->celda($tokens, 180, 235, 0, 612);
        if ($nombres === '' || trim($paterno.' '.$materno) === '' || ! preg_match('/^[A-Za-z0-9]{6,30}$/', $rude)) {
            $this->fallar('No pudimos identificar el nombre o el código RUDE de la fila '.$numero.'. Revisa el PDF original.');
        }
        $notas = [];
        for ($indice = 0; $indice < 13; $indice++) {
            $texto = $this->celda($tokens, 382.14 + $indice * 46.84, 382.14 + ($indice + 1) * 46.84, 0, 612);
            if ($texto === '' || $texto === '-') {
                $notas[] = null;

                continue;
            }
            $texto = str_replace(',', '.', $texto);
            if (! preg_match('/^\d{1,3}(\.\d{1,2})?$/', $texto) || (float) $texto < config('academico.escala_minima') || (float) $texto > config('academico.escala_maxima')) {
                $this->fallar('La fila '.$numero.' contiene una nota fuera de escala o ilegible. No se importó ninguna calificación.');
            }
            $notas[] = (float) $texto;
        }
        $fecha = $this->celda($tokens, 254, 291, 0, 612);
        $fechaObjeto = \DateTimeImmutable::createFromFormat('!d-m-Y', $fecha);
        if (! $fechaObjeto || $fechaObjeto->format('d-m-Y') !== $fecha) {
            $this->fallar('La fecha de nacimiento de la fila '.$numero.' no es válida.');
        }

        return [
            'numero' => $numero, 'codigo' => strtoupper($rude), 'nombres' => $nombres,
            'apellido_paterno' => $paterno, 'apellido_materno' => $materno, 'apellidos' => trim($paterno.' '.$materno),
            'genero' => $this->celda($tokens, 235, 254, 0, 612),
            'fecha_nacimiento' => $fechaObjeto->format('Y-m-d'), 'lugar_nacimiento' => $this->celda($tokens, 291, 308, 0, 612),
            'documento_identidad' => $this->celda($tokens, 308, 342, 0, 612),
            'estado_matricula' => $this->celda($tokens, 342, 382.14, 0, 612), 'notas' => $notas,
        ];
    }

    private function celda(array $tokens, float $izquierda, float $derecha, float $abajo, float $arriba): string
    {
        $contenidos = array_values(array_filter($tokens, fn ($token) => $token['x'] >= $izquierda && $token['x'] < $derecha && $token['y'] >= $abajo && $token['y'] <= $arriba));
        usort($contenidos, fn ($a, $b) => abs($a['y'] - $b['y']) > 1 ? $b['y'] <=> $a['y'] : $a['x'] <=> $b['x']);

        return trim(implode(' ', array_column($contenidos, 'texto')));
    }

    private function fallar(string $mensaje): never
    {
        throw ValidationException::withMessages(['archivo' => $mensaje]);
    }
}
