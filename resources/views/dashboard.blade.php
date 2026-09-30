@php
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    $progress = $stats['weeks'] ? min(100, round($stats['total'] / $stats['weeks'] * 100)) : 0;
    $reviewRate = $stats['total'] ? round($stats['reviewed'] / $stats['total'] * 100) : 0;
@endphp

<x-layouts.app title="Dashboard" :subtitle="$user->isSupervisor() ? 'Overview of your supervised students' : 'Your final year project logbook at a glance'">
    @if ($user->isStudent() && $stats['next_week'])
        <x-slot:actions>
            <a href="{{ route('logbooks.create') }}" class="btn btn-primary"><x-icon name="plus" class="h-4 w-4" /> <span class="hidden sm:inline">New entry</span></a>
        </x-slot:actions>
    @endif

    {{-- Welcome banner --}}
    <section class="relative animate-fade-up overflow-hidden rounded-3xl bg-zinc-950 px-6 py-8 text-white shadow-xl shadow-zinc-900/10 sm:px-10 sm:py-10">
        <div class="bg-grid absolute inset-0 [mask-image:radial-gradient(ellipse_at_top_right,black,transparent_70%)]"></div>
        <div class="absolute -top-24 -right-16 h-72 w-72 rounded-full bg-brand-600/40 blur-3xl"></div>
        <div class="absolute -bottom-32 left-1/3 h-64 w-64 rounded-full bg-brand-900/50 blur-3xl"></div>

        <div class="relative flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-xl">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-zinc-200 ring-1 ring-white/15">
                    <x-icon name="sparkles" class="h-3.5 w-3.5 text-brand-500" />
                    {{ $user->isSupervisor() ? 'Supervisor workspace' : 'Final Year Project' }}
                </span>
                <h2 class="mt-4 text-2xl font-bold tracking-tight sm:text-3xl">{{ $greeting }}, {{ $user->name }}</h2>
                <p class="mt-2 text-zinc-300">
                    @if ($user->isSupervisor())
                        @if ($stats['pending'])
                            You have <span class="font-semibold text-white">{{ $stats['pending'] }} {{ \Illuminate\Support\Str::plural('entry', $stats['pending']) }}</span> waiting for your review and signature.
                        @else
                            You're all caught up. Every submitted entry has been reviewed.
                        @endif
                    @elseif ($stats['next_week'])
                        Keep the momentum going. Week {{ $stats['next_week'] }} is next on your logbook.
                    @else
                        Every week is logged. Great work this semester!
                    @endif
                </p>
                <div class="mt-6 flex flex-wrap gap-3">
                    @if ($user->isSupervisor())
                        <a href="{{ route('logbooks.index', ['status' => 'pending']) }}" class="btn bg-white text-zinc-900 hover:bg-zinc-100 focus:ring-white/30">Review entries <x-icon name="chevron-right" class="h-4 w-4" /></a>
                        <a href="{{ route('students.create') }}" class="btn bg-white/10 text-white ring-1 ring-white/20 hover:bg-white/15 focus:ring-white/20"><x-icon name="plus" class="h-4 w-4" /> Add student</a>
                    @else
                        @if ($stats['next_week'])
                            <a href="{{ route('logbooks.create') }}" class="btn bg-white text-zinc-900 hover:bg-zinc-100 focus:ring-white/30">Write week {{ $stats['next_week'] }} <x-icon name="chevron-right" class="h-4 w-4" /></a>
                        @endif
                        <a href="{{ route('logbooks.index') }}" class="btn bg-white/10 text-white ring-1 ring-white/20 hover:bg-white/15 focus:ring-white/20"><x-icon name="book" class="h-4 w-4" /> View logbook</a>
                    @endif
                </div>
            </div>

            {{-- Progress ring --}}
            @php($ring = $user->isSupervisor() ? $reviewRate : $progress)
            <div class="flex items-center gap-5 self-start rounded-2xl bg-white/5 p-5 ring-1 ring-white/10 backdrop-blur lg:self-auto">
                <div class="relative h-24 w-24 shrink-0">
                    <svg viewBox="0 0 36 36" class="h-24 w-24 -rotate-90">
                        <circle cx="18" cy="18" r="15.9155" fill="none" stroke="rgba(255,255,255,0.12)" stroke-width="3" />
                        <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#ef4444" stroke-width="3" stroke-linecap="round"
                                stroke-dasharray="0 100" data-ring="{{ $ring }}" class="transition-[stroke-dasharray] duration-1000 ease-out" />
                    </svg>
                    <span class="absolute inset-0 flex items-center justify-center text-xl font-bold"><span data-count="{{ $ring }}">0</span>%</span>
                </div>
                <div>
                    <p class="text-sm font-semibold">{{ $user->isSupervisor() ? 'Review rate' : 'Semester progress' }}</p>
                    <p class="mt-1 text-xs text-zinc-400">
                        {{ $user->isSupervisor() ? $stats['reviewed'].' of '.$stats['total'].' entries signed' : $stats['total'].' of '.$stats['weeks'].' weeks logged' }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- Stats --}}
    <div class="mt-8 grid gap-5 sm:grid-cols-2 xl:grid-cols-4" data-reveal-group>
        @if ($user->isSupervisor())
            <x-stat icon="users" label="Students" :value="$stats['students']" tone="sky" hint="Under your supervision" :href="route('students.index')" />
            <x-stat icon="document" label="Total entries" :value="$stats['total']" tone="zinc" hint="Submitted by your students" :href="route('logbooks.index')" />
        @else
            <x-stat icon="calendar" label="Weeks logged" :value="$stats['total']" :suffix="' / '.$stats['weeks']" tone="sky">
                <div class="h-1.5 overflow-hidden rounded-full bg-zinc-100">
                    <div class="h-full w-0 rounded-full bg-sky-500 transition-[width] duration-1000 ease-(--ease-out-soft)" data-progress="{{ $progress }}"></div>
                </div>
            </x-stat>
            <x-stat icon="flag" label="Next to log" :value="$stats['next_week'] ? 'Week '.$stats['next_week'] : 'All done'" tone="brand"
                    :hint="$stats['next_week'] ? 'Click to start writing' : 'Every week is logged'" :href="$stats['next_week'] ? route('logbooks.create') : null" />
        @endif
        <x-stat icon="shield" label="Reviewed" :value="$stats['reviewed']" tone="emerald" hint="Commented and signed" :href="route('logbooks.index', ['status' => 'reviewed'])" />
        <x-stat icon="clock" :label="$user->isSupervisor() ? 'Awaiting your review' : 'Awaiting review'" :value="$stats['pending']" tone="amber"
                :hint="$user->isSupervisor() ? 'Needs your signature' : 'With your supervisor'" :href="route('logbooks.index', ['status' => 'pending'])" />
    </div>

    <div class="mt-8 grid gap-8 xl:grid-cols-5">
        {{-- Recent entries --}}
        <section class="card overflow-hidden xl:col-span-3" data-reveal>
            <div class="card-header justify-between">
                <div class="flex items-center gap-3">
                    <span class="card-icon bg-brand-50 text-brand-600"><x-icon name="document" class="h-5 w-5" /></span>
                    <div>
                        <h3 class="font-semibold">Recent entries</h3>
                        <p class="text-xs text-zinc-500">Latest logbook activity</p>
                    </div>
                </div>
                <a href="{{ route('logbooks.index') }}" class="group inline-flex items-center gap-1 text-sm font-semibold text-brand-600 hover:text-brand-700">
                    View all <x-icon name="chevron-right" class="h-4 w-4 transition-transform group-hover:translate-x-0.5" />
                </a>
            </div>
            <ul class="divide-y divide-zinc-100">
                @forelse ($recent as $logbook)
                    <li>
                        <a href="{{ route('logbooks.show', $logbook) }}" class="group flex items-center gap-4 px-6 py-4 transition-colors hover:bg-zinc-50">
                            <span class="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-xl bg-zinc-100 leading-none transition duration-300 group-hover:bg-brand-600 group-hover:text-white">
                                <span class="text-[9px] font-semibold tracking-wider uppercase opacity-60">Week</span>
                                <span class="mt-0.5 text-lg font-bold">{{ $logbook->week_no }}</span>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-zinc-900">
                                    @if ($user->isSupervisor()) {{ $logbook->student->name }} @else {{ \Illuminate\Support\Str::limit($logbook->progress, 60) }} @endif
                                </p>
                                <p class="mt-0.5 truncate text-xs text-zinc-500">
                                    @if ($user->isSupervisor()) {{ \Illuminate\Support\Str::limit($logbook->progress, 60) }} · @endif
                                    {{ $logbook->entry_date->format('j M Y') }} · {{ $logbook->updated_at->diffForHumans() }}
                                </p>
                            </div>
                            <x-status-badge :logbook="$logbook" />
                            <x-icon name="chevron-right" class="hidden h-4 w-4 text-zinc-300 transition group-hover:translate-x-1 group-hover:text-zinc-500 sm:block" />
                        </a>
                    </li>
                @empty
                    <li class="px-6 py-16 text-center">
                        <span class="mx-auto flex h-14 w-14 animate-float items-center justify-center rounded-2xl bg-zinc-100">
                            <x-icon name="book" class="h-7 w-7 text-zinc-400" />
                        </span>
                        <p class="mt-4 font-medium text-zinc-900">No entries yet</p>
                        <p class="mt-1 text-sm text-zinc-500">
                            @if ($user->isSupervisor())
                                Entries appear here once your students start writing.
                            @else
                                <a href="{{ route('logbooks.create') }}" class="font-semibold text-brand-600">Write your first entry</a> to get started.
                            @endif
                        </p>
                    </li>
                @endforelse
            </ul>
        </section>

        {{-- Right column --}}
        <section class="card xl:col-span-2" data-reveal>
            @if ($user->isStudent())
                <div class="card-header">
                    <span class="card-icon bg-sky-50 text-sky-600"><x-icon name="calendar" class="h-5 w-5" /></span>
                    <div>
                        <h3 class="font-semibold">Semester timeline</h3>
                        <p class="text-xs text-zinc-500">Week-by-week status</p>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-7 gap-2.5">
                        @foreach (range(1, $stats['weeks']) as $week)
                            @php($entry = $timeline->get($week))
                            <a href="{{ $entry ? route('logbooks.show', $entry) : ($week === $stats['next_week'] ? route('logbooks.create') : '#') }}"
                               title="Week {{ $week }}: {{ $entry ? ($entry->isReviewed() ? 'Reviewed' : 'Pending review') : 'Not logged' }}"
                               @class([
                                   'flex aspect-square items-center justify-center rounded-xl text-sm font-semibold transition duration-200 hover:-translate-y-0.5 hover:shadow-md',
                                   'bg-emerald-500 text-white' => $entry?->isReviewed(),
                                   'bg-amber-400 text-white' => $entry && ! $entry->isReviewed(),
                                   'border-2 border-dashed border-brand-500 bg-brand-50 text-brand-600' => ! $entry && $week === $stats['next_week'],
                                   'bg-zinc-100 text-zinc-400 pointer-events-none' => ! $entry && $week !== $stats['next_week'],
                               ])>{{ $week }}</a>
                        @endforeach
                    </div>
                    <div class="mt-6 flex flex-wrap gap-x-5 gap-y-2 text-xs text-zinc-500">
                        <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span> Reviewed</span>
                        <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span> Pending</span>
                        <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full ring-2 ring-brand-500"></span> Next</span>
                        <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-zinc-200"></span> Not logged</span>
                    </div>

                    <div class="mt-6 rounded-2xl bg-zinc-50 p-4">
                        <p class="text-xs font-medium text-zinc-500">Your supervisor</p>
                        <div class="mt-2 flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-zinc-900 text-sm font-bold text-white">{{ $user->supervisor?->initials() ?? '?' }}</span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold">{{ $user->supervisor?->name ?? 'Not assigned' }}</p>
                                <p class="truncate text-xs text-zinc-500">{{ $user->supervisor?->email ?? '—' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="card-header justify-between">
                    <div class="flex items-center gap-3">
                        <span class="card-icon bg-sky-50 text-sky-600"><x-icon name="chart" class="h-5 w-5" /></span>
                        <div>
                            <h3 class="font-semibold">Student progress</h3>
                            <p class="text-xs text-zinc-500">Weeks reviewed out of {{ $stats['weeks'] }}</p>
                        </div>
                    </div>
                    <a href="{{ route('students.index') }}" class="text-sm font-semibold text-brand-600 hover:text-brand-700">Manage</a>
                </div>
                <ul class="space-y-1 p-3">
                    @forelse ($students as $student)
                        @php($pct = min(100, round($student->reviewed_count / $stats['weeks'] * 100)))
                        <li>
                            <a href="{{ route('logbooks.index', ['student' => $student->id]) }}" class="flex items-center gap-3 rounded-xl p-3 transition hover:bg-zinc-50">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-zinc-100 text-xs font-bold text-zinc-600">{{ $student->initials() }}</span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="truncate text-sm font-semibold">{{ $student->name }}</p>
                                        <p class="shrink-0 text-xs text-zinc-500">{{ $student->reviewed_count }}/{{ $stats['weeks'] }}</p>
                                    </div>
                                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-zinc-100">
                                        <div class="h-full w-0 rounded-full bg-gradient-to-r from-emerald-400 to-emerald-600 transition-[width] duration-1000 ease-(--ease-out-soft)" data-progress="{{ $pct }}"></div>
                                    </div>
                                </div>
                            </a>
                        </li>
                    @empty
                        <li class="px-3 py-12 text-center">
                            <p class="text-sm text-zinc-500">No students yet.</p>
                            <a href="{{ route('students.create') }}" class="mt-2 inline-block text-sm font-semibold text-brand-600">Register your first student</a>
                        </li>
                    @endforelse
                </ul>
            @endif
        </section>
    </div>
</x-layouts.app>
