@extends('admin-panel::layouts.app')

@section('title', 'Tasks')
@section('page-title', 'Tasks')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
    <section class="crm-admin-page" data-crm-module="tasks">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">CRM</p>
                <h1>Tasks</h1>
            </div>

            <div class="crm-admin-actions">
                <x-admin-panel::button :href="route('crm.tasks.index')" variant="{{ $filters['scope'] === 'all' ? 'outline' : 'ghost' }}" icon="list">All</x-admin-panel::button>
                <x-admin-panel::button :href="route('crm.tasks.my')" variant="{{ $filters['scope'] === 'my' ? 'outline' : 'ghost' }}" icon="user">My</x-admin-panel::button>
                <x-admin-panel::button :href="route('crm.tasks.today')" variant="{{ $filters['scope'] === 'today' ? 'outline' : 'ghost' }}" icon="calendar">Today</x-admin-panel::button>
                <x-admin-panel::button :href="route('crm.tasks.overdue')" variant="{{ $filters['scope'] === 'overdue' ? 'outline' : 'ghost' }}" icon="alert-circle">Overdue</x-admin-panel::button>
                @can('crm.tasks.create')
                    <x-admin-panel::button :href="route('crm.tasks.create')" icon="plus">New Task</x-admin-panel::button>
                @endcan
            </div>
        </header>

        <x-admin-panel::card>
            <form method="GET" action="{{ request()->url() }}" class="crm-filter-grid">
                <x-admin-panel::input name="search" label="Search" :value="$filters['search']" placeholder="Title or description" />
                <x-admin-panel::select name="assigned_to" label="Assignee" :options="$owners" :selected="$filters['assigned_to']" placeholder="All assignees" />
                <x-admin-panel::select name="priority" label="Priority" :options="$priorities" :selected="$filters['priority']" placeholder="All priorities" />
                <x-admin-panel::select name="status" label="Status" :options="$statuses" :selected="$filters['status']" placeholder="All statuses" />
                <x-admin-panel::input name="due_from" label="Due From" type="date" :value="$filters['due_from']" />
                <x-admin-panel::input name="due_to" label="Due To" type="date" :value="$filters['due_to']" />

                <div class="crm-filter-actions">
                    <x-admin-panel::button type="submit" icon="search">Apply</x-admin-panel::button>
                    <x-admin-panel::button :href="request()->url()" variant="ghost">Reset</x-admin-panel::button>
                </div>
            </form>
        </x-admin-panel::card>

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
                ['label' => 'Actions', 'width' => '180px'],
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
                        <td>{{ $task->due_at?->format('Y-m-d H:i') ?: '-' }}</td>
                        <td>{{ ucfirst($task->priority) }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $task->status)) }}</td>
                        <td>
                            <div class="crm-row-actions">
                                <x-admin-panel::button :href="route('crm.tasks.show', $task)" size="sm" variant="ghost" icon="eye" />
                                @can('update', $task)
                                    <x-admin-panel::button :href="route('crm.tasks.edit', $task)" size="sm" variant="ghost" icon="pencil" />
                                @endcan
                                @can('complete', $task)
                                    @if($task->status !== 'completed')
                                        <form method="POST" action="{{ route('crm.tasks.complete', $task) }}">
                                            @csrf
                                            @method('PATCH')
                                            <x-admin-panel::button type="submit" size="sm" variant="ghost" icon="check" />
                                        </form>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="crm-empty">No tasks found.</td>
                    </tr>
                @endforelse
            </x-admin-panel::table>

            <div class="crm-pagination">
                {{ $tasks->links() }}
            </div>
        </x-admin-panel::card>
    </section>
@endsection
