<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportacionExpertis extends Model
{
    use HasFactory;

    public const TIPO_GESTIONES = 'gestiones';

    public const TIPO_PAGOS = 'pagos';

    public const TIPO_ASIGNACIONES = 'asignaciones';

    public const TIPOS = [
        self::TIPO_ASIGNACIONES,
        self::TIPO_GESTIONES,
        self::TIPO_PAGOS,
    ];

    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_VALIDANDO = 'validando';

    public const ESTADO_PROCESANDO = 'procesando';

    public const ESTADO_COMPLETADO = 'completado';

    public const ESTADO_COMPLETADO_CON_ERRORES = 'completado_con_errores';

    public const ESTADO_FALLIDO = 'fallido';

    public const ESTADO_DUPLICADO = 'duplicado';

    public const ESTADOS_ARCHIVO_PROCESADO = [
        self::ESTADO_COMPLETADO,
        self::ESTADO_COMPLETADO_CON_ERRORES,
        self::ESTADO_DUPLICADO,
    ];

    protected $table = 'importaciones_expertis';

    protected $fillable = [
        'user_id',
        'tipo',
        'nombre_original',
        'nombre_guardado',
        'ruta_archivo',
        'hash_archivo',
        'estado',
        'total_filas',
        'filas_insertadas',
        'filas_actualizadas',
        'filas_duplicadas',
        'filas_error',
        'fecha_minima',
        'fecha_maxima',
        'iniciado_at',
        'finalizado_at',
        'resumen',
        'mensaje_error',
    ];

    protected $casts = [
        'fecha_minima' => 'date',
        'fecha_maxima' => 'date',
        'iniciado_at' => 'datetime',
        'finalizado_at' => 'datetime',
        'resumen' => 'array',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function errores(): HasMany
    {
        return $this->hasMany(ErrorImportacionExpertis::class);
    }

    public function gestiones(): HasMany
    {
        return $this->hasMany(GestionExpertis::class);
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(AsignacionExpertis::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(PagoExpertis::class);
    }
}
