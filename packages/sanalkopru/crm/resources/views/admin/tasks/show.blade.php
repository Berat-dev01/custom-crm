@extends('admin-panel::layouts.app')

@section('title', $task->title)
@section('page-title', $task->title)

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
    @php
        $related = $task->taskable;
        $relatedLabel = match(true) {
            $related instanceof \Sanalkopru\Crm\Models\Contact => $related->full_name,
            $related instanceof \Sanalkopru\Crm\Models\Company => $related->name,
            $related instanceof \Sanalkopru\Crm\Models\Deal => $related->title,
            $related instanceof \Sanalkopru\Crm\Models\Quote => $related->quote_number,
            default => '-',
        };
    @endphp

    <section class="crm-admin-page" data-crm-module="tasks">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">CRM / Tasks</p>
                <h1>{{ $task->title }}</h1>
            </div>
            <div class="crm-admin-actions">
                @can('update', $task)
                    <x-admin-panel::button :href="route('crm.tasks.edit', $task)" icon="pencil">Edit</x-admin-panel::button>
                @endcan
                <x-admin-panel::button :href="route('crm.tasks.index')" variant="ghost" icon="arrow-left">Back</x-admin-panel::button>
            </div>
        </header>

        <div class="crm-admin-grid">
            <div class="crm-admin-card">
                <span class="crm-admin-card-label">Status</span>
                <strong>{{ ucfirst(str_replace('_', ' ', $task->status)) }}</strong>
                <p>{{ $task->completed_at?->format('Y-m-d H:i') ?: 'Not completed' }}</p>
            </div>
            <div class="crm-admin-card">
                <span class="crm-admin-card-label">Due</span>
                <strong>{{ $task->due_at?->format('Y-m-d H:i') ?: '-' }}</strong>
                <p>Reminder: {{ $task->reminder_at?->format('Y-m-d H:i') ?: '-' }}</p>
            </div>
            <div class="crm-admin-card">
                <span class="crm-admin-card-label">Assignee</span>
                <strong>{{ $task->assignee?->name ?: '-' }}</strong>
                <p>{{ ucfirst($task->priority) }} priority</p>
            </div>
        </div>

        <x-admin-panel::card>
            <x-slot:header>
                Task Detail
            </x-slot:header>

            <dl class="crm-detail-list">
                <dt>Related</dt>
                <dd>{{ $relatedLabel }}</dd>
                <dt>Description</dt>
                <dd>{{ $task->description ?: '-' }}</dd>
                <dt>Reminder Sent</dt>
                <dd>{{ $task->reminder_notified_at?->format('Y-m-d H:i') ?: '-' }}</dd>
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
