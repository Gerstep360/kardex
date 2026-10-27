<?php

namespace App\Console\Commands;

use App\Services\LocalQrCode;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

#[Signature('kardex {--ip= : IP local que verá el teléfono} {--port= : Puerto de acceso} {--no-open : No abrir la imagen del QR automáticamente}')]
#[Description('Abre Kardex en la red local y muestra un QR para el teléfono')]
class KardexCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(LocalQrCode $qrCode): int
    {
        $ip = $this->option('ip') ?: $this->localAddress();

        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || str_starts_with($ip, '127.') || $ip === '0.0.0.0') {
            $this->error('No encontré la IP de tu red. Conéctate al Wi-Fi o usa: php artisan kardex --ip=TU_IP');

            return self::FAILURE;
        }

        $requestedPort = $this->option('port');
        if ($requestedPort !== null && (! ctype_digit($requestedPort) || (int) $requestedPort < 1024 || (int) $requestedPort > 65535)) {
            $this->error('El puerto debe ser un número entre 1024 y 65535.');

            return self::FAILURE;
        }

        $port = $this->availablePort($requestedPort === null ? null : (int) $requestedPort);
        if ($port === null) {
            $this->error('El puerto está ocupado. Prueba: php artisan kardex --port=8080');

            return self::FAILURE;
        }

        try {
            DB::connection()->getPdo();
        } catch (\PDOException $exception) {
            $this->error('No se pudo conectar con la base de datos. Enciende MySQL en XAMPP y vuelve a ejecutar: php artisan kardex');

            return self::FAILURE;
        }

        if (! is_file(public_path('build/manifest.json'))) {
            $this->info('Preparando la apariencia de Kardex…');
            $build = Process::fromShellCommandline('npm run build', base_path());
            $build->setTimeout(180);
            $build->run(fn (string $type, string $output) => $this->output->write($output));
            if (! $build->isSuccessful()) {
                $this->error('No se pudo preparar la apariencia. Revisa que Node.js y las dependencias estén instalados.');

                return self::FAILURE;
            }
        }

        $url = "http://{$ip}:{$port}";
        $this->newLine();
        $this->info('KARDEX · Acceso desde tu teléfono');
        $this->line("Enlace: {$url}");
        $qrPath = storage_path('app/kardex-acceso.html');
        $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        $page = '<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Kardex · Acceso desde tu teléfono</title>'
            .'<body style="margin:40px auto;padding:0 20px;max-width:520px;text-align:center;font-family:system-ui;background:#f4f7f5;color:#182d25">'
            .'<h1>Abre Kardex en tu teléfono</h1><p>Conecta tu teléfono al mismo Wi-Fi que esta computadora y escanea el QR con la cámara.</p>'
            .'<div style="margin:24px auto;background:white;border-radius:16px;max-width:444px">'
            .str_replace('width="444" height="444"', 'width="100%" height="100%"', $qrCode->svg($url))
            .'</div><p>También puedes escribir este enlace:</p><p><a style="font-size:22px;color:#076c49" href="'.$safeUrl.'">'.$safeUrl.'</a></p>'
            .'<p>Deja abierta la terminal mientras uses Kardex.</p></body></html>';
        if (file_put_contents($qrPath, $page) === false) {
            $this->warn('No pude guardar la imagen del QR. Puedes entrar usando el enlace de arriba.');
        } else {
            $this->line('QR guardado en: '.$qrPath);
            if (! $this->option('no-open')) {
                $open = PHP_OS_FAMILY === 'Windows'
                    ? new Process(['powershell', '-NoProfile', '-NonInteractive', '-WindowStyle', 'Hidden', '-Command', "Start-Process -FilePath '".str_replace("'", "''", $qrPath)."'"])
                    : new Process([PHP_OS_FAMILY === 'Darwin' ? 'open' : 'xdg-open', $qrPath]);
                $open->setTimeout(10);
                try {
                    if (! $open->run()) {
                        $this->line('Escanea el QR que se abrió en el navegador.');
                    } else {
                        $this->warn('Abre el archivo indicado arriba para ver el QR.');
                    }
                } catch (RuntimeException $exception) {
                    $this->warn('Abre el archivo indicado arriba para ver el QR.');
                }
            }
        }
        $this->newLine();
        $this->line('Deja esta ventana abierta. Para cerrar Kardex: Ctrl+C.');
        $this->line('Si Windows pregunta, permite PHP en redes privadas.');
        $this->line('Si no abre, comprueba que ambos equipos estén en la misma red y sin aislamiento de invitados.');

        $previous = getenv('KARDEX_LOCAL_NETWORK');
        $previousConfigCache = getenv('APP_CONFIG_CACHE');
        putenv('KARDEX_LOCAL_NETWORK=true');
        $_ENV['KARDEX_LOCAL_NETWORK'] = 'true';
        putenv('APP_CONFIG_CACHE='.storage_path('framework/kardex-network-config.php'));
        $_ENV['APP_CONFIG_CACHE'] = storage_path('framework/kardex-network-config.php');
        try {
            return $this->call('serve', ['--host' => '0.0.0.0', '--port' => $port, '--no-reload' => true]);
        } finally {
            if ($previousConfigCache === false) {
                putenv('APP_CONFIG_CACHE');
                unset($_ENV['APP_CONFIG_CACHE']);
            } else {
                putenv('APP_CONFIG_CACHE='.$previousConfigCache);
                $_ENV['APP_CONFIG_CACHE'] = $previousConfigCache;
            }
            if ($previous === false) {
                putenv('KARDEX_LOCAL_NETWORK');
                unset($_ENV['KARDEX_LOCAL_NETWORK']);
            } else {
                putenv('KARDEX_LOCAL_NETWORK='.$previous);
                $_ENV['KARDEX_LOCAL_NETWORK'] = $previous;
            }
        }
    }

    private function localAddress(): ?string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $process = new Process(['powershell', '-NoProfile', '-NonInteractive', '-Command', '(Get-NetIPConfiguration | Where-Object { $_.IPv4DefaultGateway -and $_.NetAdapter.Status -eq "Up" } | Select-Object -First 1).IPv4Address.IPAddress']);
            $process->setTimeout(15);
            $process->run();
            $address = trim($process->getOutput());
            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return $address;
            }
        }

        foreach (gethostbynamel(gethostname()) ?: [] as $address) {
            if (! str_starts_with($address, '127.')) {
                return $address;
            }
        }

        return null;
    }

    private function availablePort(?int $requestedPort): ?int
    {
        foreach ($requestedPort === null ? range(8000, 8020) : [$requestedPort] as $port) {
            $socket = @stream_socket_server("tcp://0.0.0.0:{$port}", $errorCode, $errorMessage);
            if ($socket !== false) {
                fclose($socket);

                return $port;
            }
        }

        return null;
    }
}
