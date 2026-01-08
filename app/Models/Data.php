<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Data extends Model
{
    use HasFactory;

    protected $table = 'data';

    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = true;

    protected $fillable = [
        'codigo','dni','titular','cartera','entidad',
        'cosecha','sub_cartera','producto','sub_producto',
        'historico','departamento','deuda_total','deuda_capital',
        'campania','porcentaje',
    ];
}
