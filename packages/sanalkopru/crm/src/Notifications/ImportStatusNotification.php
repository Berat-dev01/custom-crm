<?php

namespace Sanalkopru\Crm\Notifications;

use Illuminate\Notifications\Notification;
use Sanalkopru\Crm\Models\CrmImport;

class ImportStatusNotification extends Notification
{
    public function __construct(
        public readonly CrmImport $import,
        public readonly string $status
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $module = ucfirst((string) $this->import->module);

        return match ($this->status) {
            'queued', 'pending', 'processing' => [
                'kind' => 'import_queued',
                'import_id' => $this->import->public_id,
                'module' => $this->import->module,
                'title' => $module.' import queued',
                'body' => $this->import->filename.' is queued for background processing.',
                'url' => route('crm.'.$this->import->module.'.import'),
            ],
            'completed_with_errors' => [
                'kind' => 'import_completed_with_errors',
                'import_id' => $this->import->public_id,
                'module' => $this->import->module,
                'title' => $module.' import finished with errors',
                'body' => sprintf(
                    '%d created, %d failed.',
                    (int) $this->import->processed_rows,
                    (int) $this->import->failed_rows
                ),
                'url' => route('crm.'.$this->import->module.'.import'),
                'error_report_url' => $this->import->error_report_path
                    ? route('crm.imports.errors', $this->import->public_id)
                    : null,
            ],
            default => [
                'kind' => 'import_completed',
                'import_id' => $this->import->public_id,
                'module' => $this->import->module,
                'title' => $module.' import completed',
                'body' => sprintf(
                    '%d created, %d failed.',
                    (int) $this->import->processed_rows,
                    (int) $this->import->failed_rows
                ),
                'url' => route('crm.'.$this->import->module.'.import'),
            ],
        };
    }
}
