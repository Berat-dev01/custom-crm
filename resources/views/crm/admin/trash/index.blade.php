@extends('crm::layouts.app')

@section('title', __('Trash'))
@section('page-title', __('Trash'))


@section('content')
    <section class="crm-admin-page" data-crm-module="trash">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">{{ __('CRM / System') }}</p>
                <h1>{{ __('Trash') }}</h1>
                <p class="crm-muted">{{ __('Deleted records can be restored or removed permanently.') }}</p>
            </div>
        </header>

        <x-admin-panel::card>
            <div class="crm-admin-actions">
                @foreach($modules as $item)
                    <x-admin-panel::button
                        :href="route('crm.trash.index', ['module' => $item])"
                        :variant="$item === $module ? 'primary' : 'outline'"
                    >
                        {{ __(ucfirst($item)) }}
                    </x-admin-panel::button>
                @endforeach
            </div>
        </x-admin-panel::card>

        <x-admin-panel::card>
            <x-slot:header>{{ __('Deleted records') }}</x-slot:header>

            <x-admin-panel::table :headers="[
                ['label' => __('Record')],
                ['label' => __('Deleted at')],
                ['label' => __('Actions'), 'width' => '260px'],
            ]">
                @forelse($records as $record)
                    <tr>
                        <td><strong>{{ $record->{$labelField} ?? ('#'.$record->id) }}</strong></td>
                        <td>
                            <div>{{ $record->deleted_at->format('d.m.Y H:i') }}</div>
                            <div class="crm-muted">{{ $record->deleted_at->diffForHumans() }}</div>
                        </td>
                        <td>
                            <div class="crm-admin-actions">
                                <form method="POST" action="{{ route('crm.trash.restore', ['module' => $module, 'id' => $record->id]) }}">
                                    @csrf
                                    <x-admin-panel::button type="submit" variant="outline" icon="undo-2">
                                        {{ __('Restore') }}
                                    </x-admin-panel::button>
                                </form>
                                <form method="POST" action="{{ route('crm.trash.destroy', ['module' => $module, 'id' => $record->id]) }}" data-admin-confirm="{{ __('Permanently delete this record? This cannot be undone.') }}">
                                    @csrf
                                    @method('DELETE')
                                    <x-admin-panel::button type="submit" variant="outline" icon="trash-2">
                                        {{ __('Delete forever') }}
                                    </x-admin-panel::button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">
                            @include('crm::admin.partials.empty-state', [
                                'title' => __('Trash is empty'),
                                'body' => __('Deleted records for this module will appear here.'),
                            ])
                        </td>
                    </tr>
                @endforelse
            </x-admin-panel::table>

            <x-admin-panel::pagination :paginator="$records" class="crm-pagination" />
        </x-admin-panel::card>
    </section>
@endsection
