@php($editing = $logbook->exists)

<x-layouts.app :title="$editing ? 'Edit week '.$logbook->week_no : 'New logbook entry'">
    <x-slot:actions>
        <a href="{{ $editing ? route('logbooks.show', $logbook) : route('logbooks.index') }}" class="btn btn-secondary"><x-icon name="arrow-left" class="h-4 w-4" /> Back</a>
    </x-slot:actions>

    <form method="POST" action="{{ $editing ? route('logbooks.update', $logbook) : route('logbooks.store') }}" class="mx-auto max-w-4xl">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="card">
            <div class="border-b border-zinc-200 px-6 py-5">
                <h2 class="font-semibold">Log Book Mingguan</h2>
                <p class="mt-1 text-sm text-zinc-500">
                    {{ auth()->user()->name }} · Supervisor: {{ auth()->user()->supervisor?->name ?? '—' }}
                </p>
            </div>

            <div class="grid gap-6 p-6 sm:grid-cols-2">
                <x-field name="week_no" label="Week no.">
                    <select id="week_no" name="week_no" required @class(['input', 'input-error' => $errors->has('week_no')])>
                        @foreach (range(1, App\Models\Logbook::MAX_WEEKS) as $week)
                            <option value="{{ $week }}" @selected(old('week_no', $logbook->week_no) == $week) @disabled(in_array($week, $usedWeeks))>
                                Week {{ $week }}{{ in_array($week, $usedWeeks) ? ' (done)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </x-field>

                <x-field name="entry_date" label="Tarikh (Date)">
                    <input id="entry_date" name="entry_date" type="date" required
                           value="{{ old('entry_date', $logbook->entry_date?->format('Y-m-d')) }}"
                           @class(['input', 'input-error' => $errors->has('entry_date')])>
                </x-field>

                <x-field name="progress" label="Kemajuan / Perkara Dibincang" class="sm:col-span-2" hint="Progress made and matters discussed with your supervisor.">
                    <textarea id="progress" name="progress" rows="4" required @class(['input', 'input-error' => $errors->has('progress')])>{{ old('progress', $logbook->progress) }}</textarea>
                </x-field>

                <x-field name="current_status" label="Status Semasa (Current status)">
                    <textarea id="current_status" name="current_status" rows="4" required @class(['input', 'input-error' => $errors->has('current_status')])>{{ old('current_status', $logbook->current_status) }}</textarea>
                </x-field>

                <x-field name="problem" label="Masalah (Problems)">
                    <textarea id="problem" name="problem" rows="4" required @class(['input', 'input-error' => $errors->has('problem')])>{{ old('problem', $logbook->problem) }}</textarea>
                </x-field>

                <x-field name="next_week_task" label="Tugasan Minggu Hadapan (Next week's task)" class="sm:col-span-2">
                    <textarea id="next_week_task" name="next_week_task" rows="3" required @class(['input', 'input-error' => $errors->has('next_week_task')])>{{ old('next_week_task', $logbook->next_week_task) }}</textarea>
                </x-field>
            </div>

            <div class="flex justify-end gap-3 rounded-b-2xl border-t border-zinc-200 bg-zinc-50 px-6 py-4">
                <a href="{{ route('logbooks.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">{{ $editing ? 'Save changes' : 'Submit entry' }}</button>
            </div>
        </div>
    </form>
</x-layouts.app>
