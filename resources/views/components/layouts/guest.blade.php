<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['app-bg h-full', 'theme-dark' => request()->cookie('theme', 'dark') !== 'light'])>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-bg h-full font-sans text-zinc-900 antialiased">
<div class="flex min-h-full">
    {{-- Brand panel --}}
    <div class="relative hidden w-1/2 overflow-hidden bg-brand-950 lg:block">
        <img src="{{ asset('images/UKMBG.jpg') }}" alt="" class="absolute inset-0 h-full w-full scale-105 object-cover opacity-60 transition duration-[20s] ease-linear hover:scale-110">
        <div class="absolute inset-0 bg-gradient-to-br from-brand-950/95 via-brand-950/80 to-brand-700/70"></div>
        <div class="bg-grid absolute inset-0 [mask-image:radial-gradient(ellipse_at_bottom_left,black,transparent_70%)]"></div>
        <div class="relative flex h-full flex-col justify-between p-12 text-white xl:p-16">
            <div class="flex animate-fade-in items-center gap-3.5">
                <span class="flex h-14 w-14 items-center justify-center keep-light rounded-2xl bg-white p-1.5 shadow-xl">
                    <img src="{{ asset('images/ukm.png') }}" alt="UKM" class="h-full w-full object-contain">
                </span>
                <span class="leading-tight">
                    <span class="block text-lg font-bold">{{ __('Digital LogBook') }}<span class="text-ukm-gold">+</span></span>
                    <span class="block text-sm text-slate-300">{{ __('Universiti Kebangsaan Malaysia') }}</span>
                </span>
            </div>

            <div class="max-w-lg animate-fade-up">
                <h2 class="text-4xl leading-tight font-bold tracking-tight xl:text-5xl">{{ __('Every week of your FYP,') }} <span class="text-ukm-gold">{{ __('logged and signed.') }}</span></h2>
                <p class="mt-4 text-lg text-slate-200">{{ __('Record your progress, get feedback from your supervisor, and keep your whole semester in one place.') }}</p>
            </div>

            <p class="text-sm text-slate-400">&copy; {{ date('Y') }} Digital LogBook+</p>
        </div>
    </div>

    {{-- Form panel --}}
    <div class="app-bg flex flex-1 flex-col justify-center px-6 py-12 sm:px-12 lg:px-20">
        <div class="mx-auto w-full max-w-sm animate-fade-up">
            <div class="mb-6 flex justify-end"><x-locale-switch /></div>
            <div class="mb-8 flex items-center gap-3 lg:hidden">
                <img src="{{ asset('images/ukm.png') }}" alt="UKM" class="h-10 w-10 object-contain">
                <span class="text-lg font-semibold">{{ __('Digital LogBook') }}<span class="text-ukm-gold">+</span></span>
            </div>
            <x-flash />
            {{ $slot }}
        </div>
    </div>
</div>
</body>
</html>
