@php($user = auth()->user())

<x-layouts.app :title="$user->isSupervisor() ? 'Student Logbooks' : 'My Logbook'"
               :subtitle="$user->isSupervisor() ? 'Read, comment on and sign your students\' weekly entries' : 'All your weekly entries in one place'">
    <x-slot:actions>
        <button type="button" onclick="window.print()" class="btn btn-secondary hidden sm:inline-flex"><x-icon name="printer" class="h-4 w-4" /> Print</button>
        @can('create', App\Models\Logbook::class)
            <a href="{{ route('logbooks.create') }}" class="btn btn-primary"><x-icon name="plus" class="h-4 w-4" /> <span class="hidden sm:inline">New entry</span></a>
        @endcan
    </x-slot:actions>

    {{-- Filters --}}
    <form method="GET" class="mb-6 flex animate-fade-up flex-wrap items-center justify-between gap-3 print:hidden">
        <div class="inline-flex rounded-xl border border-zinc-200 bg-white p-1 text-sm shadow-xs">
            @foreach (['' => ['All', 'document'], 'pending' => ['Pending', 'clock'], 'reviewed' => ['Reviewed', 'shield']] as $value => [$label, $icon])
                @php($active = ($filters['status'] ?? '') === $value)
                <a href="{{ route('logbooks.index', array_filter([...$filters, 'status' => $value])) }}"
                   @class(['inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 font-medium transition duration-200', 'bg-zinc-900 text-white shadow-sm' => $active, 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' => ! $active])>
                    <x-icon :name="$icon" class="h-4 w-4" /> {{ $label }}
                </a>
            @endforeach
        </div>
        <div class="flex items-center gap-3">
            @if ($user->isSupervisor() && $students->isNotEmpty())
                <input type="hidden" name="status" value="{{ $filters['status'] }}">
                <select name="student" onchange="this.form.submit()" data-select class="input w-auto min-w-64">
                    <option value="">All students</option>
                    @foreach ($students as $s)
                        <option value="{{ $s->id }}" @selected($filters['student'] == $s->id)>{{ $s->name }} ({{ $s->username }})</option>
                    @endforeach
                </select>
            @endif
            <p class="text-sm text-zinc-500">{{ $logbooks->total() }} {{ \Illuminate\Support\Str::plural('entry', $logbooks->total()) }}</p>
        </div>
    </form>

    <div class="card overflow-hidden" data-reveal>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="table-head">
                    <tr>
                        <th class="px-6 py-3.5">Week</th>
                        @if ($user->isSupervisor()) <th class="px-6 py-3.5">Student</th> @endif
                        <th class="px-6 py-3.5">Date</th>
                        <th class="px-6 py-3.5">Progress / Matters discussed</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 print:hidden"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($logbooks as $logbook)
                        @php($needsReview = $user->isSupervisor() && ! $logbook->isReviewed())
                        <tr class="table-row group">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex h-10 w-10 flex-col items-center justify-center rounded-xl bg-white leading-none ring-1 ring-zinc-200 transition duration-300 group-hover:bg-brand-600 group-hover:text-white">
                                    <span class="text-[8px] font-semibold tracking-wider uppercase opacity-60">Wk</span>
                                    <span class="text-sm font-bold">{{ $logbook->week_no }}</span>
                                </span>
                            </td>
                            @if ($user->isSupervisor())
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-zinc-900 text-xs font-bold text-white">{{ $logbook->student->initials() }}</span>
                                        <div>
                                            <p class="font-semibold text-zinc-900">{{ $logbook->student->name }}</p>
                                            <p class="text-xs text-zinc-500">{{ $logbook->student->username }}</p>
                                        </div>
                                    </div>
                                </td>
                            @endif
                            <td class="px-6 py-4 whitespace-nowrap text-zinc-600">
                                <span class="inline-flex items-center gap-1.5"><x-icon name="calendar" class="h-4 w-4 text-zinc-400" /> {{ $logbook->entry_date->format('j M Y') }}</span>
                            </td>
                            <td class="max-w-md px-6 py-4 text-zinc-700">
                                <a href="{{ route('logbooks.show', $logbook) }}" class="line-clamp-2 hover:text-brand-700">{{ $logbook->progress }}</a>
                            </td>
                            <td class="px-6 py-4"><x-status-badge :logbook="$logbook" /></td>
                            <td class="px-6 py-4 text-right whitespace-nowrap print:hidden">
                                <div class="inline-flex items-center gap-1">
                                    @if ($needsReview)
                                        <a href="{{ route('logbooks.show', $logbook) }}" class="btn btn-primary px-3 py-1.5 text-xs"><x-icon name="pencil" class="h-3.5 w-3.5" /> Review</a>
                                    @else
                                        <a href="{{ route('logbooks.show', $logbook) }}" title="View" class="icon-btn"><x-icon name="eye" class="h-4 w-4" /></a>
                                    @endif
                                    @can('update', $logbook)
                                        <a href="{{ route('logbooks.edit', $logbook) }}" title="Edit" class="icon-btn"><x-icon name="pencil" class="h-4 w-4" /></a>
                                    @endcan
                                    @can('delete', $logbook)
                                        <form method="POST" action="{{ route('logbooks.destroy', $logbook) }}" data-confirm="The week {{ $logbook->week_no }} entry will be permanently deleted. This cannot be undone." data-confirm-title="Delete this entry?" data-confirm-button="Yes, delete" data-confirm-tone="danger">
                                            @csrf @method('DELETE')
                                            <button type="submit" title="Delete" class="icon-btn hover:bg-red-50 hover:text-red-600"><x-icon name="trash" class="h-4 w-4" /></button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-20 text-center">
                                <span class="mx-auto flex h-16 w-16 animate-float items-center justify-center rounded-2xl bg-zinc-100">
                                    <x-icon name="book" class="h-8 w-8 text-zinc-400" />
                                </span>
                                <p class="mt-4 font-semibold text-zinc-900">No entries found</p>
                                <p class="mt-1 text-sm text-zinc-500">
                                    @if ($filters['status'] || $filters['student'])
                                        Try a different filter.
                                    @elseif ($user->isStudent())
                                        Start by writing your first weekly entry.
                                    @else
                                        Your students haven't submitted anything yet.
                                    @endif
                                </p>
                                @can('create', App\Models\Logbook::class)
                                    <a href="{{ route('logbooks.create') }}" class="btn btn-primary mt-5"><x-icon name="plus" class="h-4 w-4" /> New entry</a>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($logbooks->hasPages())
        <div class="mt-6 print:hidden">{{ $logbooks->links() }}</div>
    @endif
</x-layouts.app>
