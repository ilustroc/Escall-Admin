<?php

use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

Route::middleware('auth')->group(function (): void {
    require __DIR__.'/dashboard.php';
    require __DIR__.'/cargas.php';
    require __DIR__.'/tablas.php';
    require __DIR__.'/reportes.php';
    require __DIR__.'/listas.php';
    require __DIR__.'/expertis.php';
});
