@props(['title' => 'Dashboard', 'subtitle' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-zinc-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-zinc-50 font-sans text-zinc-900 antialiased">
@php
    $user = auth()->user();
@endphp

{{-- Mobile backdrop --}}
<div data-sidebar-backdrop data-sidebar-toggle class="fixed inset-0 z-30 hidden bg-zinc-950/60 backdrop-blur-sm lg:hidden"></div>

{{-- Sidebar --}}
<aside data-sidebar class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col bg-zinc-950 transition-transform duration-300 ease-(--ease-out-soft) lg:translate-x-0">
    {{-- Subtle brand glow at the top of the sidebar --}}
    <div class="pointer-events-none absolute inset-x-0 top-0 h-48 bg-gradient-to-b from-brand-600/15 to-transparent"></div>

    <a href="{{ route('dashboard') }}" class="group relative flex items-center gap-3.5 px-6 pt-8 pb-7">
        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white p-1.5 shadow-lg shadow-black/20 ring-1 ring-white/10 transition duration-300 group-hover:scale-105 group-hover:rotate-[-3deg]">
            <img src="{{ asset('images/ukm.png') }}" alt="UKM" class="h-full w-full object-contain">
        </span>
        <span class="min-w-0 leading-tight">
            <span class="block text-[15px] font-bold tracking-tight text-white">Digital LogBook<span class="text-brand-500">+</span></span>
            <span class="mt-1 block text-xs text-zinc-400">Universiti Kebangsaan Malaysia</span>
        </span>
    </a>

    <div class="mx-6 border-t border-white/10"></div>

    <nav class="relative flex-1 overflow-y-auto px-6 pb-6">
        <p class="nav-section">Main menu</p>
        <div class="space-y-1">
            <a href="{{ route('dashboard') }}" @class(['nav-link', 'active' => request()->routeIs('dashboard')])>
                <x-icon name="home" /> Dashboard
            </a>
            <a href="{{ route('logbooks.index') }}" @class(['nav-link', 'active' => request()->routeIs('logbooks.*') && ! request()->routeIs('logbooks.create')])>
                <x-icon name="book" /> {{ $user->isSupervisor() ? 'Student Logbooks' : 'My Logbook' }}
            </a>
            @if ($user->isSupervisor())
                <a href="{{ route('students.index') }}" @class(['nav-link', 'active' => request()->routeIs('students.*')])>
                    <x-icon name="users" /> Students
                </a>
            @else
                <a href="{{ route('logbooks.create') }}" @class(['nav-link', 'active' => request()->routeIs('logbooks.create')])>
                    <x-icon name="plus" /> New Entry
                </a>
            @endif
        </div>

        <p class="nav-section">Account</p>
        <div class="space-y-1">
            <a href="{{ route('profile') }}" @class(['nav-link', 'active' => request()->routeIs('profile')])>
                <x-icon name="user" /> Profile
            </a>
            <form method="POST" action="{{ route('logout') }}" data-confirm="You will need to sign in again to access your logbook." data-confirm-title="Log out?" data-confirm-button="Yes, log out" data-confirm-icon="question">
                @csrf
                <button type="submit" class="nav-link w-full hover:text-brand-400">
                    <x-icon name="logout" /> Log out
                </button>
            </form>
        </div>
    </nav>

    <div class="relative p-4">
        <a href="{{ route('profile') }}" class="flex items-center gap-3 rounded-2xl bg-white/5 p-3 ring-1 ring-white/10 transition duration-200 hover:bg-white/10">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-sm font-bold text-white">{{ $user->initials() }}</span>
            <span class="min-w-0 flex-1">
                <span class="block truncate text-sm font-semibold text-white">{{ $user->name }}</span>
                <span class="block truncate text-xs text-zinc-400">{{ ucfirst($user->role) }} · {{ $user->username }}</span>
            </span>
            <x-icon name="chevron-right" class="h-4 w-4 text-zinc-500" />
        </a>
    </div>
</aside>

<div class="lg:pl-72">
    {{-- Top bar --}}
    <header data-topbar class="sticky top-0 z-20 border-b border-zinc-200/70 bg-white/85 backdrop-blur-md transition-shadow duration-300">
        @php
            // Breadcrumb section for the current page.
            $section = match (true) {
                request()->routeIs('logbooks.*') => [$user->isSupervisor() ? 'Student Logbooks' : 'My Logbook', route('logbooks.index')],
                request()->routeIs('students.*') => ['Students', route('students.index')],
                request()->routeIs('profile') => ['Account', route('profile')],
                default => null,
            };
        @endphp
        <div class="mx-auto flex h-16 max-w-7xl items-center gap-3 px-4 sm:px-6 lg:px-10">
            <button type="button" data-sidebar-toggle class="icon-btn -ml-1 lg:hidden" aria-label="Open menu">
                <x-icon name="menu" />
            </button>

            <div class="min-w-0 flex-1 animate-fade-in">
                <nav aria-label="Breadcrumb" class="flex items-center gap-1 text-[11px] font-medium text-zinc-400">
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1 transition hover:text-brand-600">
                        <x-icon name="home" class="h-3.5 w-3.5" /> Home
                    </a>
                    @if ($section)
                        <x-icon name="chevron-right" class="h-3 w-3" />
                        <a href="{{ $section[1] }}" class="truncate transition hover:text-brand-600">{{ $section[0] }}</a>
                    @endif
                </nav>
                <div class="flex min-w-0 items-baseline gap-2.5">
                    <h1 class="shrink-0 truncate text-lg leading-tight font-bold tracking-tight text-zinc-900">{{ $title }}</h1>
                    @if ($subtitle)
                        <span class="hidden h-1 w-1 shrink-0 translate-y-[-3px] rounded-full bg-zinc-300 lg:block"></span>
                        <p class="hidden truncate text-sm text-zinc-500 lg:block">{{ $subtitle }}</p>
                    @endif
                </div>
            </div>

            <span class="hidden shrink-0 items-center gap-1.5 rounded-full bg-zinc-100 px-3 py-1.5 text-xs font-medium text-zinc-600 xl:inline-flex">
                <x-icon name="calendar" class="h-3.5 w-3.5 text-zinc-400" /> {{ now()->format('D, j M Y') }}
            </span>
            @isset($actions)
                <div class="flex shrink-0 items-center gap-2 [&_.btn]:px-3.5 [&_.btn]:py-2">{{ $actions }}</div>
            @endisset
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-10 lg:py-8">
        <x-flash />
        {{ $slot }}
    </main>

    <footer class="mx-auto max-w-7xl px-4 pb-8 sm:px-6 lg:px-10">
        <div class="border-t border-zinc-200 pt-6 text-center text-xs text-zinc-400">
            <p>&copy; {{ date('Y') }} Digital LogBook+ · Universiti Kebangsaan Malaysia</p>
        </div>
    </footer>
</div>
</body>
</html>
