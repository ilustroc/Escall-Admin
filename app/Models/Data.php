<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Data extends Model
{
    use HasFactory;

    protected $table = 'data';

    protected $fillable = [
        'dni', 'titular', 'cartera', 'cosecha', 'entidad', 
        'departamento', 'rango', 'capital', 'deuda_capital', 'producto'
    ];

    public $timestamps = false;
}