<?php

return [
    'task_reminder' => [
        'mail_subject' => 'CRM Task Reminder: :task',
        'mail_intro' => 'A CRM task reminder is due.',
        'mail_due_at' => 'Due at: :value',
        'mail_no_due_date' => 'No due date',
        'mail_action' => 'Open Tasks',
        'database_title' => 'Task reminder',
    ],
    'task_assignment' => [
        'assigned_title' => 'Task assigned',
        'reassigned_title' => 'Task reassigned',
    ],
    'quote_status_changed' => [
        'title' => 'Quote :status',
        'body_with_company' => ':quote - :company',
        'body_without_company' => ':quote',
    ],
    'import_status' => [
        'queued_title' => ':module import queued',
        'queued_body' => ':filename is queued for background processing.',
        'completed_with_errors_title' => ':module import finished with errors',
        'completed_title' => ':module import completed',
        'completed_body' => ':created created, :failed failed.',
    ],
    'deal_closed' => [
        'won_title' => 'Deal won',
        'lost_title' => 'Deal lost',
        'body' => ':deal - :value',
    ],
    'quote_customer' => [
        'subject' => 'Quote :quote from :company',
        'greeting' => 'Hello :name,',
        'intro' => ':company has prepared a quote for you.',
        'total' => 'Total: :value',
        'valid_until' => 'Valid until: :date',
        'attachment_note' => 'You can find the full quote attached as a PDF.',
        'outro' => 'If you have any questions, simply reply to this email.',
    ],
    'weekly_digest' => [
        'subject' => 'Your weekly CRM digest',
        'intro' => 'Here is what happened in your CRM over the last 7 days.',
        'pipeline' => 'Open pipeline: :count deal(s) worth :value',
        'won' => 'Won last week: :count deal(s) worth :value',
        'lost' => 'Lost last week: :count deal(s)',
        'overdue_tasks' => 'Overdue tasks: :count',
        'pending_quotes' => 'Quotes awaiting response: :count',
    ],
    'mail' => [
        'greeting' => 'Hello :name,',
        'action' => 'Open CRM',
        'footer' => 'You can manage your email notification preferences from the Notifications page.',
    ],
    'center' => [
        'default_title' => 'Notification',
        'fallback_due_at' => 'Due at :value',
        'fallback_priority' => 'Priority: :value',
        'fallback_open' => 'Open to review the latest CRM update.',
    ],
];
