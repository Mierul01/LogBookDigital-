@php
    use Illuminate\Support\Str;

    // Supervisors and the coordinator share the staff dashboard; the coordinator's covers every student.
    $isSupervisor = $user->isStaff();
    $studentsUrl = $user->isCoordinator() ? route('admin.users.index', ['role' => 'student']) : route('students.index');
    $hour = now()->hour;
    $greeting = __($hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening'));
    $firstName = Str::of($user->name)->replaceMatches('/^((Prof|Madya|Dr|Ir|Ts|Datuk|Dato|Encik|Puan|Cik)\.?\s+)+/i', '')->before(' ');

    // Running totals give the tiles a rising trend line.
    $cumulative = fn (array $values) => array_map(fn ($i) => array_sum(array_slice($values, 0, $i + 1)), array_keys($values));

    $hours = $stats['review_hours'];
    $reviewTime = match (true) {
        $hours === null => '—',
        $hours < 1 => max(1, round($hours * 60)).' min',
        $hours < 48 => round($hours, 1).' h',
        default => round($hours / 24, 1).' days',
    };

    $tiles = $isSupervisor
        ? [
            ['Students', $stats['students'], 'users', 'from-sky-400 to-sky-600', '#38bdf8', $cumulative($series['submitted']), ($user->isCoordinator() ? 'In the whole faculty' : 'Under your supervision'), $studentsUrl],
            ['Total entries', $stats['total'], 'document', 'from-brand-400 to-brand-600', '#5583c9', $cumulative($series['submitted']), __('+:count in the last 7 days', ['count' => $stats['this_week']]), route('logbooks.index')],
            ['Approved', $stats['reviewed'], 'shield', 'from-emerald-400 to-emerald-600', '#34d399', $cumulative($series['approved']), __(':rate% of all entries', ['rate' => $stats['approval_rate']]), route('logbooks.index', ['status' => 'approved'])],
            ['Awaiting review', $stats['pending'], 'clock', 'from-amber-300 to-amber-500', '#f5b41a', $series['pending'], $stats['pending'] ? ($user->isCoordinator() ? 'With the supervisors' : 'Needs your signature') : 'All caught up', route('logbooks.index', ['status' => 'pending'])],
        ]
        : [
            ['Weeks logged', $stats['total'].'/'.$stats['weeks'], 'calendar', 'from-sky-400 to-sky-600', '#38bdf8', $cumulative($series['submitted']), $stats['next_week'] ? __('Week :week is next', ['week' => $stats['next_week']]) : 'Every week is logged', $stats['next_week'] ? route('logbooks.create') : route('logbooks.index')],
            ['Approved', $stats['reviewed'], 'shield', 'from-emerald-400 to-emerald-600', '#34d399', $cumulative($series['approved']), 'Signed by your supervisor', route('logbooks.index', ['status' => 'approved'])],
            ['Awaiting review', $stats['pending'], 'clock', 'from-amber-300 to-amber-500', '#f5b41a', $series['pending'], 'With your supervisor', route('logbooks.index', ['status' => 'pending'])],
            ['Needs fixing', $stats['revision'], 'pencil', 'from-orange-400 to-orange-600', '#fb923c', $series['revision'], $stats['revision'] ? 'Read the comment and resend' : 'Nothing to fix', route('logbooks.index', ['status' => 'revision'])],
        ];

    $notLogged = $isSupervisor ? max(0, $stats['expected'] - $stats['total']) : max(0, $stats['weeks'] - $stats['total']);
    $donut = [
        ['Approved', $stats['reviewed'], '#34d399'],
        ['Pending', $stats['pending'], '#f5b41a'],
        ['Needs fixing', $stats['revision'], '#fb923c'],
        ['Not logged', $notLogged, '#64748b'],
    ];
    $donutTotal = max(1, array_sum(array_column($donut, 1)));
@endphp

<x-layouts.app title="{{ __('Dashboard') }}" :subtitle="$isSupervisor ? 'Overview of your supervised students' : 'Your final year project logbook at a glance'">
    @if ($user->isStudent() && $stats['next_week'])
        <x-slot:actions>
            <a href="{{ route('logbooks.create') }}" class="btn btn-primary"><x-icon name="plus" class="h-4 w-4" /> <span class="hidden sm:inline">{{ __('New entry') }}</span></a>
        </x-slot:actions>
    @endif

    <div class="grid gap-3.5 xl:grid-cols-[minmax(0,1fr)_330px]">
        {{-- Left: banner, tiles, chart --}}
        <div class="min-w-0 space-y-3.5">
            {{-- Welcome banner --}}
            <section class="relative animate-fade-up overflow-hidden rounded-2xl bg-gradient-to-r from-brand-800 via-brand-600 to-brand-500 px-5 py-5 ring-1 ring-white/10 sm:px-7">
                <div class="bg-grid absolute inset-0 opacity-60 [mask-image:linear-gradient(to_left,black,transparent_70%)]"></div>
                <div class="absolute -top-20 right-24 h-56 w-56 rounded-full bg-ukm-gold/25 blur-3xl"></div>

                <div class="relative flex items-center justify-between gap-6">
                    <div class="min-w-0">
                        <h2 class="text-xl font-bold tracking-tight text-white sm:text-2xl">{{ $greeting }}, <span class="text-ukm-gold">{{ $firstName }}</span> 👋</h2>
                        <p class="mt-1 text-sm text-slate-200">
                            {{-- Whole sentences (with :count) so they translate cleanly. --}}
                            @if ($isSupervisor)
                                @if ($stats['pending'])
                                    {{ trans_choice($user->isCoordinator()
                                        ? '{1} There is :count entry waiting for supervisors to review.|[2,*] There are :count entries waiting for supervisors to review.'
                                        : '{1} You have :count entry waiting for your review and signature.|[2,*] You have :count entries waiting for your review and signature.', $stats['pending']) }}
                                @else
                                    {{ __('You\'re all caught up. Every submitted entry has been reviewed.') }}
                                @endif
                            @elseif ($stats['revision'])
                                {{ trans_choice('{1} Your supervisor sent back :count entry. Fix it and send it again.|[2,*] Your supervisor sent back :count entries. Fix them and send them again.', $stats['revision']) }}
                            @elseif ($stats['next_week'])
                                {{ __('Keep the momentum going. Week :week is next on your logbook.', ['week' => $stats['next_week']]) }}
                            @else
                                {{ __('Every week is logged. Great work this semester!') }}
                            @endif
                        </p>
                        <div class="mt-3.5 flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-white/10 px-2.5 py-1 text-xs font-medium text-white ring-1 ring-white/15">
                                <x-icon name="calendar" class="h-3.5 w-3.5" /> {{ $semester ? $semester->name.' · '.($semester->currentWeek() ? __('Week').' '.$semester->currentWeek() : __('not started')) : now()->translatedFormat('j M Y') }}
                            </span>
                            @if ($user->isStudent())
                                @if ($project)
                                    <span class="inline-flex max-w-xs items-center gap-1.5 truncate rounded-lg bg-white/10 px-2.5 py-1 text-xs font-medium text-white ring-1 ring-white/15" title="{{ $project->title }}">{{ $project->stageLabel() }} · {{ $project->title }}</span>
                                @elseif ($semester)
                                    <a href="{{ route('profile') }}" class="keep-light inline-flex items-center gap-1 rounded-lg bg-white px-2.5 py-1 text-xs font-semibold text-brand-800 transition hover:bg-brand-50">{{ __('Add your project title') }} <x-icon name="chevron-right" class="h-3.5 w-3.5" /></a>
                                @endif
                            @endif
                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-ukm-gold/20 px-2.5 py-1 text-xs font-semibold text-ukm-gold ring-1 ring-ukm-gold/30">
                                <x-icon name="sparkles" class="h-3.5 w-3.5" /> {{ __($isSupervisor ? ($user->isCoordinator() ? 'Coordinator workspace' : 'Supervisor workspace') : 'Final Year Project') }}
                            </span>
                            @if ($isSupervisor && $stats['pending'])
                                <a href="{{ route('logbooks.index', ['status' => 'pending']) }}" class="keep-light inline-flex items-center gap-1 rounded-lg bg-white px-2.5 py-1 text-xs font-semibold text-brand-800 transition hover:bg-brand-50">{{ __('Review now') }} <x-icon name="chevron-right" class="h-3.5 w-3.5" /></a>
                            @elseif (! $isSupervisor && $stats['next_week'])
                                <a href="{{ route('logbooks.create') }}" class="keep-light inline-flex items-center gap-1 rounded-lg bg-white px-2.5 py-1 text-xs font-semibold text-brand-800 transition hover:bg-brand-50">Write week {{ $stats['next_week'] }} <x-icon name="chevron-right" class="h-3.5 w-3.5" /></a>
                            @endif
                        </div>
                    </div>

                    <div class="hidden shrink-0 items-center gap-6 md:flex">
                        <p class="max-w-44 text-sm leading-snug text-slate-200 italic">{{ __('“Small steps every week lead to a finished project.”') }}</p>
                        <div class="relative">
                            <span class="flex h-20 w-20 animate-float items-center justify-center rounded-3xl bg-gradient-to-br from-white/25 to-white/5 ring-1 ring-white/25 backdrop-blur">
                                <x-icon name="book" class="h-10 w-10 text-white" />
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            @if (! $isSupervisor && $overdueWeeks)
                <a href="{{ route('logbooks.create') }}" class="flex items-center gap-3 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 transition hover:bg-red-100">
                    <x-icon name="warning" class="h-5 w-5 shrink-0 text-red-500" />
                    <span class="flex-1">{{ trans_choice('{1} Week :weeks is past the deadline. Write it as soon as you can; late entries are marked "Late".|[2,*] Weeks :weeks are past the deadline. Write them as soon as you can; late entries are marked "Late".', count($overdueWeeks), ['weeks' => implode(', ', $overdueWeeks)]) }}</span>
                    <x-icon name="chevron-right" class="h-4 w-4 text-red-400" />
                </a>
            @endif

            {{-- Stat tiles --}}
            <div class="grid gap-3.5 sm:grid-cols-2 lg:grid-cols-4" data-reveal-group>
                @foreach ($tiles as [$label, $value, $icon, $gradient, $color, $spark, $hint, $href])
                    <a href="{{ $href }}" class="dash-card group block overflow-hidden px-4 pt-4 transition hover:-translate-y-0.5 hover:border-zinc-300" data-reveal>
                        <div class="flex items-start gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br {{ $gradient }} text-white shadow-lg shadow-black/20">
                                <x-icon :name="$icon" class="h-5 w-5" />
                            </span>
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-zinc-500">{{ __($label) }}</p>
                                <p class="mt-0.5 text-2xl leading-tight font-bold text-zinc-900">{{ $value }}</p>
                            </div>
                        </div>
                        <p class="mt-2 truncate text-xs text-zinc-500">{{ __($hint) }}</p>
                        <x-sparkline :values="$spark" :color="$color" class="-mx-4 mt-1 h-12 w-[calc(100%+2rem)]" />
                    </a>
                @endforeach
            </div>

            {{-- Activity chart --}}
            <section class="dash-card" data-reveal>
                <div class="dash-head flex-wrap">
                    <div class="flex items-center gap-3">
                        <span class="dash-icon bg-brand-50 text-brand-600"><x-icon name="chart" class="h-4 w-4" /></span>
                        <div>
                            <h3 class="text-[15px] font-semibold text-zinc-900">{{ __('Logbook activity') }}</h3>
                            <p class="text-xs text-zinc-500">{{ __('Entries submitted and approved for each week of the semester') }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-zinc-600">
                        <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-brand-400"></span> {{ __('Submitted') }}</span>
                        <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-ukm-gold"></span> {{ __('Approved') }}</span>
                    </div>
                </div>
                <div class="px-4 pb-3">
                    <x-area-chart :labels="array_map(fn ($w) => 'W'.$w, $weeks)" :series="[
                        ['label' => 'Submitted', 'values' => $series['submitted'], 'color' => '#5583c9', 'area' => true],
                        ['label' => 'Approved', 'values' => $series['approved'], 'color' => '#f5b41a'],
                    ]" />
                </div>
                <dl class="mx-4 mb-4 grid grid-cols-2 divide-zinc-200 rounded-xl border border-zinc-200 bg-zinc-50/60 sm:grid-cols-4 sm:divide-x">
                    @foreach ([
                        ['Total entries', $stats['total'], $isSupervisor ? __('of :count expected', ['count' => $stats['expected']]) : __('of :count weeks', ['count' => $stats['weeks']])],
                        ['Approval rate', $stats['approval_rate'].'%', __(':count approved', ['count' => $stats['reviewed']])],
                        ['Avg. review time', $reviewTime, 'submit → signed'],
                        $isSupervisor
                            ? ['Missing weeks', $stats['missing'], 'past the deadline']
                            : ['Overdue weeks', $stats['missing'], $stats['missing'] ? 'write them now' : 'none overdue'],
                    ] as [$label, $value, $note])
                        <div class="px-4 py-3">
                            <dt class="text-xs text-zinc-500">{{ __($label) }}</dt>
                            <dd class="mt-0.5 text-lg font-bold text-zinc-900">{{ $value }}</dd>
                            <dd class="text-xs text-zinc-500">{{ __($note) }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        </div>

        {{-- Right column --}}
        <div class="space-y-3.5">
            @if ($isSupervisor)
                {{-- Top students --}}
                <section class="dash-card" data-reveal>
                    <div class="dash-head">
                        <h3 class="dash-title"><span class="dash-icon bg-ukm-gold/15 text-ukm-gold"><x-icon name="academic-cap" class="h-4 w-4" /></span> {{ __('Top students') }}</h3>
                        <a href="{{ $studentsUrl }}" class="dash-link">{{ __('View all') }} <x-icon name="chevron-right" class="h-3.5 w-3.5" /></a>
                    </div>
                    <ul class="divide-y divide-zinc-100 px-4 pb-2">
                        @forelse ($topStudents as $i => $student)
                            <li>
                                <a href="{{ route('logbooks.index', ['student' => $student->id]) }}" class="flex items-center gap-3 py-2.5 transition hover:opacity-90">
                                    <span @class(['flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                                        'bg-ukm-gold text-ink-950' => $i === 0, 'bg-slate-300 text-ink-950' => $i === 1, 'bg-orange-400 text-ink-950' => $i === 2, 'bg-zinc-100 text-zinc-600' => $i > 2])>{{ $i + 1 }}</span>
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white ring-2 ring-white">{{ $student->initials() }}</span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-semibold text-zinc-900">{{ $student->name }}</span>
                                        <span class="block truncate text-xs text-zinc-500">{{ $student->username }}</span>
                                    </span>
                                    <span class="text-right">
                                        <span class="block text-sm font-bold text-zinc-900">{{ $student->reviewed_count }}/{{ $stats['weeks'] }}</span>
                                        <span class="block text-xs text-zinc-500">{{ $student->logbooks_count }} {{ __(Str::plural('entry', $student->logbooks_count)) }}</span>
                                    </span>
                                </a>
                            </li>
                        @empty
                            <li class="py-8 text-center text-sm text-zinc-500">
                                {{ __('No students yet.') }} <a href="{{ ($user->isCoordinator() ? route('admin.users.create', ['role' => 'student']) : route('students.create')) }}" class="font-semibold text-brand-600">{{ __('Register one') }}</a>
                            </li>
                        @endforelse
                    </ul>
                </section>
            @else
                {{-- Semester timeline --}}
                <section class="dash-card" data-reveal>
                    <div class="dash-head">
                        <h3 class="dash-title"><span class="dash-icon bg-sky-50 text-sky-600"><x-icon name="calendar" class="h-4 w-4" /></span> {{ __('Semester timeline') }}</h3>
                        <a href="{{ route('logbooks.report') }}" class="dash-link">{{ __('Print') }} <x-icon name="chevron-right" class="h-3.5 w-3.5" /></a>
                    </div>
                    <div class="px-4 pb-4">
                        <div class="grid grid-cols-7 gap-1.5">
                            @foreach ($weeks as $week)
                                @php $entry = $timeline->get($week); @endphp
                                <a href="{{ $entry ? route('logbooks.show', $entry) : ($week === $stats['next_week'] ? route('logbooks.create') : '#') }}"
                                   title="Week {{ $week }}: {{ $entry ? ($entry->isReviewed() ? 'Approved' : ($entry->needsRevision() ? 'Needs fixing' : 'Pending review')) : (in_array($week, $overdueWeeks) ? 'Overdue' : 'Not logged') }}"
                                   @class([
                                       'flex aspect-square items-center justify-center rounded-lg text-xs font-semibold transition hover:-translate-y-0.5',
                                       'bg-emerald-500 text-white' => $entry?->isReviewed(),
                                       'bg-orange-500 text-white' => $entry?->needsRevision(),
                                       'bg-amber-400 text-ink-950' => $entry?->isPending(),
                                       'border-2 border-dashed border-ukm-gold text-ukm-gold' => ! $entry && $week === $stats['next_week'],
                                       'bg-red-500/15 text-red-600 ring-1 ring-red-500/40' => ! $entry && in_array($week, $overdueWeeks) && $week !== $stats['next_week'],
                                       'pointer-events-none bg-zinc-100 text-zinc-400' => ! $entry && ! in_array($week, $overdueWeeks) && $week !== $stats['next_week'],
                                   ])>{{ $week }}</a>
                            @endforeach
                        </div>
                        <div class="mt-3 rounded-xl bg-zinc-50 p-3">
                            <p class="text-xs text-zinc-500">{{ __('Your supervisor') }}</p>
                            <div class="mt-1.5 flex items-center gap-2.5">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white">{{ $user->supervisor?->initials() ?? '?' }}</span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-zinc-900">{{ $user->supervisor?->name ?? 'Not assigned' }}</p>
                                    <p class="truncate text-xs text-zinc-500">{{ $user->supervisor?->email ?? '—' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            @endif

            @if ($isSupervisor && $behind->isNotEmpty())
                {{-- Students with weeks missing past the deadline --}}
                <section class="dash-card" data-reveal>
                    <div class="dash-head">
                        <h3 class="dash-title"><span class="dash-icon bg-red-50 text-red-600"><x-icon name="warning" class="h-4 w-4" /></span> {{ __('Falling behind') }}</h3>
                    </div>
                    <ul class="divide-y divide-zinc-100 px-4 pb-2">
                        @foreach ($behind as $student)
                            <li>
                                <a href="{{ route('logbooks.index', ['student' => $student->id]) }}" class="flex items-center gap-3 py-2.5 transition hover:opacity-90">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white ring-2 ring-white">{{ $student->initials() }}</span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-semibold text-zinc-900">{{ $student->name }}</span>
                                        <span class="block truncate text-xs text-zinc-500">{{ $student->username }}</span>
                                    </span>
                                    <span class="shrink-0 rounded-full bg-red-500/10 px-2 py-0.5 text-xs font-semibold text-red-600">{{ __(':count missing', ['count' => $stats['past_due'] - $student->due_logged]) }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- Recent activity (from notifications) --}}
            <section class="dash-card" data-reveal>
                <div class="dash-head">
                    <h3 class="dash-title"><span class="dash-icon bg-brand-50 text-brand-600"><x-icon name="bell" class="h-4 w-4" /></span> {{ __('Recent activity') }}</h3>
                </div>
                <ul class="divide-y divide-zinc-100 px-4 pb-2">
                    @forelse ($activities as $note)
                        @php
                            [$noteIcon, $noteTone] = match ($note->data['kind'] ?? '') {
                                'approved' => ['shield', 'bg-emerald-500'],
                                'revision' => ['pencil', 'bg-orange-500'],
                                'resubmitted' => ['arrow-left', 'bg-sky-500'],
                                'reminder' => ['clock', 'bg-amber-500'],
                                'digest' => ['list', 'bg-sky-500'],
                                default => ['document', 'bg-brand-500'],
                            };
                        @endphp
                        <li>
                            <a href="{{ route('notifications.open', $note->id) }}" class="flex items-center gap-3 py-2.5 transition hover:opacity-90">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $noteTone }} text-white"><x-icon :name="$noteIcon" class="h-4 w-4" /></span>
                                <span class="min-w-0 flex-1 text-sm leading-snug {{ $note->read_at ? 'text-zinc-600' : 'font-semibold text-zinc-900' }}">{{ $note->data['message'] ?? '' }}</span>
                                <span class="shrink-0 text-xs text-zinc-500">{{ $note->created_at->diffForHumans(short: true) }}</span>
                            </a>
                        </li>
                    @empty
                        <li class="py-8 text-center text-sm text-zinc-500">{{ __('No activity yet. Submissions and reviews will show up here.') }}</li>
                    @endforelse
                </ul>
            </section>
        </div>
    </div>

    {{-- Bottom row --}}
    <div class="mt-3.5 grid gap-3.5 lg:grid-cols-2 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_330px]">
        {{-- Recent entries --}}
        <section class="dash-card overflow-hidden lg:col-span-2 xl:col-span-1" data-reveal>
            <div class="dash-head">
                <h3 class="dash-title"><span class="dash-icon bg-brand-50 text-brand-600"><x-icon name="document" class="h-4 w-4" /></span> {{ __('Recent entries') }}</h3>
                <a href="{{ route('logbooks.index') }}" class="dash-link">{{ __('View all') }} <x-icon name="chevron-right" class="h-3.5 w-3.5" /></a>
            </div>
            <div class="overflow-x-auto px-4 pb-4">
                <table class="w-full table-fixed text-sm">
                    <thead class="text-left text-[11px] font-semibold tracking-wider text-zinc-500 uppercase">
                        <tr class="border-b border-zinc-200">
                            <th class="w-14 py-2 pr-3">{{ __('Week') }}</th>
                            <th class="py-2 pr-3">{{ __($isSupervisor ? 'Student' : 'Progress') }}</th>
                            <th class="hidden w-20 py-2 pr-3 sm:table-cell">{{ __('Date') }}</th>
                            <th class="w-28 py-2 text-right">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($recent as $logbook)
                            <tr class="cursor-pointer transition hover:bg-zinc-50" onclick="location.href='{{ route('logbooks.show', $logbook) }}'">
                                <td class="py-2.5 pr-3">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-zinc-100 text-sm font-bold text-zinc-900 ring-1 ring-zinc-200">{{ $logbook->week_no }}</span>
                                </td>
                                <td class="py-2.5 pr-3">
                                    <a href="{{ route('logbooks.show', $logbook) }}" class="block truncate font-medium text-zinc-900 hover:text-brand-600">{{ $isSupervisor ? $logbook->student->name : Str::limit($logbook->progress, 70) }}</a>
                                    <p class="truncate text-xs text-zinc-500">{{ $isSupervisor ? Str::limit($logbook->progress, 60) : __('Updated :time', ['time' => $logbook->updated_at->diffForHumans()]) }}</p>
                                </td>
                                <td class="hidden py-2.5 pr-3 whitespace-nowrap text-zinc-600 sm:table-cell">{{ $logbook->entry_date->format('j M') }}</td>
                                <td class="py-2.5 text-right"><x-status-badge :logbook="$logbook" /></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-10 text-center text-sm text-zinc-500">
                                    @if ($isSupervisor)
                                        Entries appear here once your students start writing.
                                    @else
                                        <a href="{{ route('logbooks.create') }}" class="font-semibold text-brand-600">{{ __('Write your first entry') }}</a> to get started.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Status breakdown --}}
        <section class="dash-card" data-reveal>
            <div class="dash-head">
                <h3 class="dash-title"><span class="dash-icon bg-emerald-50 text-emerald-600"><x-icon name="chart" class="h-4 w-4" /></span> {{ __('Status breakdown') }}</h3>
            </div>
            <div class="flex flex-col items-center gap-3.5 px-4 pb-4">
                <div class="relative h-24 w-24 shrink-0">
                    <svg viewBox="0 0 36 36" class="h-24 w-24 -rotate-90">
                        <circle cx="18" cy="18" r="15.9155" fill="none" stroke="currentColor" stroke-width="4.5" class="text-zinc-100" />
                        @php $offset = 0; @endphp
                        @foreach ($donut as [$label, $count, $color])
                            @if ($count)
                                @php $pct = $count / $donutTotal * 100; @endphp
                                <circle cx="18" cy="18" r="15.9155" fill="none" stroke="{{ $color }}" stroke-width="4.5"
                                        stroke-dasharray="{{ max(0, $pct - 0.6) }} {{ 100 - max(0, $pct - 0.6) }}" stroke-dashoffset="{{ -$offset }}"><title>{{ __($label) }}: {{ $count }}</title></circle>
                                @php $offset += $pct; @endphp
                            @endif
                        @endforeach
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-xl font-bold text-zinc-900">{{ $stats['total'] }}</span>
                        <span class="text-[10px] text-zinc-500">{{ __('entries') }}</span>
                    </div>
                </div>
                <ul class="w-full space-y-1.5 text-sm">
                    @foreach ($donut as [$label, $count, $color])
                        <li class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background: {{ $color }}"></span>
                            <span class="min-w-0 flex-1 truncate text-zinc-600">{{ __($label) }}</span>
                            <span class="font-semibold text-zinc-900">{{ round($count / $donutTotal * 100) }}%</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        {{-- Review queue (supervisor) or latest comments (student) --}}
        <section class="dash-card" data-reveal>
            <div class="dash-head">
                <h3 class="dash-title">
                    <span class="dash-icon bg-amber-50 text-amber-600"><x-icon :name="$isSupervisor ? 'clock' : 'envelope'" class="h-4 w-4" /></span>
                    {{ __($isSupervisor ? 'Waiting for review' : 'Comments') }}
                </h3>
                <a href="{{ route('logbooks.index', $isSupervisor ? ['status' => 'pending'] : []) }}" class="dash-link">{{ __('View all') }} <x-icon name="chevron-right" class="h-3.5 w-3.5" /></a>
            </div>
            <ul class="divide-y divide-zinc-100 px-4 pb-2">
                @forelse ($queue as $logbook)
                    <li>
                        <a href="{{ route('logbooks.show', $logbook) }}" class="flex items-center gap-3 py-2.5 transition hover:opacity-90">
                            @if ($isSupervisor)
                                <span class="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white">
                                    {{ $logbook->student->initials() }}
                                    <span class="absolute -right-0.5 -bottom-0.5 h-3 w-3 rounded-full bg-amber-400 ring-2 ring-white"></span>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold text-zinc-900">{{ $logbook->student->name }}</span>
                                    <span class="block truncate text-xs text-zinc-500">Week {{ $logbook->week_no }} · {{ Str::limit($logbook->progress, 40) }}</span>
                                </span>
                                <span class="shrink-0 text-xs text-zinc-500">{{ ($logbook->submitted_at ?? $logbook->created_at)->diffForHumans(short: true) }}</span>
                            @else
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-sm font-bold text-zinc-900 ring-1 ring-zinc-200">{{ $logbook->week_no }}</span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm text-zinc-700">{{ $logbook->supervisor_comment }}</span>
                                    <span class="mt-0.5 block"><x-status-badge :logbook="$logbook" /></span>
                                </span>
                            @endif
                        </a>
                    </li>
                @empty
                    <li class="py-8 text-center text-sm text-zinc-500">{{ __($isSupervisor ? 'Nothing waiting. Nice work!' : 'No comments yet.') }}</li>
                @endforelse
            </ul>
        </section>
    </div>
</x-layouts.app>
