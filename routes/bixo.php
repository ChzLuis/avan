<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    // BIXO Screens - Design Prototypes
    Route::get('/bixo/screen-1', fn() => view('bixo.screen-1-business-center'))->name('bixo.screen1');
    Route::get('/bixo/screen-2', fn() => view('bixo.screen-2-capability-operar'))->name('bixo.screen2');
    Route::get('/bixo/screen-3', fn() => view('bixo.screen-3-tool-inventario'))->name('bixo.screen3');
    Route::get('/bixo/screen-4', fn() => view('bixo.screen-4-object-producto'))->name('bixo.screen4');
    Route::get('/bixo/screen-5', fn() => view('bixo.screen-5-business-switch'))->name('bixo.screen5');
    Route::get('/bixo/demo', fn() => view('bixo.demo-prototype'))->name('bixo.demo');
});
