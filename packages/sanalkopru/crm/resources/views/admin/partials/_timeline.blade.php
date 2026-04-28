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
        $typeLabel = $crmFormat->activityType($activity->type);
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
        <strong>{{ __('No activity yet.') }}</strong>
        <p>{{ __('Activities will appear here as you interact with this record.') }}</p>
    </div>
@endforelse
