{{-- 入力欄の見出しです。forと入力欄のidを合わせると、見出しを押して入力欄へ移れます。 --}}
@props(['value'])
<label {{ $attributes->merge(['class' => 'form-field__label']) }}>{{ $value ?? $slot }}</label>
