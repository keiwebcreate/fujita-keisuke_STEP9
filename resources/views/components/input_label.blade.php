@props(['value'])
<label {{ $attributes->merge(['class' => 'form-field__label']) }}>{{ $value ?? $slot }}</label>
