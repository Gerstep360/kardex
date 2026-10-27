<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('importaciones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $table->string('archivo');
            $table->string('nombre_original');
            $table->string('sha256', 64)->unique();
            $table->json('datos');
            $table->string('estado', 20)->default('pendiente');
            $table->json('resumen')->nullable();
            $table->timestamp('importada_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('importaciones');
    }
};
