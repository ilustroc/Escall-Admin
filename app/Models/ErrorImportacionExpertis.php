<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErrorImportacionExpertis extends Model
{
    use HasFactory;

    protected $table = 'errores_importacion_expertis';

    protected $fillable = [
        'importacion_expertis_id',
        'numero_fila',
        'campo',
        'mensaje',
        'datos_originales',
    ];

    protected $casts = [
        'datos_originales' => 'array',
    ];

    public function importacion(): BelongsTo
    {
        return $this->belongsTo(ImportacionExpertis::class, 'importacion_expertis_id');
    }
}
