<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    use HasFactory;

    // Nombre de la tabla en tu BD
    protected $table = 'pagos';

    // Campos que se pueden llenar masivamente
    protected $fillable = [
        'codigo',
        'asesor',
        'fecha',
        'monto',
        'operacion',
        // Snapshot de cliente (datos copiados al momento del pago)
        'dni',
        'nombre',
        'cartera',
        'entidad',
        'cosecha',
        'departamento',
        'rango',
        'capital',
        'producto',
    ];

    // Conversión automática de tipos
    protected $casts = [
        'fecha'   => 'date',       // Para manipular fechas fácil
        'monto'   => 'decimal:2',  // Asegura 2 decimales al traerlo
        'capital' => 'decimal:2',  // Asegura 2 decimales al traerlo
    ];
}