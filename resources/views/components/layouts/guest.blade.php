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
    <div class="relative hidden w-1/2 overflow-hidden bg-zinc-950 lg:block">
        <img src="{{ asset('images/UKMBG.jpg') }}" alt="" class="absolute inset-0 h-full w-full scale-105 object-cover opacity-60 transition duration-[20s] ease-linear hover:scale-110">
        <div class="absolute inset-0 bg-gradient-to-br from-zinc-950/95 via-zinc-950/75 to-brand-900/80"></div>
        <div class="bg-grid absolute inset-0 [mask-image:radial-gradient(ellipse_at_bottom_left,black,transparent_70%)]"></div>
        <div class="relative flex h-full flex-col justify-between p-12 text-white xl:p-16">
            <div class="flex animate-fade-in items-center gap-3.5">
                <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white p-1.5 shadow-xl">
                    <img src="{{ asset('images/ukm.png') }}" alt="UKM" class="h-full w-full object-contain">
                </span>
                <span class="leading-tight">
                    <span class="block text-lg font-bold">Digital LogBook<span class="text-brand-500">+</span></span>
                    <span class="block text-sm text-zinc-400">Universiti Kebangsaan Malaysia</span>
                </span>
            </div>

            <div class="max-w-lg animate-fade-up">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-xs font-medium ring-1 ring-white/15">
                    <x-icon name="academic-cap" class="h-4 w-4 text-brand-500" /> Final Year Project logbook
                </span>
                <h2 class="mt-5 text-4xl leading-tight font-bold tracking-tight xl:text-5xl">Weekly project logs, reviewed and signed online.</h2>
                <p class="mt-4 text-lg text-zinc-300">Students record their weekly progress. Supervisors comment and sign. No more paper logbooks.</p>

                <ul class="mt-10 space-y-3">
                    @foreach ([['document', 'Write one entry per week, from anywhere'], ['shield', 'Supervisor comments and digital signature'], ['chart', 'Track progress across the whole semester']] as $i => [$icon, $text])
                        <li class="flex animate-fade-up items-center gap-3 text-zinc-200" style="animation-delay: {{ 200 + $i * 120 }}ms">
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/15"><x-icon :name="$icon" class="h-5 w-5 text-brand-500" /></span>
                            {{ $text }}
                        </li>
                    @endforeach
                </ul>
            </div>

            <p class="text-sm text-zinc-500">&copy; {{ date('Y') }} Digital LogBook+</p>
        </div>
    </div>

    {{-- Form panel --}}
    <div class="flex flex-1 flex-col justify-center bg-white px-6 py-12 sm:px-12 lg:px-20">
        <div class="mx-auto w-full max-w-sm animate-fade-up">
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
