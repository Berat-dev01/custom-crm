<?php

use Illuminate\Support\Facades\Route;
use App\Crm\Http\Controllers\Admin\ActivitiesController;
use App\Crm\Http\Controllers\Admin\AiController;
use App\Crm\Http\Controllers\Admin\CompaniesController;
use App\Crm\Http\Controllers\Admin\ContactsController;
use App\Crm\Http\Controllers\Admin\DataTransferController;
use App\Crm\Http\Controllers\Admin\DealsController;
use App\Crm\Http\Controllers\Admin\DealStagesController;
use App\Crm\Http\Controllers\Admin\NotificationsController;
use App\Crm\Http\Controllers\Admin\QuotesController;
use App\Crm\Http\Controllers\Admin\SavedFiltersController;
use App\Crm\Http\Controllers\Admin\SearchController;
use App\Crm\Http\Controllers\Admin\ApiTokensController;
use App\Crm\Http\Controllers\Admin\AuditLogsController;
use App\Crm\Http\Controllers\Admin\SettingsController;
use App\Crm\Http\Controllers\Admin\TagsController;
use App\Crm\Http\Controllers\Admin\TasksController;
use App\Crm\Http\Controllers\Admin\UsersController;
use App\Crm\Http\Controllers\DashboardController;

Route::middleware(config('crm.routes.middleware', ['web']))
    ->group(function () {
        Route::redirect('/admin', '/'.trim(config('crm.routes.admin_prefix', 'admin/crm'), '/'))
            ->name('admin.dashboard');

        Route::prefix(config('crm.routes.admin_prefix', 'admin/crm'))
            ->name('crm.')
            ->middleware(['crm.access', 'throttle:240,1'])
            ->group(function () {
                Route::get('/', DashboardController::class)->name('dashboard');
                Route::get('search', SearchController::class)->name('search');
                Route::get('notifications', [NotificationsController::class, 'index'])->name('notifications.index');
                Route::get('notifications/all', [NotificationsController::class, 'page'])->name('notifications.page');
                Route::post('notifications/read-all', [NotificationsController::class, 'readAll'])->name('notifications.read-all');
                Route::put('notifications/preferences', [NotificationsController::class, 'preferences'])->name('notifications.preferences');
                Route::post('notifications/{notification}/read', [NotificationsController::class, 'read'])->name('notifications.read');

                Route::get('imports/{import:public_id}/errors', [DataTransferController::class, 'errors'])->name('imports.errors');

                Route::post('contacts/export', [DataTransferController::class, 'export'])->defaults('module', 'contacts')->name('contacts.export');
                Route::get('contacts/import', [DataTransferController::class, 'importForm'])->defaults('module', 'contacts')->name('contacts.import');
                Route::get('contacts/import/template', [DataTransferController::class, 'template'])->defaults('module', 'contacts')->name('contacts.template');
                Route::post('contacts/import/preview', [DataTransferController::class, 'preview'])->defaults('module', 'contacts')->name('contacts.import.preview');
                Route::post('contacts/import', [DataTransferController::class, 'import'])->defaults('module', 'contacts')->name('contacts.import.store');
                Route::delete('contacts/bulk-delete', [ContactsController::class, 'bulkDelete'])->name('contacts.bulk-delete');
                Route::post('contacts/bulk-tags', [ContactsController::class, 'bulkTags'])->name('contacts.bulk-tags');
                Route::post('contacts/{contact}/notes', [ContactsController::class, 'storeNote'])->name('contacts.notes.store');
                Route::resource('contacts', ContactsController::class);
                Route::post('companies/export', [DataTransferController::class, 'export'])->defaults('module', 'companies')->name('companies.export');
                Route::get('companies/import', [DataTransferController::class, 'importForm'])->defaults('module', 'companies')->name('companies.import');
                Route::get('companies/import/template', [DataTransferController::class, 'template'])->defaults('module', 'companies')->name('companies.template');
                Route::post('companies/import/preview', [DataTransferController::class, 'preview'])->defaults('module', 'companies')->name('companies.import.preview');
                Route::post('companies/import', [DataTransferController::class, 'import'])->defaults('module', 'companies')->name('companies.import.store');
                Route::delete('companies/bulk-delete', [CompaniesController::class, 'bulkDelete'])->name('companies.bulk-delete');
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
                Route::post('deals/export', [DataTransferController::class, 'export'])->defaults('module', 'deals')->name('deals.export');
                Route::get('deals/import', [DataTransferController::class, 'importForm'])->defaults('module', 'deals')->name('deals.import');
                Route::get('deals/import/template', [DataTransferController::class, 'template'])->defaults('module', 'deals')->name('deals.template');
                Route::post('deals/import/preview', [DataTransferController::class, 'preview'])->defaults('module', 'deals')->name('deals.import.preview');
                Route::post('deals/import', [DataTransferController::class, 'import'])->defaults('module', 'deals')->name('deals.import.store');
                Route::delete('deals/bulk-delete', [DealsController::class, 'bulkDelete'])->name('deals.bulk-delete');
                Route::resource('deals', DealsController::class);
                Route::get('tasks/my', [TasksController::class, 'my'])->name('tasks.my');
                Route::get('tasks/overdue', [TasksController::class, 'overdue'])->name('tasks.overdue');
                Route::get('tasks/today', [TasksController::class, 'today'])->name('tasks.today');
                Route::patch('tasks/{task}/complete', [TasksController::class, 'complete'])->name('tasks.complete');
                Route::delete('tasks/bulk-delete', [TasksController::class, 'bulkDelete'])->name('tasks.bulk-delete');
                Route::resource('tasks', TasksController::class);
                Route::patch('quotes/{quote}/send', [QuotesController::class, 'send'])->name('quotes.send');
                Route::patch('quotes/{quote}/accept', [QuotesController::class, 'accept'])->name('quotes.accept');
                Route::patch('quotes/{quote}/reject', [QuotesController::class, 'reject'])->name('quotes.reject');
                Route::patch('quotes/{quote}/expire', [QuotesController::class, 'expire'])->name('quotes.expire');
                Route::post('quotes/{quote}/duplicate', [QuotesController::class, 'duplicate'])->name('quotes.duplicate');
                Route::get('quotes/{quote}/preview', [QuotesController::class, 'preview'])->name('quotes.preview');
                Route::get('quotes/{quote}/download', [QuotesController::class, 'download'])->name('quotes.download');
                Route::delete('quotes/bulk-delete', [QuotesController::class, 'bulkDelete'])->name('quotes.bulk-delete');
                Route::post('quotes/export', [DataTransferController::class, 'export'])->defaults('module', 'quotes')->name('quotes.export');
                Route::resource('quotes', QuotesController::class);
                Route::delete('activities/bulk-delete', [ActivitiesController::class, 'bulkDelete'])->name('activities.bulk-delete');
                Route::resource('activities', ActivitiesController::class);
                Route::post('tags/bulk', [TagsController::class, 'bulk'])->name('tags.bulk');
                Route::delete('tags/bulk-delete', [TagsController::class, 'bulkDelete'])->name('tags.bulk-delete');
                Route::resource('tags', TagsController::class);
                Route::post('saved-filters', [SavedFiltersController::class, 'store'])->name('saved-filters.store');
                Route::get('saved-filters/{savedFilter}/apply', [SavedFiltersController::class, 'apply'])->name('saved-filters.apply');
                Route::delete('saved-filters/{savedFilter}', [SavedFiltersController::class, 'destroy'])->name('saved-filters.destroy');
                Route::get('audit-logs', [AuditLogsController::class, 'index'])->name('audit-logs.index');
                Route::get('api-tokens', [ApiTokensController::class, 'index'])->name('api-tokens.index');
                Route::post('api-tokens', [ApiTokensController::class, 'store'])->name('api-tokens.store');
                Route::delete('api-tokens/{apiToken}', [ApiTokensController::class, 'destroy'])->name('api-tokens.destroy');
                Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
                Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');

                Route::patch('users/{user}/toggle-active', [UsersController::class, 'toggleActive'])->name('users.toggle-active');
                Route::resource('users', UsersController::class)->except('show');

                Route::middleware('throttle:crm-ai')->group(function () {
                    Route::post('ai/summarize', [AiController::class, 'summarize'])->name('ai.summarize');
                    Route::post('ai/summarize-note', [AiController::class, 'summarizeNote'])->name('ai.summarize-note');
                    Route::post('ai/draft-email', [AiController::class, 'draftEmail'])->name('ai.draft-email');
                    Route::post('ai/follow-up', [AiController::class, 'followUp'])->name('ai.follow-up');
                });
            });
    });
