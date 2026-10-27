<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'usuarios';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'usuario',
        'password',
        'rol',
        'activo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(Asignacion::class, 'docente_id');
    }

    public function esAdministrador(): bool
    {
        return $this->rol === 'administrador';
    }

    public function esDocente(): bool
    {
        return $this->rol === 'docente';
    }

    public function esSecretaria(): bool
    {
        return $this->rol === 'secretaria';
    }

    public function puedeConsultarBoletines(): bool
    {
        return $this->activo && in_array($this->rol, ['administrador', 'secretaria'], true);
    }
}
