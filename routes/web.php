<?php

use App\Http\Controllers\AdminAuthController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::prefix('admin')
    ->name('admin.')
    ->middleware('web')
    ->group(function () {
        Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'login'])->name('login.post');
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::post('/locale', [AdminAuthController::class, 'updateLocale'])->name('locale.update');
        Route::get('/users', fn () => redirect()->route('crm.users.index'))->name('users.index');
        Route::get('/settings', [AdminAuthController::class, 'redirectToCrm'])->name('settings.index');
    });
