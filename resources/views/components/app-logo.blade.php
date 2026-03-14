@props([
    'sidebar' => false,
])

@if ($sidebar)
    <flux:sidebar.brand name="RStack" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md overflow-hidden">
            <img src="/img/logo-vierkant-rstack.png" alt="RStack" class="size-8 object-contain" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="RStack" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md overflow-hidden">
            <img src="/img/logo-vierkant-rstack.png" alt="RStack" class="size-8 object-contain" />
        </x-slot>
    </flux:brand>
@endif
