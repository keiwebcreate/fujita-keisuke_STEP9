{{-- 文字入力欄の共通部品です。呼出元から渡されたidやname、現在値などを反映します。 --}}
@props(['disabled' => false])
<input @disabled($disabled) {{ $attributes->merge(['class' => 'form-field__input']) }}>
