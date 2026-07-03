@extends('crm::layouts.app')

@section('title', __('Audit Log'))
@section('page-title', __('Audit Log'))


@section('content')
    <section class="crm-admin-page" data-crm-module="audit-logs">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">{{ __('CRM / System') }}</p>
                <h1>{{ __('Audit Log') }}</h1>
                <p class="crm-muted">{{ __('Who changed what and when across the CRM.') }}</p>
            </div>
        </header>

        <x-admin-panel::card>
            <form method="GET" action="{{ route('crm.audit-logs.index') }}" class="crm-filter-row">
                <div class="form-group">
                    <label for="filter-event">{{ __('Event') }}</label>
                    <input id="filter-event" type="text" name="event" value="{{ $filters['event'] ?? '' }}" class="form-control" placeholder="{{ __('e.g. deal.won') }}">
                </div>
                <div class="form-group">
                    <label for="filter-user">{{ __('User') }}</label>
                    <select id="filter-user" name="user_id" class="form-control">
                        <option value="">{{ __('All users') }}</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected(($filters['user_id'] ?? null) == $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="filter-from">{{ __('From') }}</label>
                    <input id="filter-from" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control">
                </div>
                <div class="form-group">
                    <label for="filter-to">{{ __('To') }}</label>
                    <input id="filter-to" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control">
                </div>
                <div class="crm-form-actions">
                    <x-admin-panel::button type="submit" icon="filter">{{ __('Filter') }}</x-admin-panel::button>
                    <x-admin-panel::button :href="route('crm.audit-logs.index')" variant="ghost">{{ __('Reset') }}</x-admin-panel::button>
                </div>
            </form>
        </x-admin-panel::card>

        <x-admin-panel::card>
            <x-slot:header>{{ __('Entries') }}</x-slot:header>

            <x-admin-panel::table :headers="[
                ['label' => __('When')],
                ['label' => __('User')],
                ['label' => __('Event')],
                ['label' => __('Record')],
                ['label' => __('Changes')],
            ]">
                @forelse($logs as $log)
                    <tr>
                        <td>
                            <div>{{ $log->created_at->format('d.m.Y H:i') }}</div>
                            <div class="crm-muted">{{ $log->created_at->diffForHumans() }}</div>
                        </td>
                        <td>{{ $log->user?->name ?? __('System') }}</td>
                        <td><code>{{ $log->event }}</code></td>
                        <td>
                            @if($log->auditable_type)
                                {{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if(! empty($log->new_values) || ! empty($log->old_values))
                                <details>
                                    <summary>{{ __(':count field(s)', ['count' => count($log->new_values ?: $log->old_values ?: [])]) }}</summary>
                                    <dl class="crm-audit-diff">
                                        @foreach(array_unique(array_merge(array_keys($log->old_values ?? []), array_keys($log->new_values ?? []))) as $field)
                                            <dt>{{ $field }}</dt>
                                            <dd>
                                                <span class="crm-audit-old">{{ json_encode($log->old_values[$field] ?? null, JSON_UNESCAPED_UNICODE) }}</span>
                                                &rarr;
                                                <span class="crm-audit-new">{{ json_encode($log->new_values[$field] ?? null, JSON_UNESCAPED_UNICODE) }}</span>
                                            </dd>
                                        @endforeach
                                    </dl>
                                </details>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            @include('crm::admin.partials.empty-state', [
                                'title' => __('No audit entries yet'),
                                'body' => __('Changes made in the CRM will be recorded here automatically.'),
                            ])
                        </td>
                    </tr>
                @endforelse
            </x-admin-panel::table>

            <x-admin-panel::pagination :paginator="$logs" class="crm-pagination" />
        </x-admin-panel::card>
    </section>
@endsection
