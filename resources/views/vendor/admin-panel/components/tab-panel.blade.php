@props([
    'id' => '',
])

<div
    x-show="activeTab === '{{ $id }}'"
    role="tabpanel"
    {{ $attributes }}
    style="display: none;"
>
    {{ $slot }}
</div>
