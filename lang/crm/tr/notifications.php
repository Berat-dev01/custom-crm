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
    'deal_closed' => [
        'won_title' => 'Fırsat kazanıldı',
        'lost_title' => 'Fırsat kaybedildi',
        'body' => ':deal - :value',
    ],
    'quote_customer' => [
        'subject' => ':company - :quote numaralı teklif',
        'greeting' => 'Merhaba :name,',
        'intro' => ':company sizin için bir teklif hazırladı.',
        'total' => 'Toplam: :value',
        'valid_until' => 'Geçerlilik: :date',
        'view_online' => 'Teklifi çevrimiçi görüntüle',
        'attachment_note' => 'Teklifin tamamını ekteki PDF dosyasında bulabilirsiniz.',
        'outro' => 'Sorularınız için bu e-postayı yanıtlamanız yeterli.',
    ],
    'weekly_digest' => [
        'subject' => 'Haftalık CRM özetiniz',
        'intro' => 'Son 7 günde CRM tarafında olanlar aşağıda.',
        'pipeline' => 'Açık pipeline: :count fırsat, toplam :value',
        'won' => 'Geçen hafta kazanılan: :count fırsat, toplam :value',
        'lost' => 'Geçen hafta kaybedilen: :count fırsat',
        'overdue_tasks' => 'Geciken görevler: :count',
        'pending_quotes' => 'Yanıt bekleyen teklifler: :count',
    ],
    'mail' => [
        'greeting' => 'Merhaba :name,',
        'action' => "CRM'i Aç",
        'footer' => 'E-posta bildirim tercihlerinizi Bildirimler sayfasından yönetebilirsiniz.',
    ],
    'center' => [
        'default_title' => 'Bildirim',
        'fallback_due_at' => 'Vade: :value',
        'fallback_priority' => 'Öncelik: :value',
        'fallback_open' => 'En son CRM güncellemesini incelemek için açın.',
    ],
];
