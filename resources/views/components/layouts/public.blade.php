<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Support Assistant') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen flex flex-col">
        <header class="bg-white border-b">
            <div class="max-w-3xl mx-auto px-4 py-4">
                <h1 class="text-lg font-semibold text-gray-800">Support Assistant</h1>
            </div>
        </header>

        <main class="flex-1">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>
</html>