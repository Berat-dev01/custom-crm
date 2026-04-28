@extends('admin-panel::layouts.app')

@section('title', $task->exists ? __('Edit Task') : __('New Task'))
@section('page-title', $task->exists ? __('Edit Task') : __('New Task'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
    <section class="crm-admin-page" data-crm-module="tasks">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">{{ __('CRM / Tasks') }}</p>
                <h1>{{ $task->exists ? __('Edit Task') : __('New Task') }}</h1>
            </div>
            <x-admin-panel::button :href="route('crm.tasks.index')" variant="ghost" icon="arrow-left">{{ __('Back') }}</x-admin-panel::button>
        </header>

        <x-admin-panel::card>
            <form
                method="POST"
                action="{{ $task->exists ? route('crm.tasks.update', $task) : route('crm.tasks.store') }}"
                class="crm-form-grid"
            >
                @csrf
                @if($task->exists)
                    @method('PUT')
                @endif

                <x-admin-panel::input name="title" label="Title" :value="$task->title" required />
                <x-admin-panel::select name="assigned_to" label="Assignee" :options="$owners" :selected="$task->assigned_to" placeholder="Unassigned" />
                <x-admin-panel::input name="due_at" label="Due At" type="datetime-local" :value="$task->due_at?->format('Y-m-d\\TH:i')" />
                <x-admin-panel::input name="reminder_at" label="Reminder At" type="datetime-local" :value="$task->reminder_at?->format('Y-m-d\\TH:i')" />
                <x-admin-panel::select name="priority" label="Priority" :options="$priorities" :selected="$task->priority ?: 'normal'" required />
                <x-admin-panel::select name="status" label="Status" :options="$statuses" :selected="$task->status ?: 'open'" required />
                <x-admin-panel::select name="taskable_type" label="Related Type" :options="$taskableTypes" :selected="$selectedTaskableType" placeholder="No related record" />
                <x-admin-panel::input name="taskable_id" label="Related Record ID" type="number" :value="$task->taskable_id" />

                <div class="crm-span-2 crm-highlight-box">
                    <strong>{{ __('Available related records') }}</strong>
                    <p class="crm-muted">
                        {{ __('Contacts') }}: {{ $taskableOptions['contact']->pluck('full_name', 'id')->map(fn($name, $id) => "#{$id} {$name}")->implode(', ') ?: '-' }}
                    </p>
                    <p class="crm-muted">
                        {{ __('Companies') }}: {{ $taskableOptions['company']->pluck('name', 'id')->map(fn($name, $id) => "#{$id} {$name}")->implode(', ') ?: '-' }}
                    </p>
                    <p class="crm-muted">
                        {{ __('Deals') }}: {{ $taskableOptions['deal']->pluck('title', 'id')->map(fn($name, $id) => "#{$id} {$name}")->implode(', ') ?: '-' }}
                    </p>
                    <p class="crm-muted">
                        {{ __('Quotes') }}: {{ $taskableOptions['quote']->pluck('quote_number', 'id')->map(fn($name, $id) => "#{$id} {$name}")->implode(', ') ?: '-' }}
                    </p>
                </div>

                <x-admin-panel::textarea name="description" label="Description" class="crm-span-2" :value="$task->description" rows="5" />

                <div class="crm-form-actions crm-span-2">
                    <x-admin-panel::button type="submit" icon="save">
                        {{ $task->exists ? __('Save Task') : __('Create Task') }}
                    </x-admin-panel::button>
                    <x-admin-panel::button :href="route('crm.tasks.index')" variant="ghost">{{ __('Cancel') }}</x-admin-panel::button>
                </div>
            </form>
        </x-admin-panel::card>
    </section>
@endsection
