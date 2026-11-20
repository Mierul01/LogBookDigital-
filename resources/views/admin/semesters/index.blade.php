<x-layouts.app title="Semesters" subtitle="New logbook entries go into the current semester">
    <x-slot:actions>
        <a href="{{ route('admin.semesters.create') }}" class="btn btn-primary"><x-icon name="plus" class="h-4 w-4" /> <span class="hidden sm:inline">Add semester</span></a>
    </x-slot:actions>

    <div class="card overflow-hidden" data-reveal>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="table-head">
                    <tr>
                        <th class="px-5 py-3">Semester</th>
                        <th class="px-5 py-3">Dates</th>
                        <th class="px-5 py-3">Week now</th>
                        <th class="px-5 py-3">Projects</th>
                        <th class="px-5 py-3">Entries</th>
                        <th class="px-5 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($semesters as $semester)
                        <tr class="table-row">
                            <td class="px-5 py-3">
                                <span class="font-semibold text-zinc-900">{{ $semester->name }}</span>
                                @if ($semester->is_current)
                                    <span class="ml-2 inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-600/20 ring-inset"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Current</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap text-zinc-600">{{ $semester->starts_on->format('j M Y') }} – {{ $semester->endsOn()->format('j M Y') }}</td>
                            <td class="px-5 py-3 text-zinc-600">
                                @php($week = $semester->currentWeek())
                                {{ $week === 0 ? 'Not started' : ($semester->endsOn()->isPast() ? 'Finished' : 'Week '.$week) }}
                            </td>
                            <td class="px-5 py-3 text-zinc-600">{{ $semester->projects_count }}</td>
                            <td class="px-5 py-3 text-zinc-600">{{ $semester->logbooks_count }}</td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1">
                                    @unless ($semester->is_current)
                                        <form method="POST" action="{{ route('admin.semesters.current', $semester) }}" data-confirm="New logbook entries from every student will go into {{ $semester->name }}." data-confirm-title="Make this the current semester?" data-confirm-button="Yes, make current">
                                            @csrf
                                            <button type="submit" class="btn btn-secondary px-2.5 py-1 text-xs">Make current</button>
                                        </form>
                                    @endunless
                                    <a href="{{ route('admin.semesters.edit', $semester) }}" title="Edit" class="icon-btn"><x-icon name="pencil" class="h-4 w-4" /></a>
                                    @if (! $semester->is_current && $semester->logbooks_count === 0)
                                        <form method="POST" action="{{ route('admin.semesters.destroy', $semester) }}" data-confirm="{{ $semester->name }} will be deleted." data-confirm-title="Delete semester?" data-confirm-button="Yes, delete" data-confirm-tone="danger">
                                            @csrf @method('DELETE')
                                            <button type="submit" title="Delete" class="icon-btn hover:bg-red-50 hover:text-red-600"><x-icon name="trash" class="h-4 w-4" /></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-14 text-center text-sm text-zinc-500">No semesters yet. Add one so students can start their logbook.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($semesters->hasPages())
            <div class="border-t border-zinc-100 px-5 py-3.5">{{ $semesters->links() }}</div>
        @endif
    </div>
</x-layouts.app>
