<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsignacionExpertis extends Model
{
    use HasFactory;

    protected $table = 'asignaciones_expertis';

    protected $guarded = ['id'];

    protected $casts = [
        'periodo' => 'string',
        'deuda_total' => 'decimal:2',
        'deuda_capital' => 'decimal:2',
        'campania' => 'decimal:2',
        'porcentaje' => 'decimal:4',
        'fecha_nacimiento' => 'date',
        'edad' => 'integer',
        'entidades' => 'integer',
        'sueldo' => 'decimal:2',
        'anio_laboral' => 'integer',
        'anio_castigo' => 'integer',
        'numero_fila_origen' => 'integer',
    ];

    public function importacion(): BelongsTo
    {
        return $this->belongsTo(ImportacionExpertis::class, 'importacion_expertis_id');
    }
}
