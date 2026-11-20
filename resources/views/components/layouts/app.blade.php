@props(['title' => 'Dashboard', 'subtitle' => null])

@php
    // Light/dark choice saved by the top-bar switch; dark until the user picks light.
    $dark = request()->cookie('theme', 'dark') !== 'light';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['app-bg h-full', 'theme-dark' => $dark])>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-bg h-full font-sans text-zinc-900 antialiased">
@php
    $user = auth()->user();
@endphp

{{-- Mobile backdrop --}}
<div data-sidebar-backdrop data-sidebar-toggle class="fixed inset-0 z-30 hidden bg-zinc-950/60 backdrop-blur-sm lg:hidden"></div>

{{-- Sidebar --}}
<aside data-sidebar class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-brand-950 transition-transform duration-300 ease-(--ease-out-soft) lg:translate-x-0">
    {{-- Subtle brand glow at the top of the sidebar --}}
    <div class="pointer-events-none absolute inset-x-0 top-0 h-48 bg-gradient-to-b from-ukm-gold/10 to-transparent"></div>

    <a href="{{ route('dashboard') }}" class="group relative flex items-center gap-3 px-5 pt-6 pb-5">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center keep-light rounded-2xl bg-white p-1.5 shadow-lg shadow-black/20 ring-1 ring-white/10 transition duration-300 group-hover:scale-105 group-hover:rotate-[-3deg]">
            <img src="{{ asset('images/ukm.png') }}" alt="UKM" class="h-full w-full object-contain">
        </span>
        <span class="min-w-0 leading-tight">
            <span class="block text-[14px] font-bold tracking-tight text-white">Digital LogBook<span class="text-ukm-gold">+</span></span>
            <span class="mt-0.5 block text-[10.5px] whitespace-nowrap text-slate-400">Universiti Kebangsaan Malaysia</span>
        </span>
    </a>

    <div class="mx-5 border-t border-white/10"></div>

    <nav class="relative flex-1 overflow-y-auto px-5 pb-5">
        <p class="nav-section">Main menu</p>
        <div class="space-y-1">
            <a href="{{ route('dashboard') }}" @class(['nav-link', 'active' => request()->routeIs('dashboard')])>
                <x-icon name="home" /> Dashboard
            </a>
            <a href="{{ route('logbooks.index') }}" @class(['nav-link', 'active' => request()->routeIs('logbooks.*') && ! request()->routeIs('logbooks.create')])>
                <x-icon name="book" /> {{ $user->isStaff() ? 'Student Logbooks' : 'My Logbook' }}
            </a>
            @if ($user->isCoordinator())
                <a href="{{ route('admin.users.index') }}" @class(['nav-link', 'active' => request()->routeIs('admin.users.*')])>
                    <x-icon name="users" /> Users
                </a>
                <a href="{{ route('admin.semesters.index') }}" @class(['nav-link', 'active' => request()->routeIs('admin.semesters.*')])>
                    <x-icon name="calendar" /> Semesters
                </a>
            @elseif ($user->isSupervisor())
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
                <button type="submit" class="nav-link w-full hover:text-red-300">
                    <x-icon name="logout" /> Log out
                </button>
            </form>
        </div>
    </nav>

    <div class="relative p-4">
        <a href="{{ route('profile') }}" class="flex items-center gap-3 rounded-2xl bg-white/5 p-3 ring-1 ring-white/10 transition duration-200 hover:bg-white/10">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-ukm-gold to-amber-500 text-sm font-bold text-brand-950">{{ $user->initials() }}</span>
            <span class="min-w-0 flex-1">
                <span class="block truncate text-sm font-semibold text-white">{{ $user->name }}</span>
                <span class="block truncate text-xs text-slate-400">{{ ucfirst($user->role) }} · {{ $user->username }}</span>
            </span>
            <x-icon name="chevron-right" class="h-4 w-4 text-zinc-500" />
        </a>
    </div>
</aside>

<div class="lg:pl-64">
    {{-- Top bar --}}
    <header data-topbar class="app-topbar sticky top-0 z-20 border-b backdrop-blur-md transition-shadow duration-300">
        @php
            // Breadcrumb section for the current page.
            $section = match (true) {
                request()->routeIs('logbooks.*') => [$user->isStaff() ? 'Student Logbooks' : 'My Logbook', route('logbooks.index')],
                request()->routeIs('admin.semesters.*') => ['Semesters', route('admin.semesters.index')],
                request()->routeIs('admin.*') => ['Users', route('admin.users.index')],
                request()->routeIs('students.*') => ['Students', route('students.index')],
                request()->routeIs('profile') => ['Account', route('profile')],
                default => null,
            };
        @endphp
        <div class="mx-auto flex h-16 max-w-[1500px] items-center gap-3 px-4 sm:px-6 lg:px-7">
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

            {{-- Search across logbook entries. Ctrl+K focuses it (resources/js/app.js). --}}
            <form method="GET" action="{{ route('logbooks.index') }}" class="relative hidden w-72 shrink-0 md:block" role="search">
                <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input type="search" name="q" value="{{ request()->routeIs('logbooks.index') ? request('q') : '' }}" data-global-search
                       placeholder="Search entries… (Ctrl+K)" aria-label="Search logbook entries"
                       class="w-full rounded-xl border border-zinc-200 bg-zinc-50 py-2.5 pr-3 pl-10 text-sm text-zinc-900 transition placeholder:text-zinc-400 focus:border-brand-500 focus:bg-white focus:outline-none">
            </form>

            <span class="hidden shrink-0 items-center gap-1.5 rounded-full bg-zinc-100 px-3 py-1.5 text-xs font-medium text-zinc-600 xl:inline-flex">
                <x-icon name="calendar" class="h-3.5 w-3.5 text-slate-400" /> {{ now()->format('D, j M Y') }}
            </span>
            {{-- Light / dark switch (resources/js/app.js) --}}
            <button type="button" data-theme-toggle class="icon-btn shrink-0" aria-label="Switch to light or dark mode" title="Switch light / dark mode">
                <x-icon name="sun" class="theme-icon-sun h-5 w-5" />
                <x-icon name="moon" class="theme-icon-moon h-5 w-5" />
            </button>

            {{-- Notifications --}}
            @php
                $unreadCount = $user->unreadNotifications()->count();
                $notifications = $user->notifications()->latest()->take(8)->get();
            @endphp
            <details class="group relative shrink-0" data-dropdown>
                <summary class="icon-btn relative flex cursor-pointer list-none items-center [&::-webkit-details-marker]:hidden" aria-label="Notifications">
                    <x-icon name="bell" />
                    @if ($unreadCount)
                        <span class="absolute -top-0.5 -right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-ukm-red px-1 text-[10px] font-bold text-white ring-2 ring-white">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                    @endif
                </summary>
                <div class="absolute right-0 z-50 mt-2 w-80 max-w-[calc(100vw-2rem)] origin-top-right animate-scale-in overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-[0_20px_40px_-12px_rgba(16,24,40,0.25)]">
                    <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-3">
                        <p class="text-sm font-semibold">Notifications</p>
                        @if ($unreadCount)
                            <form method="POST" action="{{ route('notifications.read-all') }}">
                                @csrf
                                <button type="submit" class="text-xs font-semibold text-brand-600 hover:text-brand-800">Mark all read</button>
                            </form>
                        @endif
                    </div>
                    <ul class="max-h-96 divide-y divide-zinc-100 overflow-y-auto">
                        @forelse ($notifications as $note)
                            @php
                                [$noteIcon, $noteTone] = match ($note->data['kind'] ?? '') {
                                    'approved' => ['shield', 'bg-emerald-50 text-emerald-600'],
                                    'revision' => ['pencil', 'bg-orange-50 text-orange-600'],
                                    default => ['document', 'bg-brand-50 text-brand-600'],
                                };
                            @endphp
                            <li>
                                <a href="{{ route('notifications.open', $note->id) }}" @class(['flex gap-3 px-4 py-3 text-sm transition hover:bg-zinc-50', 'bg-brand-50/40' => ! $note->read_at])>
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $noteTone }}"><x-icon :name="$noteIcon" class="h-4 w-4" /></span>
                                    <span class="min-w-0 flex-1">
                                        <span @class(['block leading-snug', 'font-semibold text-zinc-900' => ! $note->read_at, 'text-zinc-600' => $note->read_at])>{{ $note->data['message'] ?? '' }}</span>
                                        <span class="mt-0.5 block text-xs text-zinc-400">{{ $note->created_at->diffForHumans() }}</span>
                                    </span>
                                    @unless ($note->read_at)
                                        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-brand-600"></span>
                                    @endunless
                                </a>
                            </li>
                        @empty
                            <li class="px-4 py-8 text-center text-sm text-zinc-500">No notifications yet.</li>
                        @endforelse
                    </ul>
                </div>
            </details>

            @isset($actions)
                <div class="flex shrink-0 items-center gap-2 [&_.btn]:px-3.5 [&_.btn]:py-2">{{ $actions }}</div>
            @endisset
        </div>
    </header>

    <main class="mx-auto max-w-[1500px] px-4 py-6 sm:px-6 lg:px-7 lg:py-7">
        <x-flash />
        {{ $slot }}
    </main>

    <footer class="mx-auto max-w-[1500px] px-4 pb-8 sm:px-6 lg:px-7">
        <div class="border-t border-zinc-200 pt-6 text-center text-xs text-zinc-400">
            <p>&copy; {{ date('Y') }} Digital LogBook+ · Universiti Kebangsaan Malaysia</p>
        </div>
    </footer>
</div>
</body>
</html>
