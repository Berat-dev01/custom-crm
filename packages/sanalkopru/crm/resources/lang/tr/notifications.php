<?php

return [
    'task_reminder' => [
        'mail_subject' => 'CRM Gorev Hatirlatmasi: :task',
        'mail_intro' => 'Bir CRM gorevi icin hatirlatma zamani geldi.',
        'mail_due_at' => 'Vade: :value',
        'mail_no_due_date' => 'Vade tarihi yok',
        'mail_action' => 'Gorevleri Ac',
        'database_title' => 'Gorev hatirlatmasi',
    ],
    'task_assignment' => [
        'assigned_title' => 'Gorev atandi',
        'reassigned_title' => 'Gorev yeniden atandi',
    ],
    'quote_status_changed' => [
        'title' => 'Teklif :status',
        'body_with_company' => ':quote - :company',
        'body_without_company' => ':quote',
    ],
    'import_status' => [
        'queued_title' => ':module ice aktarma siraya alindi',
        'queued_body' => ':filename arka plan islemi icin siraya alindi.',
        'completed_with_errors_title' => ':module ice aktarma hatalarla tamamlandi',
        'completed_title' => ':module ice aktarma tamamlandi',
        'completed_body' => ':created olusturuldu, :failed basarisiz oldu.',
    ],
    'center' => [
        'default_title' => 'Bildirim',
        'fallback_due_at' => 'Vade: :value',
        'fallback_priority' => 'Oncelik: :value',
        'fallback_open' => 'En son CRM guncellemesini incelemek icin acin.',
    ],
];
