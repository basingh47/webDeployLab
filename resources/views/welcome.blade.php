<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Laravel') }}</title>

        {{-- Tailwind CSS + JS, built by Vite (npm run dev / npm run build) --}}
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-100 text-slate-800 flex items-center justify-center p-6">
        <main class="w-full max-w-xl bg-white rounded-2xl shadow-lg p-8">
            <h1 class="text-3xl font-semibold text-indigo-600">Hello World from Laravel</h1>
            <p class="mt-2 text-slate-500">webDeployLab: Laravel {{ app()->version() }} on Docker, styled with Tailwind CSS.</p>

            <ul class="mt-6 space-y-2">
                <li class="rounded-lg bg-slate-50 px-4 py-2">Stage 1: HTML + Nginx</li>
                <li class="rounded-lg bg-slate-50 px-4 py-2">Stage 2: Laravel + PHP-FPM</li>
                <li class="rounded-lg bg-slate-50 px-4 py-2">Stage 3: HTTPS with Let's Encrypt</li>
                <li class="rounded-lg bg-indigo-50 px-4 py-2 text-indigo-700 font-medium">Stage 4: Tailwind with Vite</li>
            </ul>
        </main>
    </body>
</html>
