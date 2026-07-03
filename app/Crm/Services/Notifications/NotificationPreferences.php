<?php

namespace App\Crm\Services\Notifications;

use App\Crm\Services\Settings\CrmSettingsManager;

class NotificationPreferences
{
    public function __construct(private readonly CrmSettingsManager $settings) {}

    public function taskRemindersEnabled(): bool
    {
        return (bool) $this->settings->get('notify_task_reminders', true);
    }

    public function taskAssignmentsEnabled(): bool
    {
        return (bool) $this->settings->get('notify_task_assignments', true);
    }

    public function quoteStatusChangesEnabled(): bool
    {
        return (bool) $this->settings->get('notify_quote_status_changes', true);
    }

    public function importStatusUpdatesEnabled(): bool
    {
        return (bool) $this->settings->get('notify_import_status_updates', true);
    }

    public function dealClosedEnabled(): bool
    {
        return (bool) $this->settings->get('notify_deal_closed', true);
    }

    /**
     * Event keys users can opt out of receiving by email.
     *
     * @return list<string>
     */
    public static function emailEvents(): array
    {
        return [
            'task_reminders',
            'task_assignments',
            'quote_status_changes',
            'deal_closed',
            'import_status_updates',
        ];
    }

    public function emailChannelEnabled(): bool
    {
        return (bool) $this->settings->get('notify_email_enabled', true);
    }

    /**
     * Whether the given notifiable should receive this event by email:
     * the global email switch must be on, the user must have an address,
     * and the user must not have opted out of the event.
     */
    public function emailEnabledFor(?object $notifiable, string $event): bool
    {
        if (! $this->emailChannelEnabled()) {
            return false;
        }

        if (! $notifiable || empty($notifiable->email)) {
            return false;
        }

        $prefs = $notifiable->notification_email_prefs ?? [];

        return (bool) ($prefs[$event] ?? true);
    }
}
