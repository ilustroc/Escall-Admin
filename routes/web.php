<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Cargas\GestionesController;
use App\Http\Controllers\Cargas\GestionesSpController;
use App\Http\Controllers\Cargas\DataController;
use App\Http\Controllers\Cargas\PagosController;
use App\Http\Controllers\Tablas\GestionesMesController;
use App\Http\Controllers\Tablas\GestionesSemanalController;
use App\Http\Controllers\Reportes\ReporteImpulseController;
use App\Http\Controllers\Reportes\ReporteKpInvestController;
use App\Http\Controllers\Reportes\ReporteTecCenterController;
use App\Http\Controllers\Reportes\ReporteCarterasController;

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
        Route::get('/', fn() => view('cargas.index'))->name('index');

        // Sub-módulo: Carga Data
        Route::get('data',              [DataController::class, 'form'])->name('data.form');
        Route::post('data/upload',      [DataController::class, 'upload'])->name('data.upload');
        Route::post('data/import-csv',  [DataController::class, 'importarCsv'])->name('data.import.csv');
        Route::match(['get', 'post'], 'data/templateCsv', [DataController::class, 'templateCsv'])->name('data.template.csv');

        // Sub-módulo: Carga Gestiones
        Route::get('gestiones',         [GestionesController::class, 'form'])->name('gestiones.form');
        Route::post('gestiones',        [GestionesController::class, 'upload'])->name('gestiones.upload');
        Route::match(['get', 'post'], 'gestiones/templateCsv', [GestionesController::class, 'templateCsv'])->name('gestiones.template.csv');

        // Sub-módulo: Carga SP
        Route::get('sp',                [GestionesSpController::class, 'form'])->name('sp.form');
        Route::get('sp/preview',        [GestionesSpController::class, 'preview'])->name('sp.preview');
        Route::post('sp/import',        [GestionesSpController::class, 'import'])->name('sp.import');
        Route::match(['get', 'post'], 'sp/templateCsv', [GestionesSpController::class, 'templateCsv'])->name('sp.template.csv');

        // Sub-módulo: Carga Pagos
        Route::get('pagos',             [PagosController::class, 'form'])->name('pagos.form');
        Route::get('pagos/lookup',      [PagosController::class, 'lookup'])->name('pagos.lookup');
        Route::post('pagos',            [PagosController::class, 'store'])->name('pagos.store');
    });

    // 3. MÓDULO TABLAS
    Route::prefix('tablas')->name('tablas.')->group(function () {
        Route::get('/',                 [GestionesMesController::class, 'index'])->name('index');
        Route::get('/gestiones-mes',    [GestionesMesController::class, 'index'])->name('gestiones.mes');
        Route::get('/semanales',        [GestionesSemanalController::class, 'index'])->name('semanales');
    });

    // 4. MÓDULO REPORTES
    Route::prefix('reportes')->name('reportes.')->group(function () {
        Route::get('/', fn() => view('reportes.index'))->name('index');

        // Reportes Específicos
        Route::get('/impulse',              [ReporteImpulseController::class, 'index'])->name('impulse.index');
        Route::get('/impulse/export',       [ReporteImpulseController::class, 'export'])->name('impulse.export');
        
        Route::get('/kp-invest',            [ReporteKpInvestController::class, 'index'])->name('kp.index');
        Route::get('/kp-invest/export',     [ReporteKpInvestController::class, 'export'])->name('kp.export');
        
        Route::get('/tec-center',           [ReporteTecCenterController::class, 'index'])->name('tec.index');
        Route::get('/tec-center/export',    [ReporteTecCenterController::class, 'export'])->name('tec.export');
        Route::get('/tec-center-data',      [ReporteCarterasController::class, 'exportTecCenterData'])->name('tec.data'); // Movido aquí por lógica

        // Reportes Consolidados / Carteras
        Route::get('/carteras',                     [ReporteCarterasController::class, 'index'])->name('carteras.index');
        Route::get('/carteras/export-data-xlsx',    [ReporteCarterasController::class, 'exportDataXlsxFast'])->name('carteras.exportDataXlsxFast');
        Route::get('/carteras/export-tec',          [ReporteCarterasController::class, 'exportAsignacionTec'])->name('carteras.exportTec');
        Route::get('/data-tec-center',              [ReporteCarterasController::class, 'exportDataTecCenter'])->name('carteras.exportDataTecCenter');
    });

    // 5. MÓDULO SMS
    Route::get('/sms', fn() => view('sms.index'))->name('sms.index');

});