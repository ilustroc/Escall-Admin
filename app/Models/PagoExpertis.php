<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoExpertis extends Model
{
    use HasFactory;

    protected $table = 'pagos_expertis';

    protected $guarded = ['id'];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
        'numero_fila_origen' => 'integer',
    ];

    public function importacion(): BelongsTo
    {
        return $this->belongsTo(ImportacionExpertis::class, 'importacion_expertis_id');
    }
}
