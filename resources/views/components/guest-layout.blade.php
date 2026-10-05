@props(['title' => null])
<!doctype html>
<html lang="pt-br">
    <head>
        <meta charset="utf-8" />
        <title>{{ $title ? "{$title} | " . config('app.name') : config('app.name') }}</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="color-scheme" content="light dark" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="login-page bg-body-secondary">
        {{ $slot }}
    </body>
</html>
