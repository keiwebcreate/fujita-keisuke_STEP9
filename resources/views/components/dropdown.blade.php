@props(['align' => 'right', 'width' => '48'])
<div data-dropdown {{ $attributes->class(['dropdown', 'dropdown--left' => $align === 'left', 'dropdown--wide' => $width !== '48']) }}>
    <div data-dropdown-trigger>{{ $trigger }}</div>
    <div class="dropdown__panel" data-dropdown-panel hidden>
        <div class="dropdown__content">{{ $content }}</div>
    </div>
</div>
