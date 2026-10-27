<?php

namespace Tests\Feature;

use Tests\TestCase;

class KardexCommandTest extends TestCase
{
    public function test_rechaza_una_ip_publica_antes_de_cambiar_la_red(): void
    {
        $this->artisan('kardex', ['--ip' => '8.8.8.8', '--no-open' => true])
            ->expectsOutput('No encontré la IP de tu red. Conéctate al Wi-Fi o usa: php artisan kardex --ip=TU_IP')
            ->assertFailed();
    }

    public function test_rechaza_un_puerto_invalido_antes_de_iniciar_el_servidor(): void
    {
        $this->artisan('kardex', ['--ip' => '192.168.1.20', '--port' => '70000', '--no-open' => true])
            ->expectsOutput('El puerto debe ser un número entre 1024 y 65535.')
            ->assertFailed();
    }
}
