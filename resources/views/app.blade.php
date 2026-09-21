<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" style="color-scheme: light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Light theme only — strip any leftover dark class before paint --}}
        <script>
            (function() {
                document.documentElement.classList.remove('dark');
                document.documentElement.style.colorScheme = 'light';
            })();
        </script>

        @php
            $applicationSettings = app(\App\Services\Application\ApplicationSettingsService::class);
            $themeColor = (string) config('pwa.theme_color', '#C4472F');
            $shortDescription = $applicationSettings->shortDescription();
            $hasCustomFavicon = $applicationSettings->hasCustomFavicon();
        @endphp

        <style>
            html {
                background-color: oklch(1 0 0);
            }

            :root {
                --primary: {{ $themeColor }};
                --ring: {{ $themeColor }};
                --sidebar-primary: {{ $themeColor }};
                --sidebar-ring: {{ $themeColor }};
                --chart-1: {{ $themeColor }};
            }
        </style>

        <link rel="icon" href="{{ $applicationSettings->faviconHref() }}" sizes="any">
        @unless ($hasCustomFavicon)
            <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        @endunless
        <link rel="apple-touch-icon" href="{{ $applicationSettings->appleTouchIconHref() }}">
        <link rel="manifest" href="{{ route('pwa.manifest') }}">
        <meta name="theme-color" content="{{ $themeColor }}">
        @if ($shortDescription)
            <meta name="description" content="{{ $shortDescription }}">
        @endif
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="{{ config('pwa.name') }}">
        @foreach (\App\Support\PwaAppleSplash::links() as $splash)
            <link rel="apple-touch-startup-image" href="{{ $splash['href'] }}" media="{{ $splash['media'] }}">
        @endforeach

        @fonts

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
