<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Cargas\DataController;
use App\Http\Controllers\Cargas\GestionesSpController;
use App\Http\Controllers\Cargas\PagosController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Expertis\ExpertisDashboardController;
use App\Http\Controllers\Expertis\ExpertisReportController;
use App\Http\Controllers\Expertis\GestionExpertisController;
use App\Http\Controllers\Expertis\GestionExpertisImportController;
use App\Http\Controllers\Expertis\ImportacionExpertisController;
use App\Http\Controllers\Expertis\PagoExpertisController;
use App\Http\Controllers\Expertis\PagoExpertisImportController;
use App\Http\Controllers\Expertis\TipificacionExpertisController;
use App\Http\Controllers\ListasController;
use App\Http\Controllers\Reportes\ReporteCarterasController;
use App\Http\Controllers\Reportes\ReporteImpulseController;
use App\Http\Controllers\Reportes\ReporteKpInvestController;
use App\Http\Controllers\Tablas\TablasController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AUTENTICACIÓN
|--------------------------------------------------------------------------
*/
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'doLogin'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| PANEL ADMINISTRATIVO (Protegido)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    // 1. DASHBOARD
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // 2. MÓDULO CARGAS (Data, Gestiones, SP, Pagos)
    Route::prefix('cargas')->name('cargas.')->group(function () {
        // Vista Principal de Cargas
        Route::get('/', fn () => view('cargas.index'))->name('index');

        // Sub-módulo: Carga Data
        Route::get('data', [DataController::class, 'form'])->name('data.form');
        Route::post('data/upload', [DataController::class, 'upload'])->name('data.upload');
        Route::post('data/import-csv', [DataController::class, 'importarCsv'])->name('data.import.csv');
        Route::match(['get', 'post'], 'data/templateCsv', [DataController::class, 'templateCsv'])->name('data.template.csv');

        // Sub-módulo: Carga SP
        Route::get('sp', [GestionesSpController::class, 'form'])->name('sp.form');
        Route::get('sp/preview', [GestionesSpController::class, 'preview'])->name('sp.preview');
        Route::post('sp/import', [GestionesSpController::class, 'import'])->name('sp.import');
        Route::match(['get', 'post'], 'sp/templateCsv', [GestionesSpController::class, 'templateCsv'])->name('sp.template.csv');

        // Sub-módulo: Carga Pagos
        Route::get('pagos', [PagosController::class, 'form'])->name('pagos.form');
        Route::get('pagos/lookup', [PagosController::class, 'lookup'])->name('pagos.lookup');
        Route::post('pagos', [PagosController::class, 'store'])->name('pagos.store');
    });

    // 3. MÓDULO TABLAS
    Route::prefix('tablas')->name('tablas.')->group(function () {
        Route::get('/', [TablasController::class, 'index'])->name('index');
    });

    // 4. MÓDULO REPORTES
    Route::prefix('reportes')->name('reportes.')->group(function () {
        Route::get('/', fn () => view('reportes.index'))->name('index');

        // Reportes Específicos
        Route::get('/impulse', [ReporteImpulseController::class, 'index'])->name('impulse.index');
        Route::get('/impulse/export', [ReporteImpulseController::class, 'export'])->name('impulse.export');

        Route::get('/kp-invest', [ReporteKpInvestController::class, 'index'])->name('kp.index');
        Route::get('/kp-invest/export', [ReporteKpInvestController::class, 'export'])->name('kp.export');

        Route::get('/tec-center-data', [ReporteCarterasController::class, 'exportTecCenterData'])->name('tec.data'); // Movido aquí por lógica

        // Reportes Consolidados / Carteras
        Route::get('/carteras', [ReporteCarterasController::class, 'index'])->name('carteras.index');
        Route::get('/carteras/export-data-xlsx', [ReporteCarterasController::class, 'exportDataXlsxFast'])->name('carteras.exportDataXlsxFast');
        Route::get('/carteras/export-tec', [ReporteCarterasController::class, 'exportAsignacionTec'])->name('carteras.exportTec');
        Route::get('/data-tec-center', [ReporteCarterasController::class, 'exportDataTecCenter'])->name('carteras.exportDataTecCenter');
    });

    // 5. MÓDULO LISTAS (CRUD)
    Route::prefix('listas')->name('listas.')->group(function () {
        Route::get('/', [ListasController::class, 'index'])->name('index');
        Route::put('/update', [ListasController::class, 'update'])->name('update');
        Route::delete('/delete', [ListasController::class, 'destroy'])->name('destroy');
        Route::get('/export', [ListasController::class, 'export'])->name('export');
    });

    Route::prefix('expertis')->name('expertis.')->group(function () {
        Route::get('/', [ExpertisDashboardController::class, 'index'])->name('dashboard');

        Route::prefix('importaciones')->name('importaciones.')->group(function () {
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
});
