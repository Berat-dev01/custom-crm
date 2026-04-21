<?php

use Illuminate\Support\Facades\Route;

Route::prefix('crm')
    ->name('crm.api.')
    ->group(function () {
        Route::get('/health', fn () => response()->json(['status' => 'ok']))
            ->name('health');
    });
