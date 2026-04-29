<div class="crm-stack">
    <div class="crm-row-actions">
        @forelse($savedFilters as $savedFilter)
            <x-admin-panel::button :href="route('crm.saved-filters.apply', $savedFilter)" size="sm" variant="outline" icon="filter" data-admin-ajax-link>
                {{ $savedFilter->name }}
            </x-admin-panel::button>
            @if($savedFilter->user_id === auth()->id())
                <form method="POST" action="{{ route('crm.saved-filters.destroy', $savedFilter) }}" data-crm-confirm="{{ __('Delete this saved filter?') }}">
                    @csrf
                    @method('DELETE')
                    <x-admin-panel::button type="submit" size="sm" variant="ghost" icon="x" />
                </form>
            @endif
        @empty
            <span class="crm-muted">{{ __('No saved filters yet.') }}</span>
        @endforelse
    </div>

    @can('crm.'.$module.'.view')
        <form method="POST" action="{{ route('crm.saved-filters.store') }}" class="crm-filter-grid">
            @csrf
            <input type="hidden" name="module" value="{{ $module }}">
            @foreach($filters as $key => $value)
                @if($value !== null && $value !== '')
                    <input type="hidden" name="filters[{{ $key }}]" value="{{ $value }}">
                @endif
            @endforeach
            <x-admin-panel::input name="name" label="Name" placeholder="My active filter" required />
            <x-admin-panel::select
                name="visibility"
                label="Visibility"
                selected="private"
                :options="app(\App\Crm\Support\CrmLabelCatalog::class)->savedFilterVisibilities()"
                required
            />
            <div class="crm-filter-actions">
                <x-admin-panel::button type="submit" icon="save">{{ __('Save Current Filter') }}</x-admin-panel::button>
            </div>
        </form>
    @endcan
</div>
