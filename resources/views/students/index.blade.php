<x-layouts.app title="Students">
    <x-slot:actions>
        <a href="{{ route('students.create') }}" class="btn btn-primary"><x-icon name="plus" class="h-4 w-4" /> Add student</a>
    </x-slot:actions>

    <form method="GET" class="mb-5 max-w-sm">
        <div class="relative">
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-zinc-400" />
            <input type="search" name="q" value="{{ $search }}" placeholder="Search name or matric no." class="input pl-9">
        </div>
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm">
                <thead class="bg-zinc-50 text-left text-xs font-semibold tracking-wide text-zinc-500 uppercase">
                    <tr>
                        <th class="px-5 py-3">Student</th>
                        <th class="px-5 py-3">Email</th>
                        <th class="px-5 py-3">Progress</th>
                        <th class="px-5 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($students as $student)
                        <tr class="hover:bg-zinc-50">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-xs font-bold text-zinc-600">{{ $student->initials() }}</span>
                                    <div>
                                        <p class="font-medium">{{ $student->name }}</p>
                                        <p class="text-xs text-zinc-500">{{ $student->username }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-zinc-600">{{ $student->email ?? '—' }}</td>
                            <td class="px-5 py-4">
                                <a href="{{ route('logbooks.index', ['student' => $student->id]) }}" class="group block w-40">
                                    <p class="text-xs text-zinc-600 group-hover:text-brand-600">{{ $student->logbooks_count }} entries · {{ $student->reviewed_count }} reviewed</p>
                                    <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-zinc-100">
                                        <div class="h-full rounded-full bg-emerald-500" style="width: {{ min(100, round($student->reviewed_count / App\Models\Logbook::MAX_WEEKS * 100)) }}%"></div>
                                    </div>
                                </a>
                            </td>
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1">
                                    <a href="{{ route('students.edit', $student) }}" title="Edit" class="rounded-md p-1.5 text-zinc-500 hover:bg-zinc-100"><x-icon name="pencil" class="h-4 w-4" /></a>
                                    <form method="POST" action="{{ route('students.destroy', $student) }}" data-confirm="Remove {{ $student->name }}? All of their logbook entries will be deleted too.">
                                        @csrf @method('DELETE')
                                        <button type="submit" title="Remove" class="rounded-md p-1.5 text-zinc-500 hover:bg-red-50 hover:text-red-600"><x-icon name="trash" class="h-4 w-4" /></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-16 text-center text-zinc-500">
                                <x-icon name="users" class="mx-auto h-10 w-10 text-zinc-300" />
                                <p class="mt-3">{{ $search ? 'No students match "'.$search.'".' : 'No students yet.' }}</p>
                                @unless ($search)
                                    <a href="{{ route('students.create') }}" class="mt-2 inline-block font-medium text-brand-600">Register your first student</a>
                                @endunless
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($students->hasPages())
        <div class="mt-5">{{ $students->links() }}</div>
    @endif
</x-layouts.app>
