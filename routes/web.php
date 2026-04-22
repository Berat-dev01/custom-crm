<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::prefix('admin')
    ->name('admin.')
    ->middleware('web')
    ->group(function () {
        Route::get('/users', fn () => redirect()->route('crm.dashboard'))->name('users.index');
        Route::get('/settings', fn () => redirect()->route('crm.dashboard'))->name('settings.index');
        Route::post('/locale', fn () => back())->name('locale.update');
        Route::post('/logout', fn () => redirect()->route('home'))->name('logout');
    });
