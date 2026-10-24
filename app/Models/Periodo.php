<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Periodo extends Model
{
    use HasFactory;

    protected $table = 'periodos';

    protected $fillable = [
        'nombre',
        'numero',
        'gestion',
    ];

    protected function casts(): array
    {
        return [
            'numero' => 'integer',
            'gestion' => 'integer',
        ];
    }

    public function notas(): HasMany
    {
        return $this->hasMany(Nota::class);
    }
}
