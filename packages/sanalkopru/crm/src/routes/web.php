<?php

use Illuminate\Support\Facades\Route;
use Sanalkopru\Crm\Http\Controllers\Admin\ActivitiesController;
use Sanalkopru\Crm\Http\Controllers\Admin\AiController;
use Sanalkopru\Crm\Http\Controllers\Admin\CompaniesController;
use Sanalkopru\Crm\Http\Controllers\Admin\ContactsController;
use Sanalkopru\Crm\Http\Controllers\Admin\DataTransferController;
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

                Route::get('imports/{import:public_id}/errors', [DataTransferController::class, 'errors'])->name('imports.errors');

                Route::get('contacts/export', [DataTransferController::class, 'export'])->defaults('module', 'contacts')->name('contacts.export');
                Route::get('contacts/import', [DataTransferController::class, 'importForm'])->defaults('module', 'contacts')->name('contacts.import');
                Route::get('contacts/import/template', [DataTransferController::class, 'template'])->defaults('module', 'contacts')->name('contacts.template');
                Route::post('contacts/import/preview', [DataTransferController::class, 'preview'])->defaults('module', 'contacts')->name('contacts.import.preview');
                Route::post('contacts/import', [DataTransferController::class, 'import'])->defaults('module', 'contacts')->name('contacts.import.store');
                Route::delete('contacts/bulk-delete', [ContactsController::class, 'bulkDelete'])->name('contacts.bulk-delete');
                Route::post('contacts/bulk-tags', [ContactsController::class, 'bulkTags'])->name('contacts.bulk-tags');
                Route::post('contacts/{contact}/notes', [ContactsController::class, 'storeNote'])->name('contacts.notes.store');
                Route::resource('contacts', ContactsController::class);
                Route::get('companies/export', [DataTransferController::class, 'export'])->defaults('module', 'companies')->name('companies.export');
                Route::get('companies/import', [DataTransferController::class, 'importForm'])->defaults('module', 'companies')->name('companies.import');
                Route::get('companies/import/template', [DataTransferController::class, 'template'])->defaults('module', 'companies')->name('companies.template');
                Route::post('companies/import/preview', [DataTransferController::class, 'preview'])->defaults('module', 'companies')->name('companies.import.preview');
                Route::post('companies/import', [DataTransferController::class, 'import'])->defaults('module', 'companies')->name('companies.import.store');
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
                Route::get('deals/export', [DataTransferController::class, 'export'])->defaults('module', 'deals')->name('deals.export');
                Route::get('deals/import', [DataTransferController::class, 'importForm'])->defaults('module', 'deals')->name('deals.import');
                Route::get('deals/import/template', [DataTransferController::class, 'template'])->defaults('module', 'deals')->name('deals.template');
                Route::post('deals/import/preview', [DataTransferController::class, 'preview'])->defaults('module', 'deals')->name('deals.import.preview');
                Route::post('deals/import', [DataTransferController::class, 'import'])->defaults('module', 'deals')->name('deals.import.store');
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
                Route::get('quotes/export', [DataTransferController::class, 'export'])->defaults('module', 'quotes')->name('quotes.export');
                Route::resource('quotes', QuotesController::class);
                Route::resource('activities', ActivitiesController::class);
                Route::post('tags/bulk', [TagsController::class, 'bulk'])->name('tags.bulk');
                Route::resource('tags', TagsController::class);
                Route::post('saved-filters', [SavedFiltersController::class, 'store'])->name('saved-filters.store');
                Route::get('saved-filters/{savedFilter}/apply', [SavedFiltersController::class, 'apply'])->name('saved-filters.apply');
                Route::delete('saved-filters/{savedFilter}', [SavedFiltersController::class, 'destroy'])->name('saved-filters.destroy');

                Route::post('ai/summarize', [AiController::class, 'summarize'])->name('ai.summarize');
                Route::post('ai/summarize-note', [AiController::class, 'summarizeNote'])->name('ai.summarize-note');
                Route::post('ai/draft-email', [AiController::class, 'draftEmail'])->name('ai.draft-email');
                Route::post('ai/follow-up', [AiController::class, 'followUp'])->name('ai.follow-up');
            });
    });
