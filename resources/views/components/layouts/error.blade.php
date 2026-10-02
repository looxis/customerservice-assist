@props(['title'])

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} – {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-dvh items-center justify-center p-4">
    <main class="w-full max-w-md">
        <p class="mb-4 text-center font-display text-lg font-semibold text-slate-900">{{ config('app.name') }}</p>
        <x-card class="text-center">
            <h1 class="text-base font-semibold text-slate-900">{{ $title }}</h1>
            <div class="mt-2 text-sm text-slate-600">{{ $slot }}</div>
            @isset($action)
                <div class="mt-6">{{ $action }}</div>
            @endisset
        </x-card>
    </main>
</body>
</html>
