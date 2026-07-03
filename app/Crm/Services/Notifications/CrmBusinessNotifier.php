<?php

namespace App\Crm\Services\Notifications;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use App\Crm\Models\CrmImport;
use App\Crm\Models\Deal;
use App\Crm\Models\Quote;
use App\Crm\Models\Task;
use App\Crm\Notifications\ImportStatusNotification;
use App\Crm\Notifications\DealClosedNotification;
use App\Crm\Notifications\QuoteStatusChangedNotification;
use App\Crm\Notifications\TaskAssignmentNotification;

class CrmBusinessNotifier
{
    public function __construct(private readonly NotificationPreferences $preferences) {}

    public function taskAssigned(Task $task, ?Authenticatable $actor = null, bool $reassigned = false): void
    {
        if (! $this->preferences->taskAssignmentsEnabled()) {
            return;
        }

        $task->loadMissing('assignee');

        $recipient = $task->assignee;

        if (! $recipient instanceof User) {
            return;
        }

        if ($actor && (int) $actor->getAuthIdentifier() === (int) $recipient->getKey()) {
            return;
        }

        if ($this->alreadyHasUnread($recipient, [
            'kind' => $reassigned ? 'task_reassigned' : 'task_assigned',
            'task_id' => $task->id,
        ])) {
            return;
        }

        $recipient->notify(new TaskAssignmentNotification($task, $reassigned));
    }

    public function dealClosed(Deal $deal, string $result, ?Authenticatable $actor = null): void
    {
        if (! $this->preferences->dealClosedEnabled()) {
            return;
        }

        $deal->loadMissing('owner');
        $recipient = $deal->owner;

        if (! $recipient instanceof User) {
            return;
        }

        if ($actor && (int) $actor->getAuthIdentifier() === (int) $recipient->getKey()) {
            return;
        }

        if ($this->alreadyHasUnread($recipient, [
            'kind' => 'deal_'.$result,
            'deal_id' => $deal->id,
        ])) {
            return;
        }

        $recipient->notify(new DealClosedNotification($deal, $result));
    }

    public function quoteStatusChanged(Quote $quote, string $status, ?Authenticatable $actor = null): void
    {
        if (! $this->preferences->quoteStatusChangesEnabled()) {
            return;
        }

        $quote->loadMissing('owner', 'company', 'deal.owner');

        $recipientIds = collect([
            $quote->owner?->getKey(),
            $quote->deal?->owner?->getKey(),
        ])
            ->filter()
            ->unique()
            ->values();

        if ($actor) {
            $recipientIds = $recipientIds
                ->reject(fn (mixed $id): bool => (int) $id === (int) $actor->getAuthIdentifier())
                ->values();
        }

        if ($recipientIds->isEmpty()) {
            return;
        }

        User::query()
            ->whereKey($recipientIds->all())
            ->get()
            ->each(function (User $user) use ($quote, $status): void {
                if ($this->alreadyHasUnread($user, [
                    'kind' => 'quote_'.$status,
                    'quote_id' => $quote->id,
                    'status' => $status,
                ])) {
                    return;
                }

                $user->notify(new QuoteStatusChangedNotification($quote, $status));
            });
    }

    public function importQueued(CrmImport $import): void
    {
        if (! $this->preferences->importStatusUpdatesEnabled()) {
            return;
        }

        $import->loadMissing('creator');

        if (! $import->creator instanceof User) {
            return;
        }

        if ($this->alreadyHasUnread($import->creator, [
            'kind' => 'import_queued',
            'import_id' => $import->public_id,
        ])) {
            return;
        }

        $import->creator->notify(new ImportStatusNotification($import, 'queued'));
    }

    public function importCompleted(CrmImport $import): void
    {
        if (! $this->preferences->importStatusUpdatesEnabled()) {
            return;
        }

        $import->loadMissing('creator');

        if (! $import->creator instanceof User) {
            return;
        }

        $kind = match ((string) $import->status) {
            'completed_with_errors' => 'import_completed_with_errors',
            default => 'import_completed',
        };

        if ($this->alreadyHasUnread($import->creator, [
            'kind' => $kind,
            'import_id' => $import->public_id,
        ])) {
            return;
        }

        $import->creator->notify(new ImportStatusNotification($import, (string) $import->status));
    }

    /**
     * @param  array<string, mixed>  $signature
     */
    private function alreadyHasUnread(User $recipient, array $signature): bool
    {
        return $recipient->unreadNotifications()
            ->latest()
            ->limit(25)
            ->get()
            ->contains(function ($notification) use ($signature): bool {
                foreach ($signature as $key => $value) {
                    if (data_get($notification->data, $key) != $value) {
                        return false;
                    }
                }

                return true;
            });
    }
}
