<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-zinc-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-zinc-900 antialiased">
@php($user = auth()->user())

{{-- Mobile backdrop --}}
<div data-sidebar-backdrop data-sidebar-toggle class="fixed inset-0 z-30 hidden bg-zinc-900/50 lg:hidden"></div>

{{-- Sidebar --}}
<aside data-sidebar class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-zinc-900 transition-transform lg:translate-x-0">
    <div class="flex h-16 items-center gap-3 border-b border-white/10 px-5">
        <img src="{{ asset('images/ukm.png') }}" alt="UKM" class="h-9 w-9 rounded-md bg-white object-contain p-0.5">
        <div class="leading-tight">
            <p class="text-sm font-semibold text-white">Digital LogBook<span class="text-brand-500">+</span></p>
            <p class="text-xs text-zinc-400">Universiti Kebangsaan Malaysia</p>
        </div>
    </div>

    <nav class="flex-1 space-y-1 p-3">
        <a href="{{ route('dashboard') }}" @class(['nav-link', 'active' => request()->routeIs('dashboard')])>
            <x-icon name="home" /> Dashboard
        </a>
        <a href="{{ route('logbooks.index') }}" @class(['nav-link', 'active' => request()->routeIs('logbooks.*')])>
            <x-icon name="book" /> {{ $user->isSupervisor() ? 'Student Logbooks' : 'My Logbook' }}
        </a>
        @if ($user->isSupervisor())
            <a href="{{ route('students.index') }}" @class(['nav-link', 'active' => request()->routeIs('students.*')])>
                <x-icon name="users" /> Students
            </a>
        @endif
        <a href="{{ route('profile') }}" @class(['nav-link', 'active' => request()->routeIs('profile')])>
            <x-icon name="user" /> Profile
        </a>
    </nav>

    <div class="border-t border-white/10 p-3">
        <div class="flex items-center gap-3 rounded-lg px-2 py-2">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white">{{ $user->initials() }}</span>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-white">{{ $user->name }}</p>
                <p class="truncate text-xs text-zinc-400">{{ ucfirst($user->role) }} · {{ $user->username }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" title="Log out" class="rounded-md p-1.5 text-zinc-400 hover:bg-white/10 hover:text-white">
                    <x-icon name="logout" />
                </button>
            </form>
        </div>
    </div>
</aside>

<div class="lg:pl-64">
    {{-- Top bar --}}
    <header class="sticky top-0 z-20 flex h-16 items-center gap-4 border-b border-zinc-200 bg-white/80 px-4 backdrop-blur sm:px-6 lg:px-8">
        <button type="button" data-sidebar-toggle class="-ml-1 rounded-md p-1.5 text-zinc-600 hover:bg-zinc-100 lg:hidden" aria-label="Open menu">
            <x-icon name="menu" />
        </button>
        <div class="min-w-0 flex-1">
            <h1 class="truncate text-lg font-semibold text-zinc-900">{{ $title ?? 'Dashboard' }}</h1>
        </div>
        {{ $actions ?? '' }}
    </header>

    <main class="px-4 py-6 sm:px-6 lg:px-8">
        <x-flash />
        {{ $slot }}
    </main>

    <footer class="px-4 pb-6 text-center text-xs text-zinc-400 sm:px-6 lg:px-8">
        &copy; {{ date('Y') }} Digital LogBook+ · Built with Laravel {{ app()->version() }}
    </footer>
</div>
</body>
</html>
