<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Laravel') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="site-page site-page--guest">
        <nav class="guest-navigation" aria-label="認証ナビゲーション">
            <div class="guest-navigation__container">
                <a class="guest-navigation__brand" href="{{ route('home') }}">{{ config('app.name') }}</a>
                <div class="guest-navigation__links">
                    <a href="{{ route('login') }}">ログイン</a>
                    <a href="{{ route('register') }}">新規登録</a>
                </div>
            </div>
        </nav>
        <div class="guest-page">
            <main class="auth-panel">{{ $slot }}</main>
        </div>
    </body>
</html>
