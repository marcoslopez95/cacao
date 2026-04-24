<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      data-theme="light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Detect theme before paint to avoid flash --}}
        <script>
            (function() {
                try {
                    var stored = localStorage.getItem('cacao-theme');
                    if (stored) {
                        document.documentElement.setAttribute('data-theme', stored);
                    } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                        document.documentElement.setAttribute('data-theme', 'dark');
                    }
                } catch (_) {}
            })();
        </script>

        {{-- Prevent background flash while CSS loads --}}
        <style>
            html { background-color: #EDEBE7; }
            html[data-theme="dark"] { background-color: #1C1A18; }
        </style>

        <!-- Favicon -->
        <link rel="icon" type="image/x-icon" href="/favicon.ico">
        <link rel="icon" type="image/svg+xml" href="/img/brand/favicon.svg">
        <link rel="icon" type="image/png" sizes="32x32" href="/img/brand/favicon-32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/img/brand/favicon-16.png">

        <!-- Apple Touch Icon -->
        <link rel="apple-touch-icon" sizes="180x180" href="/img/brand/apple-touch-icon.png">

        <!-- PWA Manifest -->
        <link rel="manifest" href="/site.webmanifest">
        <meta name="theme-color" content="#C8521A">

        <!-- Open Graph -->
        <meta property="og:image" content="{{ asset('img/brand/og-image.png') }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:site_name" content="CACAO">

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:image" content="{{ asset('img/brand/og-image.png') }}">

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body>
        <x-inertia::app />
    </body>
</html>
