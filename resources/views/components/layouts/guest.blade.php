<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-white font-sans text-zinc-900 antialiased">
<div class="flex min-h-full">
    {{-- Brand panel --}}
    <div class="relative hidden w-1/2 overflow-hidden lg:block">
        <img src="{{ asset('images/UKMBG.jpg') }}" alt="" class="absolute inset-0 h-full w-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-br from-zinc-900/90 via-zinc-900/70 to-brand-900/80"></div>
        <div class="relative flex h-full flex-col justify-between p-12 text-white">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/ukm.png') }}" alt="UKM" class="h-12 w-12 rounded-lg bg-white object-contain p-1">
                <span class="text-lg font-semibold">Digital LogBook<span class="text-brand-500">+</span></span>
            </div>
            <div class="max-w-md">
                <h2 class="text-4xl font-bold leading-tight">Weekly project logs, reviewed and signed online.</h2>
                <p class="mt-4 text-zinc-300">Students record their weekly progress. Supervisors comment and sign. No more paper logbooks.</p>
            </div>
            <p class="text-sm text-zinc-400">Universiti Kebangsaan Malaysia</p>
        </div>
    </div>

    {{-- Form panel --}}
    <div class="flex flex-1 flex-col justify-center px-6 py-12 sm:px-12 lg:px-20">
        <div class="mx-auto w-full max-w-sm">
            <div class="mb-8 flex items-center gap-3 lg:hidden">
                <img src="{{ asset('images/ukm.png') }}" alt="UKM" class="h-10 w-10 object-contain">
                <span class="text-lg font-semibold">Digital LogBook<span class="text-brand-600">+</span></span>
            </div>
            {{ $slot }}
        </div>
    </div>
</div>
</body>
</html>
