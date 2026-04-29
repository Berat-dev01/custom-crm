@once
    @push('scripts')
        @include('crm::admin.partials.translations')
        @vite('resources/js/crm.js')
    @endpush
@endonce

@if(session('crm_status'))
    <x-admin-panel::alert variant="success" class="crm-toast" data-crm-toast>
        {{ session('crm_status') }}
    </x-admin-panel::alert>
@endif

@php($crmErrors = $errors->any() ? $errors : session('errors'))

@if($crmErrors && $crmErrors->any())
    <x-admin-panel::alert variant="danger" class="crm-error-state" data-crm-error-state>
        <strong>{{ __('Please review the highlighted fields.') }}</strong>
        <span>{{ $crmErrors->first() }}</span>
    </x-admin-panel::alert>
@endif
