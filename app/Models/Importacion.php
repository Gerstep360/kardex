<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Importacion extends Model
{
    use HasFactory;

    protected $table = 'importaciones';

    protected $fillable = ['usuario_id', 'archivo', 'nombre_original', 'sha256', 'datos', 'estado', 'resumen', 'importada_at'];

    protected function casts(): array
    {
        return ['datos' => 'array', 'resumen' => 'array', 'importada_at' => 'datetime'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
