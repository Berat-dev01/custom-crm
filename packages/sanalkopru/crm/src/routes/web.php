<?php

use Illuminate\Support\Facades\Route;
use Sanalkopru\Crm\Http\Controllers\Admin\ActivitiesController;
use Sanalkopru\Crm\Http\Controllers\Admin\AiController;
use Sanalkopru\Crm\Http\Controllers\Admin\CompaniesController;
use Sanalkopru\Crm\Http\Controllers\Admin\ContactsController;
use Sanalkopru\Crm\Http\Controllers\Admin\DealsController;
use Sanalkopru\Crm\Http\Controllers\Admin\DealStagesController;
use Sanalkopru\Crm\Http\Controllers\Admin\QuotesController;
use Sanalkopru\Crm\Http\Controllers\Admin\SavedFiltersController;
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
                Route::post('deal-stages/reorder', [DealStagesController::class, 'reorder'])->name('deal-stages.reorder');
                Route::resource('deal-stages', DealStagesController::class)->except('show');
                Route::post('deals/{deal}/tasks', [DealsController::class, 'storeTask'])->name('deals.tasks.store');
                Route::post('deals/{deal}/quotes', [DealsController::class, 'storeQuote'])->name('deals.quotes.store');
                Route::post('deals/{deal}/activities', [DealsController::class, 'storeActivity'])->name('deals.activities.store');
                Route::patch('deals/{deal}/stage', [DealsController::class, 'stage'])->name('deals.stage');
                Route::patch('deals/{deal}/close-won', [DealsController::class, 'closeWon'])->name('deals.close-won');
                Route::patch('deals/{deal}/close-lost', [DealsController::class, 'closeLost'])->name('deals.close-lost');
                Route::patch('deals/{deal}/move', [DealsController::class, 'move'])->name('deals.move');
                Route::resource('deals', DealsController::class);
                Route::get('tasks/my', [TasksController::class, 'my'])->name('tasks.my');
                Route::get('tasks/overdue', [TasksController::class, 'overdue'])->name('tasks.overdue');
                Route::get('tasks/today', [TasksController::class, 'today'])->name('tasks.today');
                Route::patch('tasks/{task}/complete', [TasksController::class, 'complete'])->name('tasks.complete');
                Route::resource('tasks', TasksController::class);
                Route::patch('quotes/{quote}/send', [QuotesController::class, 'send'])->name('quotes.send');
                Route::patch('quotes/{quote}/accept', [QuotesController::class, 'accept'])->name('quotes.accept');
                Route::patch('quotes/{quote}/reject', [QuotesController::class, 'reject'])->name('quotes.reject');
                Route::patch('quotes/{quote}/expire', [QuotesController::class, 'expire'])->name('quotes.expire');
                Route::post('quotes/{quote}/duplicate', [QuotesController::class, 'duplicate'])->name('quotes.duplicate');
                Route::get('quotes/{quote}/preview', [QuotesController::class, 'preview'])->name('quotes.preview');
                Route::get('quotes/{quote}/download', [QuotesController::class, 'download'])->name('quotes.download');
                Route::resource('quotes', QuotesController::class);
                Route::resource('activities', ActivitiesController::class);
                Route::post('tags/bulk', [TagsController::class, 'bulk'])->name('tags.bulk');
                Route::resource('tags', TagsController::class);
                Route::post('saved-filters', [SavedFiltersController::class, 'store'])->name('saved-filters.store');
                Route::get('saved-filters/{savedFilter}/apply', [SavedFiltersController::class, 'apply'])->name('saved-filters.apply');
                Route::delete('saved-filters/{savedFilter}', [SavedFiltersController::class, 'destroy'])->name('saved-filters.destroy');

                Route::post('ai/summarize-note', [AiController::class, 'summarizeNote'])->name('ai.summarize-note');
                Route::post('ai/draft-email', [AiController::class, 'draftEmail'])->name('ai.draft-email');
            });
    });
