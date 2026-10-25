@props(['active' => false])
<a @if ($active) aria-current="page" @endif {{ $attributes->class(['navigation-link', 'navigation-link--active' => $active]) }}>{{ $slot }}</a>
