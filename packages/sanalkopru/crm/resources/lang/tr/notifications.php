<?php

return [
    'task_reminder' => [
        'mail_subject' => 'CRM Görev Hatırlatması: :task',
        'mail_intro' => 'Bir CRM görevi için hatırlatma zamanı geldi.',
        'mail_due_at' => 'Vade: :value',
        'mail_no_due_date' => 'Vade tarihi yok',
        'mail_action' => 'Görevleri Aç',
        'database_title' => 'Görev hatırlatması',
    ],
    'task_assignment' => [
        'assigned_title' => 'Görev atandı',
        'reassigned_title' => 'Görev yeniden atandı',
    ],
    'quote_status_changed' => [
        'title' => 'Teklif :status',
        'body_with_company' => ':quote - :company',
        'body_without_company' => ':quote',
    ],
    'import_status' => [
        'queued_title' => ':module içe aktarma sıraya alındı',
        'queued_body' => ':filename arka plan işlemi için sıraya alındı.',
        'completed_with_errors_title' => ':module içe aktarma hatalarla tamamlandı',
        'completed_title' => ':module içe aktarma tamamlandı',
        'completed_body' => ':created oluşturuldu, :failed başarısız oldu.',
    ],
    'center' => [
        'default_title' => 'Bildirim',
        'fallback_due_at' => 'Vade: :value',
        'fallback_priority' => 'Öncelik: :value',
        'fallback_open' => 'En son CRM güncellemesini incelemek için açın.',
    ],
];
