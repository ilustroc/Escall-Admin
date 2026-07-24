<?php

use App\Http\Controllers\Reportes\ReporteCarterasController;
use App\Http\Controllers\Reportes\ReporteImpulseController;
use App\Http\Controllers\Reportes\ReporteKpInvestController;
use App\Http\Controllers\Reportes\ReportesController;
use Illuminate\Support\Facades\Route;

Route::prefix('reportes')->name('reportes.')->group(function (): void {
    Route::get('/', [ReportesController::class, 'index'])->name('index');

    Route::get('/impulse', [ReporteImpulseController::class, 'index'])->name('impulse.index');
    Route::get('/impulse/export', [ReporteImpulseController::class, 'export'])->name('impulse.export');

    Route::get('/kp-invest', [ReporteKpInvestController::class, 'index'])->name('kp.index');
    Route::get('/kp-invest/export', [ReporteKpInvestController::class, 'export'])->name('kp.export');

    Route::get('/tec-center-data', [ReporteCarterasController::class, 'exportTecCenterData'])
        ->name('tec.data');
    Route::get('/carteras', [ReporteCarterasController::class, 'index'])->name('carteras.index');
    Route::get('/carteras/export-data-xlsx', [ReporteCarterasController::class, 'exportDataXlsxFast'])
        ->name('carteras.exportDataXlsxFast');
    Route::get('/carteras/export-tec', [ReporteCarterasController::class, 'exportAsignacionTec'])
        ->name('carteras.exportTec');
    Route::get('/data-tec-center', [ReporteCarterasController::class, 'exportDataTecCenter'])
        ->name('carteras.exportDataTecCenter');
});
