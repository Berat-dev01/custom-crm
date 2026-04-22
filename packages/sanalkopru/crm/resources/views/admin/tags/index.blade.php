@extends('admin-panel::layouts.app')

@section('title', 'Tags')
@section('page-title', 'Tags')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
    <section class="crm-admin-page" data-crm-module="tags">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">CRM</p>
                <h1>Tags</h1>
            </div>

            @can('crm.tags.create')
                <x-admin-panel::button :href="route('crm.tags.create')" icon="plus">New Tag</x-admin-panel::button>
            @endcan
        </header>

        <x-admin-panel::card>
            <form method="GET" action="{{ route('crm.tags.index') }}" class="crm-filter-grid">
                <x-admin-panel::input name="search" label="Search" :value="$filters['search']" placeholder="Name or slug" />
                <div class="crm-filter-actions">
                    <x-admin-panel::button type="submit" icon="search">Apply</x-admin-panel::button>
                    <x-admin-panel::button :href="route('crm.tags.index')" variant="ghost">Reset</x-admin-panel::button>
                </div>
            </form>
        </x-admin-panel::card>

        <x-admin-panel::card>
            <x-slot:header>
                Tags
            </x-slot:header>

            <x-admin-panel::table :headers="[
                ['label' => 'Tag'],
                ['label' => 'Slug'],
                ['label' => 'Usage'],
                ['label' => 'Actions', 'width' => '160px'],
            ]">
                @forelse($tags as $tag)
                    <tr>
                        <td>
                            <span class="crm-color-swatch" style="background: {{ $tag->color }}"></span>
                            <strong>{{ $tag->name }}</strong>
                        </td>
                        <td>{{ $tag->slug }}</td>
                        <td>
                            <span class="crm-muted">
                                {{ $tag->contacts_count }} contacts,
                                {{ $tag->companies_count }} companies,
                                {{ $tag->deals_count }} deals,
                                {{ $tag->quotes_count }} quotes
                            </span>
                        </td>
                        <td>
                            <div class="crm-row-actions">
                                <x-admin-panel::button :href="route('crm.tags.show', $tag)" size="sm" variant="ghost" icon="eye" />
                                @can('update', $tag)
                                    <x-admin-panel::button :href="route('crm.tags.edit', $tag)" size="sm" variant="ghost" icon="pencil" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">
                            @include('crm::admin.partials.empty-state', [
                                'title' => 'No tags found.',
                                'body' => 'Use tags to segment accounts, opportunities and contacts.',
                                'actionUrl' => route('crm.tags.create'),
                                'actionLabel' => 'New Tag',
                                'actionPermission' => 'crm.tags.create',
                            ])
                        </td>
                    </tr>
                @endforelse
            </x-admin-panel::table>

            <div class="crm-pagination">
                {{ $tags->links() }}
            </div>
        </x-admin-panel::card>
    </section>
@endsection
