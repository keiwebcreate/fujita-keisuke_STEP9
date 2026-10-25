{{-- 入力エラーの一覧を表示する共通部品です。項目ごとのメッセージを受け取ります。 --}}
@props(['messages'])
@if ($messages)
    <ul {{ $attributes->merge(['class' => 'form-field__errors']) }}>
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
