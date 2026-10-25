<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Laravel') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="site-page">
        <div class="guest-page">
            <a class="guest-page__brand" href="{{ url('/') }}"><x-application_logo class="guest-page__logo" /></a>
            <main class="auth-panel">{{ $slot }}</main>
        </div>
    </body>
</html>
