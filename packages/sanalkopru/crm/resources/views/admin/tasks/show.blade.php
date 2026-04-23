@extends('admin-panel::layouts.app')

@section('title', $task->title)
@section('page-title', $task->title)

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
    @php
        $related = $task->taskable;
        $relatedRoute = match(true) {
            $related instanceof \Sanalkopru\Crm\Models\Contact => route('crm.contacts.show', $related),
            $related instanceof \Sanalkopru\Crm\Models\Company => route('crm.companies.show', $related),
            $related instanceof \Sanalkopru\Crm\Models\Deal   => route('crm.deals.show', $related),
            $related instanceof \Sanalkopru\Crm\Models\Quote  => route('crm.quotes.show', $related),
            default => null,
        };
        $relatedLabel = match(true) {
            $related instanceof \Sanalkopru\Crm\Models\Contact => $related->full_name,
            $related instanceof \Sanalkopru\Crm\Models\Company => $related->name,
            $related instanceof \Sanalkopru\Crm\Models\Deal   => $related->title,
            $related instanceof \Sanalkopru\Crm\Models\Quote  => $related->quote_number,
            default => '-',
        };
        $statusVariant = match($task->status) { 'completed' => 'success', 'in_progress' => 'warning', default => 'secondary' };
        $priorityVariant = match($task->priority) { 'high' => 'danger', 'medium' => 'warning', default => 'secondary' };
    @endphp

    <section class="crm-admin-page" data-crm-module="tasks">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">CRM / Tasks</p>
                <h1>{{ $task->title }}</h1>
                <p class="crm-muted" style="display:flex;gap:8px;align-items:center;margin-top:4px;">
                    <x-admin-panel::badge :variant="$statusVariant">{{ ucfirst(str_replace('_', ' ', $task->status)) }}</x-admin-panel::badge>
                    <x-admin-panel::badge :variant="$priorityVariant">{{ ucfirst($task->priority) }} priority</x-admin-panel::badge>
                </p>
            </div>
            <div class="crm-admin-actions">
                @can('update', $task)
                    <x-admin-panel::button :href="route('crm.tasks.edit', $task)" icon="pencil">Edit</x-admin-panel::button>
                @endcan
                @can('delete', $task)
                    <form method="POST" action="{{ route('crm.tasks.destroy', $task) }}" data-crm-confirm="Delete this task?">
                        @csrf
                        @method('DELETE')
                        <x-admin-panel::button type="submit" variant="danger" icon="trash-2">Delete</x-admin-panel::button>
                    </form>
                @endcan
                <x-admin-panel::button :href="route('crm.tasks.index')" variant="ghost" icon="arrow-left">Back</x-admin-panel::button>
            </div>
        </header>

        <div class="crm-admin-grid">
            <x-admin-panel::stat-card
                label="Status"
                :value="ucfirst(str_replace('_', ' ', $task->status))"
                icon="circle-check"
                :variant="$statusVariant"
            />
            <x-admin-panel::stat-card
                label="Due Date"
                :value="$task->due_at?->format('d M Y') ?: 'No due date'"
                icon="calendar"
                variant="warning"
            />
            <x-admin-panel::stat-card
                label="Assignee"
                :value="$task->assignee?->name ?: 'Unassigned'"
                icon="user"
                variant="primary"
            />
            <x-admin-panel::stat-card
                label="Reminder"
                :value="$task->reminder_at?->format('d M Y H:i') ?: 'None'"
                icon="bell"
                variant="info"
            />
        </div>

        <x-admin-panel::card>
            <x-slot:header>Task Detail</x-slot:header>

            <dl class="crm-detail-list">
                <dt>Related</dt>
                <dd>
                    @if($relatedRoute)
                        <a href="{{ $relatedRoute }}" style="color:#0369a1;">{{ $relatedLabel }}</a>
                    @else
                        {{ $relatedLabel }}
                    @endif
                </dd>
                <dt>Description</dt>
                <dd>{{ $task->description ?: '-' }}</dd>
                @if($task->completed_at)
                    <dt>Completed</dt>
                    <dd>{{ $task->completed_at->format('d M Y H:i') }}</dd>
                @endif
                @if($task->reminder_notified_at)
                    <dt>Reminder sent</dt>
                    <dd>{{ $task->reminder_notified_at->format('d M Y H:i') }}</dd>
                @endif
            </dl>

            @can('complete', $task)
                @if($task->status !== 'completed')
                    <form method="POST" action="{{ route('crm.tasks.complete', $task) }}" class="crm-form-actions" style="margin-top: 18px;">
                        @csrf
                        @method('PATCH')
                        <x-admin-panel::button type="submit" icon="check">Mark Completed</x-admin-panel::button>
                    </form>
                @endif
            @endcan
        </x-admin-panel::card>
    </section>
@endsection
