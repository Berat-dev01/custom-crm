<?php

use Illuminate\Support\Facades\Route;
use Sanalkopru\Crm\Http\Controllers\Admin\ActivitiesController;
use Sanalkopru\Crm\Http\Controllers\Admin\AiController;
use Sanalkopru\Crm\Http\Controllers\Admin\CompaniesController;
use Sanalkopru\Crm\Http\Controllers\Admin\ContactsController;
use Sanalkopru\Crm\Http\Controllers\Admin\DealsController;
use Sanalkopru\Crm\Http\Controllers\Admin\QuotesController;
use Sanalkopru\Crm\Http\Controllers\Admin\TagsController;
use Sanalkopru\Crm\Http\Controllers\Admin\TasksController;
use Sanalkopru\Crm\Http\Controllers\DashboardController;

Route::middleware(config('crm.routes.middleware', ['web']))
    ->group(function () {
        Route::redirect('/admin', '/'.trim(config('crm.routes.admin_prefix', 'admin/crm'), '/'))
            ->name('admin.dashboard');

        Route::prefix(config('crm.routes.admin_prefix', 'admin/crm'))
            ->name('crm.')
            ->middleware('crm.access')
            ->group(function () {
                Route::get('/', DashboardController::class)->name('dashboard');

                Route::get('contacts/export', [ContactsController::class, 'export'])->name('contacts.export');
                Route::get('contacts/import', [ContactsController::class, 'importForm'])->name('contacts.import');
                Route::post('contacts/import', [ContactsController::class, 'import'])->name('contacts.import.store');
                Route::delete('contacts/bulk-delete', [ContactsController::class, 'bulkDelete'])->name('contacts.bulk-delete');
                Route::post('contacts/bulk-tags', [ContactsController::class, 'bulkTags'])->name('contacts.bulk-tags');
                Route::post('contacts/{contact}/notes', [ContactsController::class, 'storeNote'])->name('contacts.notes.store');
                Route::resource('contacts', ContactsController::class);
                Route::post('companies/{company}/contacts', [CompaniesController::class, 'attachContacts'])->name('companies.contacts.attach');
                Route::resource('companies', CompaniesController::class);
                Route::patch('deals/{deal}/move', [DealsController::class, 'move'])->name('deals.move');
                Route::resource('deals', DealsController::class);
                Route::resource('tasks', TasksController::class);
                Route::resource('quotes', QuotesController::class);
                Route::resource('activities', ActivitiesController::class);
                Route::resource('tags', TagsController::class);

                Route::post('ai/summarize-note', [AiController::class, 'summarizeNote'])->name('ai.summarize-note');
                Route::post('ai/draft-email', [AiController::class, 'draftEmail'])->name('ai.draft-email');
            });
    });
