<?php

namespace Sanalkopru\Crm\Services\Notifications;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\DatabaseNotification;
use Sanalkopru\Crm\Support\CrmFormatter;
use Sanalkopru\Crm\Support\CrmLabelCatalog;

class NotificationCenter
{
    private const LIMIT = 8;

    public function __construct(
        private readonly CrmFormatter $formatter,
        private readonly CrmLabelCatalog $labels
    ) {}

    /**
     * @return array{items: list<array<string, mixed>>, unread_count: int, has_more: bool}
     */
    public function payload(?User $user): array
    {
        if (! $user) {
            return [
                'items' => [],
                'unread_count' => 0,
                'has_more' => false,
            ];
        }

        $items = $user->notifications()
            ->latest()
            ->limit(self::LIMIT + 1)
            ->get();

        return [
            'items' => $items->take(self::LIMIT)->map(fn (DatabaseNotification $notification): array => $this->format($notification))->all(),
            'unread_count' => $user->unreadNotifications()->count(),
            'has_more' => $items->count() > self::LIMIT,
            'server_time' => now()->toIso8601String(),
        ];
    }

    public function paginate(?User $user, int $perPage = 20): LengthAwarePaginator
    {
        abort_unless($user, 403);

        $paginator = $user->notifications()
            ->latest()
            ->paginate($perPage)
            ->through(fn (DatabaseNotification $notification): array => $this->format($notification));

        return $paginator;
    }

    public function markRead(?User $user, string $notificationId): void
    {
        if (! $user) {
            return;
        }

        $notification = $user->notifications()->whereKey($notificationId)->first();

        if ($notification && $notification->read_at === null) {
            $notification->markAsRead();
        }
    }

    public function markAllRead(?User $user): void
    {
        if (! $user) {
            return;
        }

        $user->unreadNotifications->markAsRead();
    }

    /**
     * @return array<string, mixed>
     */
    private function format(DatabaseNotification $notification): array
    {
        $data = $notification->data;
        $kind = (string) ($data['kind'] ?? 'system');
        $title = (string) ($data['title'] ?? $data['subject'] ?? trans('crm::notifications.center.default_title'));
        $body = (string) ($data['body'] ?? $this->fallbackBody($data));
        $icon = $this->icon($kind);

        return [
            'id' => $notification->id,
            'kind' => $kind,
            'title' => $title,
            'body' => $body,
            'url' => $data['url'] ?? null,
            'icon' => $icon['icon'],
            'variant' => $icon['variant'],
            'created_at' => $notification->created_at?->toIso8601String(),
            'relative_time' => $notification->created_at?->diffForHumans(),
            'read_at' => $notification->read_at?->toIso8601String(),
            'unread' => $notification->read_at === null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function fallbackBody(array $data): string
    {
        if (! empty($data['due_at'])) {
            return trans('crm::notifications.center.fallback_due_at', [
                'value' => $this->formatter->datetime((string) $data['due_at']),
            ]);
        }

        if (! empty($data['priority'])) {
            return trans('crm::notifications.center.fallback_priority', [
                'value' => $this->labels->status((string) $data['priority']),
            ]);
        }

        return trans('crm::notifications.center.fallback_open');
    }

    /**
     * @return array{icon: string, variant: string}
     */
    private function icon(string $kind): array
    {
        return match ($kind) {
            'task_reminder' => ['icon' => 'check-square', 'variant' => 'warning'],
            'task_assigned' => ['icon' => 'check-square', 'variant' => 'info'],
            'task_reassigned' => ['icon' => 'check-square', 'variant' => 'warning'],
            'quote_sent' => ['icon' => 'send', 'variant' => 'info'],
            'quote_accepted' => ['icon' => 'badge-check', 'variant' => 'success'],
            'quote_rejected', 'quote_expired' => ['icon' => 'circle-x', 'variant' => 'danger'],
            'import_completed' => ['icon' => 'file-up', 'variant' => 'success'],
            'import_completed_with_errors' => ['icon' => 'file-up', 'variant' => 'warning'],
            'import_queued' => ['icon' => 'file-up', 'variant' => 'info'],
            default => ['icon' => 'bell', 'variant' => 'secondary'],
        };
    }
}
