<?php

use App\Http\Controllers\Expertis\ExpertisDashboardController;
use App\Http\Controllers\Expertis\ExpertisReportController;
use App\Http\Controllers\Expertis\GestionExpertisController;
use App\Http\Controllers\Expertis\GestionExpertisImportController;
use App\Http\Controllers\Expertis\ImportacionExpertisController;
use App\Http\Controllers\Expertis\PagoExpertisController;
use App\Http\Controllers\Expertis\PagoExpertisImportController;
use App\Http\Controllers\Expertis\TipificacionExpertisController;
use Illuminate\Support\Facades\Route;

Route::prefix('expertis')->name('expertis.')->group(function (): void {
    Route::get('/', [ExpertisDashboardController::class, 'index'])->name('dashboard');

    Route::prefix('importaciones')->name('importaciones.')->group(function (): void {
        Route::get('/', [ImportacionExpertisController::class, 'index'])->name('index');
        Route::get('/gestiones', [GestionExpertisImportController::class, 'create'])
            ->name('gestiones.create');
        Route::post('/gestiones/preview', [GestionExpertisImportController::class, 'preview'])
            ->name('gestiones.preview');
        Route::post('/gestiones', [GestionExpertisImportController::class, 'store'])
            ->name('gestiones.store');
        Route::get('/pagos', [PagoExpertisImportController::class, 'create'])
            ->name('pagos.create');
        Route::post('/pagos/preview', [PagoExpertisImportController::class, 'preview'])
            ->name('pagos.preview');
        Route::post('/pagos', [PagoExpertisImportController::class, 'store'])
            ->name('pagos.store');
        Route::post('/pagos/manual', [PagoExpertisImportController::class, 'storeManual'])
            ->name('pagos.manual.store');
        Route::get('/{importacion}', [ImportacionExpertisController::class, 'show'])
            ->name('show');
        Route::get('/{importacion}/archivo', [ImportacionExpertisController::class, 'archivo'])
            ->name('archivo');
        Route::get('/{importacion}/errores', [ImportacionExpertisController::class, 'errores'])
            ->name('errores');
    });

    Route::get('/gestiones/export', [GestionExpertisController::class, 'export'])
        ->name('gestiones.export');
    Route::get('/gestiones', [GestionExpertisController::class, 'index'])
        ->name('gestiones.index');
    Route::get('/pagos/export', [PagoExpertisController::class, 'export'])
        ->name('pagos.export');
    Route::get('/pagos', [PagoExpertisController::class, 'index'])
        ->name('pagos.index');

    Route::get('/tipificaciones', [TipificacionExpertisController::class, 'index'])
        ->name('tipificaciones.index');
    Route::post('/tipificaciones', [TipificacionExpertisController::class, 'store'])
        ->name('tipificaciones.store');
    Route::put('/tipificaciones/{tipificacion}', [TipificacionExpertisController::class, 'update'])
        ->name('tipificaciones.update');
    Route::delete('/tipificaciones/{tipificacion}', [TipificacionExpertisController::class, 'destroy'])
        ->name('tipificaciones.destroy');

    Route::get('/reportes/gestiones/export', [ExpertisReportController::class, 'gestionesExport'])
        ->name('reportes.gestiones.export');
    Route::get('/reportes/pagos/export', [ExpertisReportController::class, 'pagosExport'])
        ->name('reportes.pagos.export');
    Route::get('/reportes', [ExpertisReportController::class, 'index'])
        ->name('reportes.index');
});
