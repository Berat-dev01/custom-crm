@forelse($timeline as $activity)
    @php
        $isSystem = in_array($activity->type, ['system', 'task_completed', 'quote_sent', 'deal_moved', 'deal_won', 'deal_lost']);
        $typeVariant = match($activity->type) {
            'note' => 'info',
            'call' => 'success',
            'email' => 'primary',
            'meeting' => 'warning',
            default => 'secondary',
        };
        $typeLabel = match($activity->type) {
            'note' => 'Note',
            'call' => 'Call',
            'email' => 'Email',
            'meeting' => 'Meeting',
            'task_completed' => 'Task Completed',
            'quote_sent' => 'Quote Sent',
            'deal_moved' => 'Stage Change',
            'deal_won' => 'Deal Won',
            'deal_lost' => 'Deal Lost',
            default => ucfirst(str_replace('_', ' ', $activity->type)),
        };
    @endphp
    <div class="crm-timeline-item{{ $isSystem ? ' crm-timeline-item--system' : '' }}">
        <div class="crm-timeline-item-header">
            <x-admin-panel::badge :variant="$typeVariant" size="sm">{{ $typeLabel }}</x-admin-panel::badge>
            <strong>{{ $activity->subject }}</strong>
        </div>
        @if($activity->body)
            <p class="crm-timeline-body">{{ $activity->body }}</p>
        @endif
        <span class="crm-timeline-meta">
            {{ $activity->occurred_at?->diffForHumans() ?: '-' }}
            {{ $activity->user ? '· '.$activity->user->name : '' }}
        </span>
    </div>
@empty
    <div class="crm-empty-state">
        <strong>No activity yet.</strong>
        <p>Activities will appear here as you interact with this record.</p>
    </div>
@endforelse
