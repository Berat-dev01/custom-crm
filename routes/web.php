<?php

use App\Http\Controllers\AdminAuthController;
use Illuminate\Support\Facades\Route;

use App\Crm\Http\Controllers\PublicQuoteController;

Route::get('/', function () {
    return redirect('/admin');
});

Route::prefix('quote')
    ->name('crm.public.quote.')
    ->middleware(['web', 'throttle:30,1'])
    ->group(function () {
        Route::get('/{token}', [PublicQuoteController::class, 'show'])->name('show');
        Route::post('/{token}/accept', [PublicQuoteController::class, 'accept'])->name('accept');
        Route::post('/{token}/reject', [PublicQuoteController::class, 'reject'])->name('reject');
        Route::get('/{token}/download', [PublicQuoteController::class, 'download'])->name('download');
    });
Route::prefix('admin')
    ->name('admin.')
    ->middleware('web')
    ->group(function () {
        Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'login'])->name('login.post');
        Route::get('/login/2fa', [AdminAuthController::class, 'showTwoFactorChallenge'])->name('login.2fa');
        Route::post('/login/2fa', [AdminAuthController::class, 'verifyTwoFactor'])->middleware('throttle:10,1')->name('login.2fa.post');
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::post('/locale', [AdminAuthController::class, 'updateLocale'])->name('locale.update');
        Route::get('/users', fn () => redirect()->route('crm.users.index'))->name('users.index');
        Route::get('/settings', [AdminAuthController::class, 'redirectToCrm'])->name('settings.index');
    });
