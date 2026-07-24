<?php

use App\Http\Controllers\Cargas\CargasController;
use App\Http\Controllers\Cargas\DataController;
use App\Http\Controllers\Cargas\GestionesSpController;
use App\Http\Controllers\Cargas\PagosController;
use Illuminate\Support\Facades\Route;

Route::prefix('cargas')->name('cargas.')->group(function (): void {
    Route::get('/', [CargasController::class, 'index'])->name('index');

    Route::get('data', [DataController::class, 'form'])->name('data.form');
    Route::post('data/upload', [DataController::class, 'upload'])->name('data.upload');
    Route::post('data/import-csv', [DataController::class, 'importarCsv'])->name('data.import.csv');
    Route::match(['get', 'post'], 'data/templateCsv', [DataController::class, 'templateCsv'])
        ->name('data.template.csv');

    Route::get('sp', [GestionesSpController::class, 'form'])->name('sp.form');
    Route::get('sp/preview', [GestionesSpController::class, 'preview'])->name('sp.preview');
    Route::post('sp/import', [GestionesSpController::class, 'import'])->name('sp.import');
    Route::match(['get', 'post'], 'sp/templateCsv', [GestionesSpController::class, 'templateCsv'])
        ->name('sp.template.csv');

    Route::get('pagos', [PagosController::class, 'form'])->name('pagos.form');
    Route::get('pagos/lookup', [PagosController::class, 'lookup'])->name('pagos.lookup');
    Route::post('pagos', [PagosController::class, 'store'])->name('pagos.store');
});
