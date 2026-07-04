<?php

namespace App\Crm\Notifications;

use App\Crm\Models\CrmImport;
use App\Crm\Notifications\Concerns\RoutesEmailByPreference;
use App\Crm\Support\CrmLabelCatalog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ImportStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use RoutesEmailByPreference;

    public const EMAIL_PREFERENCE_KEY = 'import_status_updates';

    public function __construct(
        public readonly CrmImport $import,
        public readonly string $status
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->toArray($notifiable);

        return (new MailMessage)
            ->subject($data['title'])
            ->greeting(trans('crm::notifications.mail.greeting', ['name' => $notifiable->name ?? '']))
            ->line($data['body'])
            ->action(trans('crm::notifications.mail.action'), $data['url'])
            ->line(trans('crm::notifications.mail.footer'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $module = app(CrmLabelCatalog::class)->moduleLabel((string) $this->import->module);
        $summary = trans('crm::notifications.import_status.completed_body', [
            'created' => (int) $this->import->processed_rows,
            'failed' => (int) $this->import->failed_rows,
        ]);

        return match ($this->status) {
            'queued', 'pending', 'processing' => [
                'kind' => 'import_queued',
                'import_id' => $this->import->public_id,
                'module' => $this->import->module,
                'title' => trans('crm::notifications.import_status.queued_title', ['module' => $module]),
                'body' => trans('crm::notifications.import_status.queued_body', ['filename' => $this->import->filename]),
                'url' => route('crm.'.$this->import->module.'.import'),
            ],
            'completed_with_errors' => [
                'kind' => 'import_completed_with_errors',
                'import_id' => $this->import->public_id,
                'module' => $this->import->module,
                'title' => trans('crm::notifications.import_status.completed_with_errors_title', ['module' => $module]),
                'body' => $summary,
                'url' => route('crm.'.$this->import->module.'.import'),
                'error_report_url' => $this->import->error_report_path
                    ? route('crm.imports.errors', $this->import->public_id)
                    : null,
            ],
            default => [
                'kind' => 'import_completed',
                'import_id' => $this->import->public_id,
                'module' => $this->import->module,
                'title' => trans('crm::notifications.import_status.completed_title', ['module' => $module]),
                'body' => $summary,
                'url' => route('crm.'.$this->import->module.'.import'),
            ],
        };
    }
}
