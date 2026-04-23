@extends('admin-panel::layouts.app')

@section('title', 'Tasks')
@section('page-title', 'Tasks')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
    @php
        $activeFilterCount = collect($filters)
            ->except(['scope'])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->count();
    @endphp

    <section class="crm-admin-page" data-crm-module="tasks">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">CRM</p>
                <h1>Tasks</h1>
            </div>

            <div class="crm-admin-actions">
                <x-admin-panel::button :href="route('crm.tasks.index')" variant="{{ $filters['scope'] === 'all' ? 'outline' : 'ghost' }}" icon="list" data-admin-ajax-link data-admin-ajax-target="crm-tasks-list">All</x-admin-panel::button>
                <x-admin-panel::button :href="route('crm.tasks.my')" variant="{{ $filters['scope'] === 'my' ? 'outline' : 'ghost' }}" icon="user" data-admin-ajax-link data-admin-ajax-target="crm-tasks-list">My</x-admin-panel::button>
                <x-admin-panel::button :href="route('crm.tasks.today')" variant="{{ $filters['scope'] === 'today' ? 'outline' : 'ghost' }}" icon="calendar" data-admin-ajax-link data-admin-ajax-target="crm-tasks-list">Today</x-admin-panel::button>
                <x-admin-panel::button :href="route('crm.tasks.overdue')" variant="{{ $filters['scope'] === 'overdue' ? 'outline' : 'ghost' }}" icon="alert-circle" data-admin-ajax-link data-admin-ajax-target="crm-tasks-list">Overdue</x-admin-panel::button>
                @can('crm.tasks.create')
                    <x-admin-panel::button :href="route('crm.tasks.create')" icon="plus">New Task</x-admin-panel::button>
                @endcan
            </div>
        </header>

        <div id="crm-tasks-list" class="admin-ajax-region" data-admin-ajax-list>
            <x-admin-panel::filter-shell :action="request()->url()" :reset-url="request()->url()" :active-count="$activeFilterCount">
                <x-slot:compact>
                    <x-admin-panel::input name="search" label="Search" :value="$filters['search']" placeholder="Title or description" />
                    <x-admin-panel::select name="assigned_to" label="Assignee" :options="$owners" :selected="$filters['assigned_to']" placeholder="All assignees" />
                    <x-admin-panel::select name="status" label="Status" :options="$statuses" :selected="$filters['status']" placeholder="All statuses" />
                </x-slot:compact>

                <x-slot:advanced>
                    <x-admin-panel::select name="priority" label="Priority" :options="$priorities" :selected="$filters['priority']" placeholder="All priorities" />
                    <x-admin-panel::input name="due_from" label="Due From" type="date" :value="$filters['due_from']" />
                    <x-admin-panel::input name="due_to" label="Due To" type="date" :value="$filters['due_to']" />
                </x-slot:advanced>

                <x-slot:saved>
                    @include('crm::admin.partials.saved-filters', ['module' => 'tasks', 'savedFilters' => $savedFilters, 'filters' => $filters])
                </x-slot:saved>
            </x-admin-panel::filter-shell>

            <x-admin-panel::card>
                <x-slot:header>
                    {{ ucfirst($filters['scope']) }} Tasks
                </x-slot:header>

                <x-admin-panel::table :headers="[
                    ['label' => 'Task'],
                    ['label' => 'Related'],
                    ['label' => 'Assignee'],
                    ['label' => 'Due'],
                    ['label' => 'Priority'],
                    ['label' => 'Status'],
                    ['label' => 'Actions', 'width' => '240px'],
                ]">
                    @forelse($tasks as $task)
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
                    <tr>
                        <td>
                            <strong>{{ $task->title }}</strong>
                            <div class="crm-muted">{{ $task->description ? str($task->description)->limit(80) : 'No description' }}</div>
                        </td>
                        <td>{{ $relatedLabel }}</td>
                        <td>{{ $task->assignee?->name ?: '-' }}</td>
                        <td>{{ $crmFormat->datetime($task->due_at) }}</td>
                        <td>{{ $crmFormat->status($task->priority) }}</td>
                        <td>{{ $crmFormat->status($task->status) }}</td>
                        <td>
                            <div class="crm-row-actions">
                                <x-admin-panel::button :href="route('crm.tasks.show', $task)" size="sm" variant="ghost" icon="eye" />
                                @can('update', $task)
                                    <x-admin-panel::button :href="route('crm.tasks.edit', $task)" size="sm" variant="ghost" icon="pencil" />
                                @endcan
                                @can('complete', $task)
                                    @if($task->status !== 'completed')
                                        <form method="POST" action="{{ route('crm.tasks.complete', $task) }}" class="crm-inline-form">
                                            @csrf
                                            @method('PATCH')
                                            <x-admin-panel::button type="submit" size="sm" variant="ghost" icon="check" />
                                        </form>
                                    @endif
                                @endcan
                                @can('delete', $task)
                                    <form method="POST" action="{{ route('crm.tasks.destroy', $task) }}" class="crm-inline-form" data-crm-confirm="Delete this task?">
                                        @csrf
                                        @method('DELETE')
                                        <x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2" />
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            @include('crm::admin.partials.empty-state', [
                                'title' => 'No tasks found.',
                                'body' => 'Create a follow-up so the next sales action is visible.',
                                'actionUrl' => route('crm.tasks.create'),
                                'actionLabel' => 'New Task',
                                'actionPermission' => 'crm.tasks.create',
                            ])
                        </td>
                    </tr>
                    @endforelse
                </x-admin-panel::table>

                <x-admin-panel::pagination :paginator="$tasks" class="crm-pagination" />
            </x-admin-panel::card>
        </div>
    </section>
@endsection
