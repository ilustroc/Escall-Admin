<?php

use App\Http\Controllers\ListasController;
use Illuminate\Support\Facades\Route;

Route::prefix('listas')->name('listas.')->group(function (): void {
    Route::get('/', [ListasController::class, 'index'])->name('index');
    Route::put('/update', [ListasController::class, 'update'])->name('update');
    Route::delete('/delete', [ListasController::class, 'destroy'])->name('destroy');
    Route::get('/export', [ListasController::class, 'export'])->name('export');
});
