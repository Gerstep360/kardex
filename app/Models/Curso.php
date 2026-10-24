<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Curso extends Model
{
    use HasFactory;

    protected $table = 'cursos';

    protected $fillable = [
        'nombre',
        'paralelo',
        'gestion',
    ];

    protected function casts(): array
    {
        return [
            'gestion' => 'integer',
        ];
    }

    public function matriculas(): HasMany
    {
        return $this->hasMany(Matricula::class);
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(Asignacion::class);
    }

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombre} \"{$this->paralelo}\" ({$this->gestion})";
    }
}
