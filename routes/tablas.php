<?php

use App\Http\Controllers\Tablas\TablasController;
use Illuminate\Support\Facades\Route;

Route::prefix('tablas')->name('tablas.')->group(function (): void {
    Route::get('/', [TablasController::class, 'index'])->name('index');
});
