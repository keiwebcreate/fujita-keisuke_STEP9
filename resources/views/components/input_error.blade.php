@props(['messages'])
@if ($messages)
    <ul {{ $attributes->merge(['class' => 'form-field__errors']) }}>
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
