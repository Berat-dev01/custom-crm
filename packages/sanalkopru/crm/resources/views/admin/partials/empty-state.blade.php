<div class="crm-empty-state">
    <strong>{{ __($title) }}</strong>
    @isset($body)
        <p>{{ __($body) }}</p>
    @endisset
    @isset($actionUrl)
        @if(empty($actionPermission) || \Illuminate\Support\Facades\Gate::allows($actionPermission))
            <x-admin-panel::button :href="$actionUrl" icon="{{ $actionIcon ?? 'plus' }}" variant="{{ $actionVariant ?? 'outline' }}">
                {{ $actionLabel ?? 'Create' }}
            </x-admin-panel::button>
        @endif
    @endisset
</div>
