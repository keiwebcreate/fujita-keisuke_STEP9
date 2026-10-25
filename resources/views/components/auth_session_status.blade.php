@props(['status'])
@if ($status)
    <div role="status" {{ $attributes->merge(['class' => 'status-message status-message--success']) }}>{{ $status }}</div>
@endif
