<?php

namespace App\Crm\Notifications\Concerns;

use App\Crm\Services\Notifications\NotificationPreferences;

/**
 * Adds the mail channel when the global email switch is on and the
 * notifiable has not opted out of the notification's event key.
 */
trait RoutesEmailByPreference
{
    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (app(NotificationPreferences::class)->emailEnabledFor($notifiable, static::EMAIL_PREFERENCE_KEY)) {
            $channels[] = 'mail';
        }

        return $channels;
    }
}
