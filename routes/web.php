<?php

use App\Http\Controllers\LaporanExportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/laporan/export/excel', [LaporanExportController::class, 'excel'])
        ->name('laporan.export.excel');

    Route::get('/laporan/export/pdf', [LaporanExportController::class, 'pdf'])
        ->name('laporan.export.pdf');
});
