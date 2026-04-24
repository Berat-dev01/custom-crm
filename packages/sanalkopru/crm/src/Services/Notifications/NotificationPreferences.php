<?php

namespace Sanalkopru\Crm\Services\Notifications;

use Sanalkopru\Crm\Services\Settings\CrmSettingsManager;

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
}
