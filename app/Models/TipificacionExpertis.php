<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipificacionExpertis extends Model
{
    use HasFactory;

    protected $table = 'tipificaciones_expertis';

    protected $fillable = [
        'tipificacion',
        'gestion',
        'peso',
        'observacion',
        'activo',
    ];

    protected $casts = [
        'peso' => 'integer',
        'activo' => 'boolean',
    ];

    public function gestiones(): HasMany
    {
        return $this->hasMany(GestionExpertis::class);
    }
}
