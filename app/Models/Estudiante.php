<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estudiante extends Model
{
    use HasFactory;

    public const DATOS_OBLIGATORIOS = [
        'codigo' => 'Código RUDE',
        'nombres' => 'Nombres',
        'apellido_paterno' => 'Apellido paterno',
        'apellido_materno' => 'Apellido materno',
        'genero' => 'Género',
        'fecha_nacimiento' => 'Fecha de nacimiento',
        'lugar_nacimiento' => 'Lugar de nacimiento',
        'documento_identidad' => 'Número de C.I.',
    ];

    public function datosFaltantes(): array
    {
        return array_filter(self::DATOS_OBLIGATORIOS, fn ($etiqueta, $campo) => blank($this->getAttribute($campo)), ARRAY_FILTER_USE_BOTH);
    }

    protected $table = 'estudiantes';

    protected $fillable = [
        'codigo',
        'nombres',
        'apellidos',
        'estado',
        'apellido_paterno',
        'apellido_materno',
        'genero',
        'fecha_nacimiento',
        'lugar_nacimiento',
        'documento_identidad',
    ];

    protected function casts(): array
    {
        return ['fecha_nacimiento' => 'date'];
    }

    public function matriculas(): HasMany
    {
        return $this->hasMany(Matricula::class);
    }

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->apellidos}, {$this->nombres}";
    }

    public function matriculaPorGestion(?int $gestion = null)
    {
        $gestion = $gestion ?? date('Y');

        return $this->matriculas()->where('gestion', $gestion)->first();
    }
}
