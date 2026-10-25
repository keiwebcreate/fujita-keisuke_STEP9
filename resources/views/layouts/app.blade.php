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
        @include('layouts.navigation')
        @isset($header)
            <header class="page-header"><div class="page-header__container">{{ $header }}</div></header>
        @endisset
        <main>{{ $slot }}</main>
    </body>
</html>
