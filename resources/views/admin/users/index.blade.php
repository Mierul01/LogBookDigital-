<x-layouts.app title="Users" subtitle="Every student, supervisor and coordinator account in the faculty">
    <x-slot:actions>
        <a href="{{ route('admin.users.create', ['role' => $filters['role'] ?? 'student']) }}" class="btn btn-primary"><x-icon name="plus" class="h-4 w-4" /> <span class="hidden sm:inline">Add user</span></a>
    </x-slot:actions>

    {{-- Role tabs and filters --}}
    <form method="GET" class="mb-4 flex animate-fade-up flex-wrap items-center justify-between gap-3">
        <div class="inline-flex rounded-xl border border-zinc-200 bg-white p-1 text-sm shadow-xs">
            @foreach (['' => 'All', 'student' => 'Students', 'supervisor' => 'Supervisors', 'coordinator' => 'Coordinators'] as $value => $label)
                @php
                    $active = ($filters['role'] ?? '') === $value;
                    $count = $value === '' ? $counts->sum() : ($counts[$value] ?? 0);
                @endphp
                <a href="{{ route('admin.users.index', array_filter([...$filters, 'role' => $value, 'supervisor' => $value === 'student' ? $filters['supervisor'] : null])) }}"
                   @class(['inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 font-medium transition', 'bg-brand-600 text-white shadow-sm' => $active, 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' => ! $active])>
                    {{ $label }} <span @class(['rounded-full px-1.5 text-[11px]', 'bg-white/20' => $active, 'bg-zinc-100 text-zinc-500' => ! $active])>{{ $count }}</span>
                </a>
            @endforeach
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <input type="hidden" name="role" value="{{ $filters['role'] }}">
            <label class="input-icon-wrap relative">
                <x-icon name="search" class="input-icon pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-zinc-400" />
                <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Name, username or email…" class="input w-60 py-2.5 pl-9">
            </label>
            @if (($filters['role'] ?? null) === 'student' && $supervisors->isNotEmpty())
                <select name="supervisor" onchange="this.form.submit()" data-select class="input w-auto min-w-56">
                    <option value="">All supervisors</option>
                    @foreach ($supervisors as $s)
                        <option value="{{ $s->id }}" @selected($filters['supervisor'] == $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            @endif
        </div>
    </form>

    <div class="card overflow-hidden" data-reveal>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="table-head">
                    <tr>
                        <th class="px-5 py-3">User</th>
                        <th class="px-5 py-3">Role</th>
                        <th class="px-5 py-3">Email</th>
                        <th class="px-5 py-3">Supervisor / Students</th>
                        <th class="px-5 py-3">Logbook</th>
                        <th class="px-5 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($users as $account)
                        @php
                            $roleTone = match ($account->role) {
                                'coordinator' => 'bg-ukm-gold/15 text-amber-700 ring-amber-500/25',
                                'supervisor' => 'bg-sky-50 text-sky-700 ring-sky-500/20',
                                default => 'bg-brand-50 text-brand-700 ring-brand-500/20',
                            };
                        @endphp
                        <tr class="table-row">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-600 text-xs font-bold text-white">{{ $account->initials() }}</span>
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-zinc-900">{{ $account->name }} @if ($account->is(auth()->user()))<span class="text-xs font-normal text-zinc-400">(you)</span>@endif</p>
                                        <p class="text-xs text-zinc-500">{{ $account->username }}</p>
                                        @if ($account->projects->first())
                                            <p class="max-w-64 truncate text-xs text-zinc-400" title="{{ $account->projects->first()->title }}">{{ $account->projects->first()->title }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3"><span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ring-1 ring-inset {{ $roleTone }}">{{ $account->role }}</span></td>
                            <td class="px-5 py-3 text-zinc-600">{{ $account->email ?? '—' }}</td>
                            <td class="px-5 py-3 text-zinc-600">
                                @if ($account->isStudent())
                                    {{ $account->supervisor?->name ?? 'Not assigned' }}
                                @elseif ($account->isSupervisor())
                                    <a href="{{ route('admin.users.index', ['role' => 'student', 'supervisor' => $account->id]) }}" class="hover:text-brand-600">{{ $account->students_count }} {{ Str::plural('student', $account->students_count) }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @if ($account->isStudent())
                                    <a href="{{ route('logbooks.index', ['student' => $account->id]) }}" class="block w-36">
                                        <span class="flex justify-between text-xs text-zinc-600"><span>{{ $account->logbooks_count }} entries</span><span class="font-medium text-emerald-600">{{ $account->approved_count }}/{{ App\Models\Logbook::MAX_WEEKS }}</span></span>
                                        <span class="mt-1.5 block h-1.5 overflow-hidden rounded-full bg-zinc-100"><span class="block h-full rounded-full bg-emerald-500" style="width: {{ min(100, round($account->approved_count / App\Models\Logbook::MAX_WEEKS * 100)) }}%"></span></span>
                                    </a>
                                @else
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1">
                                    @if ($account->isStudent())
                                        <a href="{{ route('logbooks.report', ['student' => $account->id]) }}" title="Full logbook" class="icon-btn"><x-icon name="printer" class="h-4 w-4" /></a>
                                    @endif
                                    <a href="{{ route('admin.users.edit', $account) }}" title="Edit" class="icon-btn"><x-icon name="pencil" class="h-4 w-4" /></a>
                                    @unless ($account->is(auth()->user()))
                                        <form method="POST" action="{{ route('admin.users.destroy', $account) }}" data-confirm="{{ $account->name }}'s account will be deleted.{{ $account->isStudent() ? ' Their logbook entries are deleted too.' : '' }} This cannot be undone." data-confirm-title="Delete account?" data-confirm-button="Yes, delete" data-confirm-tone="danger">
                                            @csrf @method('DELETE')
                                            <button type="submit" title="Delete" class="icon-btn hover:bg-red-50 hover:text-red-600"><x-icon name="trash" class="h-4 w-4" /></button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-14 text-center text-sm text-zinc-500">No users match these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())
            <div class="border-t border-zinc-100 px-5 py-3.5">{{ $users->links() }}</div>
        @endif
    </div>
</x-layouts.app>
