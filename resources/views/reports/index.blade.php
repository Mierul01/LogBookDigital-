@php
    $isCoordinator = auth()->user()->isCoordinator();
    $query = array_filter($filters);
@endphp

<x-layouts.app title="{{ __('Reports') }}" :subtitle="($report->semester?->name ?? 'All semesters').' · '.($isCoordinator ? 'whole faculty' : 'your students')">
    <x-slot:actions>
        <button type="button" onclick="window.print()" class="btn btn-secondary hidden sm:inline-flex"><x-icon name="printer" class="h-4 w-4" /> {{ __('Print / PDF') }}</button>
        <a href="{{ route('reports.students.export', $query) }}" class="btn btn-primary"><x-icon name="document" class="h-4 w-4" /> <span class="hidden sm:inline">{{ __('Export to Excel') }}</span></a>
    </x-slot:actions>

    {{-- Filters --}}
    <form method="GET" class="mb-4 flex animate-fade-up flex-wrap items-center gap-3 print:hidden">
        <select name="semester" onchange="this.form.submit()" data-select class="input w-auto min-w-52">
            @foreach ($semesters as $s)
                <option value="{{ $s->id }}" @selected($filters['semester'] === $s->id)>{{ $s->name }}{{ __($s->is_current ? ' (current)' : '') }}</option>
            @endforeach
        </select>
        @if ($isCoordinator)
            <select name="supervisor" onchange="this.form.submit()" data-select class="input w-auto min-w-56">
                <option value="">{{ __('All supervisors') }}</option>
                @foreach ($supervisorOptions as $s)
                    <option value="{{ $s->id }}" @selected($filters['supervisor'] === $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
        @endif
        <label class="input-icon-wrap relative">
            <x-icon name="search" class="input-icon pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-zinc-400" />
            <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('Student name or matric…') }}" class="input w-60 py-2.5 pl-9">
        </label>
        <p class="ml-auto text-sm text-zinc-500">{{ $report->pastDue() }} of {{ App\Models\Logbook::MAX_WEEKS }} weeks past their deadline</p>
    </form>

    {{-- Supervisor summary (coordinator) --}}
    @if ($supervisors)
        <section class="card mb-4 overflow-hidden" data-reveal>
            <div class="card-header justify-between px-5 py-4">
                <div class="flex items-center gap-3">
                    <span class="card-icon bg-sky-50 text-sky-600"><x-icon name="users" class="h-5 w-5" /></span>
                    <div>
                        <h2 class="font-semibold">{{ __('By supervisor') }}</h2>
                        <p class="text-xs text-zinc-500">{{ __('Entries, approvals and missing weeks for each supervisor\'s students') }}</p>
                    </div>
                </div>
                <a href="{{ route('reports.supervisors.export', $query) }}" class="btn btn-secondary px-3 py-1.5 text-xs print:hidden"><x-icon name="document" class="h-3.5 w-3.5" /> {{ __('Export') }}</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="table-head">
                        <tr>
                            <th class="px-5 py-2.5">{{ __('Supervisor') }}</th>
                            <th class="px-5 py-2.5 text-right">{{ __('Students') }}</th>
                            <th class="px-5 py-2.5 text-right">{{ __('Entries') }}</th>
                            <th class="px-5 py-2.5 text-right">{{ __('Approved') }}</th>
                            <th class="px-5 py-2.5 text-right">{{ __('Pending') }}</th>
                            <th class="px-5 py-2.5 text-right">{{ __('Missing weeks') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($supervisors as $s)
                            @php($missing = max(0, $s->students_count * $report->pastDue() - $s->due_logged))
                            <tr class="table-row">
                                <td class="px-5 py-2.5"><a href="{{ route('reports.index', [...$query, 'supervisor' => $s->id]) }}" class="font-semibold text-zinc-900 hover:text-brand-600">{{ $s->name }}</a></td>
                                <td class="px-5 py-2.5 text-right text-zinc-600">{{ $s->students_count }}</td>
                                <td class="px-5 py-2.5 text-right text-zinc-600">{{ $s->entries_count }}</td>
                                <td class="px-5 py-2.5 text-right font-medium text-emerald-600">{{ $s->approved_count }}</td>
                                <td class="px-5 py-2.5 text-right text-amber-600">{{ $s->pending_count }}</td>
                                <td @class(['px-5 py-2.5 text-right', 'font-semibold text-red-600' => $missing, 'text-zinc-400' => ! $missing])>{{ $missing }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-8 text-center text-zinc-500">{{ __('No supervisors yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($supervisors->hasPages())
                <div class="border-t border-zinc-100 px-5 py-3 print:hidden">{{ $supervisors->links() }}</div>
            @endif
        </section>
    @endif

    {{-- Student progress --}}
    <section class="card overflow-hidden" data-reveal>
        <div class="card-header px-5 py-4">
            <span class="card-icon bg-brand-50 text-brand-600"><x-icon name="chart" class="h-5 w-5" /></span>
            <div>
                <h2 class="font-semibold">{{ __('Student progress') }}</h2>
                <p class="text-xs text-zinc-500">{{ $students->total() }} {{ __(Str::plural('student', $students->total())) }}</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="table-head">
                    <tr>
                        <th class="px-5 py-2.5">{{ __('Student') }}</th>
                        @if ($isCoordinator) <th class="px-5 py-2.5">{{ __('Supervisor') }}</th> @endif
                        <th class="px-5 py-2.5 text-right">{{ __('Entries') }}</th>
                        <th class="px-5 py-2.5 text-right">{{ __('Approved') }}</th>
                        <th class="px-5 py-2.5 text-right">{{ __('Pending') }}</th>
                        <th class="px-5 py-2.5 text-right">{{ __('Fixing') }}</th>
                        <th class="px-5 py-2.5 text-right">{{ __('Late') }}</th>
                        <th class="px-5 py-2.5 text-right">{{ __('Overdue') }}</th>
                        <th class="px-5 py-2.5 text-right">{{ __('Meetings') }}</th>
                        <th class="px-5 py-2.5 print:hidden"><span class="sr-only">{{ __('Logbook') }}</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($rows as $r)
                        <tr class="table-row">
                            <td class="max-w-72 px-5 py-2.5">
                                <p class="font-semibold text-zinc-900">{{ $r['name'] }} <span class="text-xs font-normal text-zinc-500">{{ $r['matric'] }}</span></p>
                                <p class="truncate text-xs text-zinc-500" title="{{ $r['project'] }}">{{ $r['project'] ? $r['stage'].' · '.$r['project'] : 'No project title yet' }}</p>
                            </td>
                            @if ($isCoordinator) <td class="px-5 py-2.5 text-zinc-600">{{ $r['supervisor'] ?? 'Not assigned' }}</td> @endif
                            <td class="px-5 py-2.5 text-right text-zinc-600">{{ $r['entries'] }}</td>
                            <td class="px-5 py-2.5 text-right font-medium text-emerald-600">{{ $r['approved'] }}</td>
                            <td class="px-5 py-2.5 text-right text-amber-600">{{ $r['pending'] }}</td>
                            <td class="px-5 py-2.5 text-right text-orange-600">{{ $r['revision'] }}</td>
                            <td @class(['px-5 py-2.5 text-right', 'text-red-600' => $r['late'], 'text-zinc-400' => ! $r['late']])>{{ $r['late'] }}</td>
                            <td @class(['px-5 py-2.5 text-right', 'font-semibold text-red-600' => $r['overdue'], 'text-zinc-400' => ! $r['overdue']])>{{ $r['overdue'] }}</td>
                            <td class="px-5 py-2.5 text-right text-zinc-600">{{ $r['meetings'] }}</td>
                            <td class="px-5 py-2.5 text-right whitespace-nowrap print:hidden">
                                <a href="{{ route('logbooks.report', ['student' => $r['student']->id, 'semester' => $filters['semester']]) }}" title="{{ __('Full logbook') }}" class="icon-btn"><x-icon name="printer" class="h-4 w-4" /></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="px-5 py-10 text-center text-zinc-500">{{ __('No students match these filters.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($students->hasPages())
            <div class="border-t border-zinc-100 px-5 py-3 print:hidden">{{ $students->links() }}</div>
        @endif
    </section>
</x-layouts.app>
