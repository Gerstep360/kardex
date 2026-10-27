<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estudiantes', function (Blueprint $table): void {
            $table->string('apellido_paterno', 120)->nullable();
            $table->string('apellido_materno', 120)->nullable();
            $table->string('genero', 1)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('lugar_nacimiento', 20)->nullable();
            $table->string('documento_identidad', 30)->nullable();
        });
        Schema::table('materias', function (Blueprint $table): void {
            $table->string('campo', 100)->nullable();
            $table->unsignedSmallInteger('orden')->default(0);
        });
        Schema::table('cursos', function (Blueprint $table): void {
            $table->string('nivel', 100)->nullable();
            $table->string('turno', 30)->nullable();
        });
        Schema::table('matriculas', function (Blueprint $table): void {
            $table->string('estado_matricula', 30)->default('EFECTIVO');
        });
        Schema::table('notas', function (Blueprint $table): void {
            $table->foreignId('importacion_id')->nullable()->constrained('importaciones')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('notas', fn (Blueprint $table) => $table->dropConstrainedForeignId('importacion_id'));
        Schema::table('matriculas', fn (Blueprint $table) => $table->dropColumn('estado_matricula'));
        Schema::table('cursos', fn (Blueprint $table) => $table->dropColumn(['nivel', 'turno']));
        Schema::table('materias', fn (Blueprint $table) => $table->dropColumn(['campo', 'orden']));
        Schema::table('estudiantes', fn (Blueprint $table) => $table->dropColumn(['apellido_paterno', 'apellido_materno', 'genero', 'fecha_nacimiento', 'lugar_nacimiento', 'documento_identidad']));
    }
};
