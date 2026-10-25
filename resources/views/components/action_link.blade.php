@props(['href', 'variant' => 'primary'])
@php
    $variant_class = match ($variant) {
        'detail' => 'button--detail',
        'secondary' => 'button--secondary',
        default => 'button--primary',
    };
@endphp
<a href="{{ $href }}" {{ $attributes->merge(['class' => 'button '.$variant_class]) }}>{{ $slot }}</a>
