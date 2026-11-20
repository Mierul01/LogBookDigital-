<x-layouts.app title="{{ __('Students') }}" subtitle="{{ __('Register and manage the students you supervise') }}">
    <x-slot:actions>
        <a href="{{ route('students.create') }}" class="btn btn-primary"><x-icon name="plus" class="h-4 w-4" /> <span class="hidden sm:inline">{{ __('Add student') }}</span></a>
    </x-slot:actions>

    <div class="mb-6 flex animate-fade-up flex-wrap items-center justify-between gap-3">
        <form method="GET" class="w-full max-w-sm">
            <x-icon-input icon="search" name="q" type="search" value="{{ $search }}" placeholder="{{ __('Search name or matric no.') }}" />
        </form>
        <p class="text-sm text-zinc-500">{{ $students->total() }} {{ __(\Illuminate\Support\Str::plural('student', $students->total())) }}</p>
    </div>

    <div class="card overflow-hidden" data-reveal>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="table-head">
                    <tr>
                        <th class="px-6 py-3.5">{{ __('Student') }}</th>
                        <th class="px-6 py-3.5">{{ __('Email') }}</th>
                        <th class="px-6 py-3.5">{{ __('Progress') }}</th>
                        <th class="px-6 py-3.5"><span class="sr-only">{{ __('Actions') }}</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($students as $student)
                        @php($pct = min(100, round($student->reviewed_count / App\Models\Logbook::MAX_WEEKS * 100)))
                        <tr class="table-row group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-xs font-bold text-zinc-600 ring-1 ring-zinc-200 transition duration-300 group-hover:bg-brand-600 group-hover:text-white group-hover:ring-brand-600">{{ $student->initials() }}</span>
                                    <div>
                                        <p class="font-semibold text-zinc-900">{{ $student->name }}</p>
                                        <p class="text-xs text-zinc-500">{{ $student->username }}</p>
                                        @if ($student->projects->first())
                                            <p class="max-w-64 truncate text-xs text-zinc-400" title="{{ $student->projects->first()->title }}">{{ $student->projects->first()->title }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-zinc-600">{{ $student->email ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <a href="{{ route('logbooks.index', ['student' => $student->id]) }}" class="block w-48">
                                    <div class="flex justify-between text-xs">
                                        <span class="text-zinc-600 group-hover:text-brand-600">{{ __(':count entries', ['count' => $student->logbooks_count]) }}</span>
                                        <span class="font-medium text-emerald-600">{{ __(':count approved', ['count' => $student->reviewed_count]) }}</span>
                                    </div>
                                    @if ($pastDue > $student->due_logged)
                                        <p class="mt-0.5 text-xs font-semibold text-red-600">{{ $pastDue - $student->due_logged }} {{ __(':count overdue', ['count' => __(Str::plural('week', $pastDue - $student->due_logged))]) }}</p>
                                    @endif
                                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-zinc-100">
                                        <div class="h-full w-0 rounded-full bg-gradient-to-r from-emerald-400 to-emerald-600 transition-[width] duration-1000 ease-(--ease-out-soft)" data-progress="{{ $pct }}"></div>
                                    </div>
                                </a>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1">
                                    <a href="{{ route('logbooks.index', ['student' => $student->id]) }}" title="{{ __('View logbook') }}" class="icon-btn"><x-icon name="book" class="h-4 w-4" /></a>
                                    <a href="{{ route('students.edit', $student) }}" title="{{ __('Edit') }}" class="icon-btn"><x-icon name="pencil" class="h-4 w-4" /></a>
                                    <form method="POST" action="{{ route('students.destroy', $student) }}" data-confirm="{{ $student->name }} and all of their logbook entries will be permanently deleted." data-confirm-title="Remove student?" data-confirm-button="Yes, remove" data-confirm-tone="danger">
                                        @csrf @method('DELETE')
                                        <button type="submit" title="{{ __('Remove') }}" class="icon-btn hover:bg-red-50 hover:text-red-600"><x-icon name="trash" class="h-4 w-4" /></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-20 text-center">
                                <span class="mx-auto flex h-16 w-16 animate-float items-center justify-center rounded-2xl bg-zinc-100">
                                    <x-icon name="users" class="h-8 w-8 text-zinc-400" />
                                </span>
                                <p class="mt-4 font-semibold text-zinc-900">{{ __($search ? 'No matches' : 'No students yet') }}</p>
                                <p class="mt-1 text-sm text-zinc-500">{{ $search ? 'No students match "'.$search.'".' : 'Register the students you supervise so they can start their logbook.' }}</p>
                                @unless ($search)
                                    <a href="{{ route('students.create') }}" class="btn btn-primary mt-5"><x-icon name="plus" class="h-4 w-4" /> {{ __('Add student') }}</a>
                                @endunless
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($students->hasPages())
        <div class="mt-6">{{ $students->links() }}</div>
    @endif
</x-layouts.app>
