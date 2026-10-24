<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class RespaldoService
{
    protected string $backupDir;

    public function __construct()
    {
        $this->backupDir = storage_path('app/private/respaldos');

        if (!File::exists($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true);
        }
    }

    /**
     * Obtiene el listado de respaldos disponibles.
     */
    public function listarRespaldos(): array
    {
        $files = File::files($this->backupDir);
        $respaldos = [];

        foreach ($files as $file) {
            if ($file->getExtension() === 'sql') {
                $respaldos[] = [
                    'nombre' => $file->getFilename(),
                    'tamano' => $this->formatearTamano($file->getSize()),
                    'tamano_bytes' => $file->getSize(),
                    'fecha' => date('Y-m-d H:i:s', $file->getMTime()),
                ];
            }
        }

        // Ordenar por fecha descendente
        usort($respaldos, fn($a, $b) => strcmp($b['fecha'], $a['fecha']));

        return $respaldos;
    }

    /**
     * Genera un nuevo respaldo de la base de datos MySQL/MariaDB.
     */
    public function crearRespaldo(): string
    {
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host', '127.0.0.1');
        $port = config('database.connections.mysql.port', '3306');

        $timestamp = date('Y-m-d_His');
        $filename = "respaldo_notas_{$timestamp}.sql";
        $filepath = $this->backupDir . DIRECTORY_SEPARATOR . $filename;

        // Intentar encontrar mysqldump en PATH o XAMPP
        $dumpBinary = $this->buscarBinario('mysqldump');

        $command = [
            $dumpBinary,
            "--host={$host}",
            "--port={$port}",
            "--user={$username}",
        ];

        if (!empty($password)) {
            $command[] = "--password={$password}";
        }

        $command[] = '--single-transaction';
        $command[] = '--quick';
        $command[] = $database;

        $process = new Process($command);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            // Si mysqldump falla, generar volcado mediante PDO/PHP como fallback offline
            $this->generarRespaldoPhp($filepath);
        } else {
            File::put($filepath, $process->getOutput());
        }

        if (!File::exists($filepath) || File::size($filepath) === 0) {
            throw new Exception("El archivo de respaldo no se pudo generar o está vacío.");
        }

        return $filename;
    }

    /**
     * Valida y devuelve la ruta completa de un respaldo.
     */
    public function obtenerRutaRespaldo(string $filename): string
    {
        // Limpieza de nombre de archivo para evitar path traversal
        $cleanName = basename($filename);
        $filepath = $this->backupDir . DIRECTORY_SEPARATOR . $cleanName;

        if (!File::exists($filepath) || pathinfo($filepath, PATHINFO_EXTENSION) !== 'sql') {
            throw new Exception("El archivo de respaldo solicitado no es válido o no existe.");
        }

        return $filepath;
    }

    /**
     * Restaura un respaldo existente. Realiza una copia preventiva antes.
     */
    public function restaurarRespaldo(string $filename): void
    {
        $filepath = $this->obtenerRutaRespaldo($filename);

        // Copia preventiva
        $this->crearRespaldo();

        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host', '127.0.0.1');
        $port = config('database.connections.mysql.port', '3306');

        $mysqlBinary = $this->buscarBinario('mysql');

        $command = [
            $mysqlBinary,
            "--host={$host}",
            "--port={$port}",
            "--user={$username}",
        ];

        if (!empty($password)) {
            $command[] = "--password={$password}";
        }

        $command[] = $database;

        $process = new Process($command);
        $process->setInput(File::get($filepath));
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            // Intentar restauración vía PDO
            $this->restaurarRespaldoPhp($filepath);
        }
    }

    protected function buscarBinario(string $name): string
    {
        // Rutas habituales en Windows / XAMPP
        $rutas = [
            "C:\\xampp\\mysql\\bin\\{$name}.exe",
            "D:\\xampp\\mysql\\bin\\{$name}.exe",
            $name,
        ];

        foreach ($rutas as $ruta) {
            if ($ruta === $name || File::exists($ruta)) {
                return $ruta;
            }
        }

        return $name;
    }

    /**
     * Fallback por PHP si el binario de consola no está en PATH ni en ruta XAMPP fija.
     */
    protected function generarRespaldoPhp(string $filepath): void
    {
        $pdo = \Illuminate\Support\Facades\DB::connection()->getPdo();
        $tables = [];
        $result = $pdo->query('SHOW TABLES');
        while ($row = $result->fetch(\PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        $sql = "-- Respaldo Generado por Plataforma Kardex Offline BTH\n";
        $sql .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            $createTable = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_NUM);
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql .= $createTable[1] . ";\n\n";

            $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $fields = array_map(fn($f) => "`{$f}`", array_keys($row));
                $values = array_map(function ($val) use ($pdo) {
                    if ($val === null) return 'NULL';
                    return $pdo->quote($val);
                }, array_values($row));

                $sql .= "INSERT INTO `{$table}` (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ");\n";
            }
            $sql .= "\n";
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        File::put($filepath, $sql);
    }

    protected function restaurarRespaldoPhp(string $filepath): void
    {
        $pdo = \Illuminate\Support\Facades\DB::connection()->getPdo();
        $sql = File::get($filepath);
        $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");
        $pdo->exec($sql);
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");
    }

    protected function formatearTamano(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
