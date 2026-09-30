<x-layouts.app title="Dashboard">
    @if ($user->isStudent())
        <x-slot:actions>
            <a href="{{ route('logbooks.create') }}" class="btn btn-primary"><x-icon name="plus" class="h-4 w-4" /> New entry</a>
        </x-slot:actions>
    @endif

    <div class="mb-8">
        <p class="text-sm text-zinc-500">{{ now()->format('l, j F Y') }}</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight">Hello, {{ $user->name }} 👋</h2>
        <p class="mt-1 text-zinc-600">
            @if ($user->isSupervisor())
                Here's how your students' logbooks are coming along.
            @else
                Supervisor: <span class="font-medium text-zinc-900">{{ $user->supervisor?->name ?? 'Not assigned' }}</span>
            @endif
        </p>
    </div>

    {{-- Stats --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @if ($user->isSupervisor())
            <div class="card p-5">
                <p class="text-sm font-medium text-zinc-500">Students</p>
                <p class="mt-2 text-3xl font-bold">{{ $stats['students'] }}</p>
            </div>
        @else
            @php($pct = $stats['weeks'] ? min(100, round($stats['total'] / $stats['weeks'] * 100)) : 0)
            <div class="card p-5">
                <p class="text-sm font-medium text-zinc-500">Weeks logged</p>
                <p class="mt-2 text-3xl font-bold">{{ $stats['total'] }}<span class="text-lg font-medium text-zinc-400"> / {{ $stats['weeks'] }}</span></p>
                <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-zinc-100">
                    <div class="h-full rounded-full bg-brand-600" style="width: {{ $pct }}%"></div>
                </div>
            </div>
        @endif
        @if ($user->isSupervisor())
            <div class="card p-5">
                <p class="text-sm font-medium text-zinc-500">Total entries</p>
                <p class="mt-2 text-3xl font-bold">{{ $stats['total'] }}</p>
            </div>
        @elseif ($stats['next_week'])
            <a href="{{ route('logbooks.create') }}" class="card p-5 transition hover:border-brand-500">
                <p class="text-sm font-medium text-zinc-500">Next to log</p>
                <p class="mt-2 text-3xl font-bold">Week {{ $stats['next_week'] }}</p>
            </a>
        @else
            <div class="card p-5">
                <p class="text-sm font-medium text-zinc-500">Next to log</p>
                <p class="mt-2 text-xl font-bold text-emerald-600">All weeks done 🎉</p>
            </div>
        @endif
        <div class="card p-5">
            <p class="flex items-center gap-1.5 text-sm font-medium text-zinc-500"><x-icon name="check" class="h-4 w-4 text-emerald-600" /> Reviewed</p>
            <p class="mt-2 text-3xl font-bold text-emerald-600">{{ $stats['reviewed'] }}</p>
        </div>
        <a href="{{ route('logbooks.index', ['status' => 'pending']) }}" class="card p-5 transition hover:border-amber-300">
            <p class="flex items-center gap-1.5 text-sm font-medium text-zinc-500"><x-icon name="clock" class="h-4 w-4 text-amber-500" /> {{ $user->isSupervisor() ? 'Awaiting your review' : 'Awaiting review' }}</p>
            <p class="mt-2 text-3xl font-bold text-amber-600">{{ $stats['pending'] }}</p>
        </a>
    </div>

    {{-- Recent activity --}}
    <div class="card mt-8">
        <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-4">
            <h3 class="font-semibold">Recent entries</h3>
            <a href="{{ route('logbooks.index') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">View all →</a>
        </div>
        <ul class="divide-y divide-zinc-100">
            @forelse ($recent as $logbook)
                <li>
                    <a href="{{ route('logbooks.show', $logbook) }}" class="flex items-center gap-4 px-5 py-4 hover:bg-zinc-50">
                        <span class="flex h-11 w-11 shrink-0 flex-col items-center justify-center rounded-lg bg-zinc-100 leading-none">
                            <span class="text-[10px] font-medium uppercase text-zinc-500">Week</span>
                            <span class="text-base font-bold">{{ $logbook->week_no }}</span>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">
                                @if ($user->isSupervisor()) {{ $logbook->student->name }} · @endif
                                {{ \Illuminate\Support\Str::limit($logbook->progress, 80) }}
                            </p>
                            <p class="text-xs text-zinc-500">{{ $logbook->entry_date->format('j M Y') }} · updated {{ $logbook->updated_at->diffForHumans() }}</p>
                        </div>
                        <x-status-badge :logbook="$logbook" />
                    </a>
                </li>
            @empty
                <li class="px-5 py-12 text-center">
                    <x-icon name="book" class="mx-auto h-10 w-10 text-zinc-300" />
                    <p class="mt-3 text-sm text-zinc-500">
                        @if ($user->isSupervisor())
                            No logbook entries from your students yet.
                            @if (! $stats['students']) <a href="{{ route('students.create') }}" class="font-medium text-brand-600">Register your first student</a>. @endif
                        @else
                            You haven't written any entries yet. <a href="{{ route('logbooks.create') }}" class="font-medium text-brand-600">Write week 1</a>.
                        @endif
                    </p>
                </li>
            @endforelse
        </ul>
    </div>
</x-layouts.app>
