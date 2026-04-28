@can('crm.dashboard.view')
    <form method="GET" action="{{ route('crm.search') }}" class="crm-global-search" role="search">
        <label class="crm-sr-only" for="crm-global-search-input">{{ __('Search CRM') }}</label>
        <input
            id="crm-global-search-input"
            name="q"
            type="search"
            value="{{ request('q') }}"
            placeholder="{{ __('Search contacts, companies, deals or quotes') }}"
            autocomplete="off"
            data-crm-global-search
        >
        <x-admin-panel::button type="submit" icon="search">Search</x-admin-panel::button>
    </form>
@endcan
