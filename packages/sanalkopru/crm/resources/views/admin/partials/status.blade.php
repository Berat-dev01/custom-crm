@if(session('crm_status'))
    <x-admin-panel::alert variant="success">
        {{ session('crm_status') }}
    </x-admin-panel::alert>
@endif
