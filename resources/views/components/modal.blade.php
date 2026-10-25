@props(['name', 'show' => false, 'maxWidth' => '2xl'])
@php
    $width_class = match ($maxWidth) {
        'sm' => 'modal--small',
        'md' => 'modal--medium',
        'lg' => 'modal--large',
        'xl' => 'modal--wide',
        default => 'modal--extra-wide',
    };
@endphp
<dialog id="{{ $name }}" class="modal {{ $width_class }}" data-modal @if ($show) data-modal-initial-open @endif aria-labelledby="{{ $name }}Title">
    {{ $slot }}
</dialog>
