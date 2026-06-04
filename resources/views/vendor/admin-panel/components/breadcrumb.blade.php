@props([
    'items' => [],           // Array of breadcrumb items: [['label' => 'Home', 'url' => '/'], ...]
])

@php
    $attributes = $attributes->class(['d-flex items-center gap-2 text-sm']);
@endphp

<nav {{ $attributes }} aria-label="{{ admin_trans('Breadcrumb') }}">
    <ol class="d-flex items-center gap-2 m-0 p-0" style="list-style: none;">
        @foreach($items as $index => $item)
            <li class="d-flex items-center gap-2">
                @if($index > 0)
                    <i data-lucide="chevron-right" width="14" height="14" class="text-muted"></i>
                @endif

                @if(isset($item['url']) && !$loop->last)
                    <a href="{{ $item['url'] }}" class="text-secondary hover:text-primary">
                        {{ $item['label'] }}
                    </a>
                @else
                    <span class="text-primary font-medium">
                        {{ $item['label'] }}
                    </span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
