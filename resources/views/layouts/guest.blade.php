<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">

        <title>{{ $title ?? config('app.name', 'SIM SDM RS Juwita') }}</title>

        <!-- Web App Manifest & Mobile App Capabilities -->
        <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="RS Juwita">
        <link rel="apple-touch-icon" href="{{ asset('assets/icons/apple-touch-icon.png') }}">
        <meta name="theme-color" content="#059669" media="(prefers-color-scheme: light)">
        <meta name="theme-color" content="#09090b" media="(prefers-color-scheme: dark)">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

        <link rel="icon" type="image/png" href="{{ asset('assets/icons/icon-192.png') }}">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance
    </head>
    <body class="min-h-full bg-slate-50 dark:bg-zinc-950 text-zinc-800 dark:text-zinc-200 antialiased selection:bg-emerald-500 selection:text-white">
        {{ $slot }}

        @fluxScripts
    </body>
</html>
