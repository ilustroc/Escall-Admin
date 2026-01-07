<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gestion extends Model
{
    use HasFactory;

    // Nombre de la tabla en tu BD
    protected $table = 'gestiones';

    // Campos que se pueden llenar masivamente
    protected $fillable = [
        'fecha_gestion',
        'dni',
        'telefono',
        'status',
        'tipificacion',
        'observacion',
        'fecha_pago',
        'monto_pago',
        'nombre',
    ];

    // Conversión automática de tipos
    protected $casts = [
        'fecha_gestion' => 'date', // Laravel lo tratará como objeto Carbon
        'fecha_pago'    => 'date', // Laravel lo tratará como objeto Carbon
        'monto_pago'    => 'integer', // En tu BD es int(11)
    ];
}