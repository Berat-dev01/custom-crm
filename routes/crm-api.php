<?php

use Illuminate\Support\Facades\Route;
use App\Crm\Http\Controllers\Api\CompaniesController;
use App\Crm\Http\Controllers\Api\ContactsController;
use App\Crm\Http\Controllers\Api\DealsController;
use App\Crm\Http\Controllers\Api\HealthController;
use App\Crm\Http\Controllers\Api\QuotesController;
use App\Crm\Http\Controllers\Api\TasksController;

Route::prefix('crm')
    ->name('crm.api.')
    ->group(function () {
        Route::get('/health', HealthController::class)->name('health');

        Route::middleware(['crm.api.auth', 'throttle:crm-api'])->group(function () {
            Route::apiResource('contacts', ContactsController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
            Route::apiResource('companies', CompaniesController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
            Route::apiResource('deals', DealsController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
            Route::post('deals/{deal}/move', [DealsController::class, 'move'])->name('deals.move');
            Route::apiResource('tasks', TasksController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
            Route::post('tasks/{task}/complete', [TasksController::class, 'complete'])->name('tasks.complete');
            Route::apiResource('quotes', QuotesController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
        });
    });
