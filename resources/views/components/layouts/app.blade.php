@props(['title', 'width' => '3xl'])

@php
    $maxWidth = match ($width) {
        '5xl' => 'max-w-5xl',
        '6xl' => 'max-w-6xl',
        default => 'max-w-3xl',
    };
@endphp

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} – {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex h-dvh overflow-hidden">
    <aside class="hidden w-[250px] shrink-0 flex-col border-r border-slate-200 bg-white md:flex">
        <div class="flex h-[60px] shrink-0 items-center px-6">
            <a href="{{ route('tickets.analyze') }}" class="truncate font-display text-lg font-semibold text-slate-900">{{ config('app.name') }}</a>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3" aria-label="Hauptnavigation">
            <x-nav-item :href="route('tickets.analyze')" icon="sparkle" :active="request()->routeIs('tickets.*')">Ticket analysieren</x-nav-item>
        </nav>

        <div class="shrink-0 border-t border-slate-200 p-4 font-mono text-xs text-slate-400">
            Version {{ config('app.version') }}
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="flex h-[60px] shrink-0 items-center justify-between gap-4 border-b border-slate-200 bg-white px-4 md:px-8">
            <h1 class="min-w-0 truncate font-display text-lg font-semibold text-slate-900">{{ $title }}</h1>
            <div class="flex shrink-0 items-center gap-3">{{ $user ?? '' }}</div>
        </header>

        <main class="flex-1 overflow-y-auto px-4 py-8 md:px-8">
            <div class="mx-auto {{ $maxWidth }} space-y-6">
                @if (session('success'))
                    <x-alert type="success">{{ session('success') }}</x-alert>
                @endif
                @if (session('error'))
                    <x-alert type="error">{{ session('error') }}</x-alert>
                @endif

                {{ $slot }}
            </div>
        </main>
    </div>

    <x-loading-overlay />
</body>
</html>
