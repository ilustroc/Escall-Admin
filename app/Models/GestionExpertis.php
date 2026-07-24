<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GestionExpertis extends Model
{
    use HasFactory;

    protected $table = 'gestiones_expertis';

    protected $guarded = ['id'];

    protected $casts = [
        'fecha_llamada' => 'date',
        'fecha_hora' => 'datetime',
        'fecha_compromiso' => 'date',
        'monto' => 'decimal:2',
        'numero_fila_origen' => 'integer',
    ];

    public function importacion(): BelongsTo
    {
        return $this->belongsTo(ImportacionExpertis::class, 'importacion_expertis_id');
    }

    public function tipificacion(): BelongsTo
    {
        return $this->belongsTo(TipificacionExpertis::class, 'tipificacion_expertis_id');
    }
}
