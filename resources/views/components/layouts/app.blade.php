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
            <x-nav-item :href="route('knowledge.index')" icon="layers" :active="request()->routeIs('knowledge.*')">Knowledge</x-nav-item>
        </nav>

        <nav class="shrink-0 space-y-1 border-t border-slate-200 px-3 py-3" aria-label="Weitere Seiten">
            <x-nav-item :href="route('about')" icon="help" :active="request()->routeIs('about')">Über die App</x-nav-item>
        </nav>

        <div class="shrink-0 border-t border-slate-200 p-4 font-mono text-xs text-slate-400">
            Version {{ config('app.version') }}
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="flex h-[60px] shrink-0 items-center justify-between gap-4 border-b border-slate-200 bg-white px-4 md:px-8">
            <h1 class="min-w-0 truncate font-display text-lg font-semibold text-slate-900">{{ $title }}</h1>
            <div class="flex shrink-0 items-center gap-3">
                @if ($testModeAvailable)
                    <form method="POST" action="{{ route('test-mode.switch') }}">
                        @csrf
                        <input type="hidden" name="aktiv" value="{{ $testModeActive ? '0' : '1' }}">
                        <button type="submit" role="switch" aria-checked="{{ $testModeActive ? 'true' : 'false' }}"
                                class="inline-flex items-center gap-2 rounded-md px-2 py-1 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-brand">
                            <span @class(['relative inline-flex h-5 w-9 shrink-0 rounded-full transition', 'bg-warning-500' => $testModeActive, 'bg-slate-300' => ! $testModeActive])>
                                <span @class(['absolute top-0.5 size-4 rounded-full bg-white shadow-1 transition', 'left-4.5' => $testModeActive, 'left-0.5' => ! $testModeActive])></span>
                            </span>
                            Testmodus
                        </button>
                    </form>
                @endif
                <x-staff.picker :names="$staffNames" :current="$currentStaff" />
            </div>
        </header>

        @if ($testModeActive)
            <div class="shrink-0 border-b border-warning-500/30 bg-warning-500/10 px-4 py-2 text-sm font-medium text-warning-700 md:px-8" role="status">
                Testmodus aktiv – bei Tickets kannst du an jeder Kundennachricht „Bis hierher testen" wählen.
            </div>
        @endif

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
