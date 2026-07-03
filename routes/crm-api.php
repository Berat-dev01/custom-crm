<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Crm\Http\Controllers\Api\ActivitiesController;
use App\Crm\Http\Controllers\Api\CompaniesController;
use App\Crm\Http\Controllers\Api\ContactsController;
use App\Crm\Http\Controllers\Api\DealsController;
use App\Crm\Http\Controllers\Api\DealStagesController;
use App\Crm\Http\Controllers\Api\HealthController;
use App\Crm\Http\Controllers\Api\QuotesController;
use App\Crm\Http\Controllers\Api\TagsController;
use App\Crm\Http\Controllers\Api\TasksController;

Route::prefix('crm/v1')
    ->name('crm.api.')
    ->group(function () {
        Route::get('/health', HealthController::class)->name('health');

        Route::middleware(['crm.api.auth', 'throttle:crm-api'])->group(function () {
            Route::apiResource('contacts', ContactsController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
            Route::apiResource('companies', CompaniesController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
            Route::apiResource('deals', DealsController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
            Route::post('deals/{deal}/move', [DealsController::class, 'move'])->name('deals.move');
            Route::get('deal-stages', [DealStagesController::class, 'index'])->name('deal-stages.index');
            Route::apiResource('tasks', TasksController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
            Route::post('tasks/{task}/complete', [TasksController::class, 'complete'])->name('tasks.complete');
            Route::apiResource('quotes', QuotesController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
            Route::apiResource('activities', ActivitiesController::class)->only(['index', 'store']);
            Route::get('tags', [TagsController::class, 'index'])->name('tags.index');
        });
    });

// Legacy unversioned paths permanently redirect to v1 (308 keeps the method).
Route::any('crm/{path}', function (Request $request, string $path) {
    $query = $request->getQueryString();

    return redirect('/api/crm/v1/'.$path.($query ? '?'.$query : ''), 308);
})->where('path', '^(?!v1(/|$)).*');
