<?php

use App\Http\Controllers\Admin\CrmDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::redirect('/admin', '/'.trim(config('crm.routes.admin_prefix', 'admin/crm'), '/'))
    ->name('admin.dashboard');

Route::prefix(config('crm.routes.admin_prefix', 'admin/crm'))
    ->name('crm.')
    ->group(function () {
        Route::get('/', CrmDashboardController::class)->name('dashboard');
    });
