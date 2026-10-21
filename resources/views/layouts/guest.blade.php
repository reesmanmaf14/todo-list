<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Crema') }}</title>

        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    {{-- Login, register and password pages: the same Warm Ivory & Espresso look as the rest of the app --}}
    <body class="font-sans text-crema-roast antialiased bg-crema-ivory">
        <div class="min-h-screen flex flex-col justify-center items-center px-4 py-10">
            <a href="/" class="flex flex-col items-center gap-3 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-crema-espresso focus-visible:ring-offset-4 focus-visible:ring-offset-crema-ivory">
                <x-application-logo class="w-14 h-14 text-crema-espresso" />
                <span class="text-2xl font-semibold tracking-wide">{{ config('app.name', 'Crema') }}</span>
            </a>

            

            <div class="w-full sm:max-w-md mt-8 px-6 py-7 sm:px-8 bg-white border border-crema-latte rounded-xl shadow-[0_1px_2px_rgba(48,39,32,0.04),0_6px_20px_rgba(48,39,32,0.06)]">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
