<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estudiante extends Model
{
    use HasFactory;

    protected $table = 'estudiantes';

    protected $fillable = [
        'codigo',
        'nombres',
        'apellidos',
        'estado',
    ];

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
