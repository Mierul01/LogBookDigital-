@php($user = auth()->user())

<x-layouts.app :title="$user->isSupervisor() ? 'Student Logbooks' : 'My Logbook'">
    <x-slot:actions>
        <button type="button" onclick="window.print()" class="btn btn-secondary hidden sm:inline-flex"><x-icon name="printer" class="h-4 w-4" /> Print</button>
        @can('create', App\Models\Logbook::class)
            <a href="{{ route('logbooks.create') }}" class="btn btn-primary"><x-icon name="plus" class="h-4 w-4" /> New entry</a>
        @endcan
    </x-slot:actions>

    {{-- Filters --}}
    <form method="GET" class="mb-5 flex flex-wrap items-center gap-3 print:hidden">
        <div class="inline-flex rounded-lg border border-zinc-200 bg-white p-1 text-sm shadow-xs">
            @foreach (['' => 'All', 'pending' => 'Pending', 'reviewed' => 'Reviewed'] as $value => $label)
                <a href="{{ route('logbooks.index', array_filter([...$filters, 'status' => $value])) }}"
                   @class(['rounded-md px-3 py-1.5 font-medium', 'bg-zinc-900 text-white' => ($filters['status'] ?? '') === $value, 'text-zinc-600 hover:text-zinc-900' => ($filters['status'] ?? '') !== $value])>{{ $label }}</a>
            @endforeach
        </div>
        @if ($user->isSupervisor() && $students->isNotEmpty())
            <input type="hidden" name="status" value="{{ $filters['status'] }}">
            <select name="student" onchange="this.form.submit()" class="input w-auto">
                <option value="">All students</option>
                @foreach ($students as $s)
                    <option value="{{ $s->id }}" @selected($filters['student'] == $s->id)>{{ $s->name }} ({{ $s->username }})</option>
                @endforeach
            </select>
        @endif
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm">
                <thead class="bg-zinc-50 text-left text-xs font-semibold tracking-wide text-zinc-500 uppercase">
                    <tr>
                        <th class="px-5 py-3">Week</th>
                        @if ($user->isSupervisor()) <th class="px-5 py-3">Student</th> @endif
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Progress / Matters discussed</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 print:hidden"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($logbooks as $logbook)
                        <tr class="hover:bg-zinc-50">
                            <td class="px-5 py-4 font-semibold whitespace-nowrap">Week {{ $logbook->week_no }}</td>
                            @if ($user->isSupervisor())
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <p class="font-medium">{{ $logbook->student->name }}</p>
                                    <p class="text-xs text-zinc-500">{{ $logbook->student->username }}</p>
                                </td>
                            @endif
                            <td class="px-5 py-4 whitespace-nowrap text-zinc-600">{{ $logbook->entry_date->format('j M Y') }}</td>
                            <td class="max-w-md px-5 py-4 text-zinc-700">{{ \Illuminate\Support\Str::limit($logbook->progress, 110) }}</td>
                            <td class="px-5 py-4"><x-status-badge :logbook="$logbook" /></td>
                            <td class="px-5 py-4 text-right whitespace-nowrap print:hidden">
                                <div class="inline-flex items-center gap-1">
                                    <a href="{{ route('logbooks.show', $logbook) }}" title="{{ $user->isSupervisor() && ! $logbook->isReviewed() ? 'Review' : 'View' }}"
                                       @class(['rounded-md p-1.5 hover:bg-zinc-100', 'text-brand-600' => $user->isSupervisor() && ! $logbook->isReviewed(), 'text-zinc-500' => ! ($user->isSupervisor() && ! $logbook->isReviewed())])>
                                        <x-icon name="{{ $user->isSupervisor() && ! $logbook->isReviewed() ? 'pencil' : 'eye' }}" class="h-4 w-4" />
                                    </a>
                                    @can('update', $logbook)
                                        <a href="{{ route('logbooks.edit', $logbook) }}" title="Edit" class="rounded-md p-1.5 text-zinc-500 hover:bg-zinc-100"><x-icon name="pencil" class="h-4 w-4" /></a>
                                    @endcan
                                    @can('delete', $logbook)
                                        <form method="POST" action="{{ route('logbooks.destroy', $logbook) }}" data-confirm="Delete the week {{ $logbook->week_no }} entry? This cannot be undone.">
                                            @csrf @method('DELETE')
                                            <button type="submit" title="Delete" class="rounded-md p-1.5 text-zinc-500 hover:bg-red-50 hover:text-red-600"><x-icon name="trash" class="h-4 w-4" /></button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-16 text-center text-zinc-500">
                                <x-icon name="book" class="mx-auto h-10 w-10 text-zinc-300" />
                                <p class="mt-3">No logbook entries found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($logbooks->hasPages())
        <div class="mt-5 print:hidden">{{ $logbooks->links() }}</div>
    @endif
</x-layouts.app>
