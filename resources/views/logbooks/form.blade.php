@php
    $editing = $logbook->exists;
@endphp

<x-layouts.app :title="$editing ? 'Edit Week '.$logbook->week_no : 'New Logbook Entry'"
               subtitle="Log Book Mingguan · record what you did and discussed this week">
    <x-slot:actions>
        <a href="{{ route('logbooks.index') }}" class="btn btn-secondary"><x-icon name="list" class="h-4 w-4" /> <span class="hidden sm:inline">View all entries</span></a>
    </x-slot:actions>

    <form method="POST" action="{{ $editing ? route('logbooks.update', $logbook) : route('logbooks.store') }}" class="grid gap-5 lg:grid-cols-3"
          @unless ($editing) data-confirm="Your supervisor will be able to review it. You can still edit it until it is signed." data-confirm-title="Submit this entry?" data-confirm-button="Yes, submit" @endunless
          @if ($editing && $logbook->needsRevision()) data-confirm="Your supervisor will review week {{ $logbook->week_no }} again." data-confirm-title="Send the fixed entry?" data-confirm-button="Yes, send" @endif>
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="space-y-5 lg:col-span-2" data-reveal-group>
            @if ($editing && $logbook->needsRevision())
                <div class="flex gap-3 rounded-2xl border border-orange-200 bg-orange-50 px-5 py-4 text-sm text-orange-900">
                    <x-icon name="warning" class="h-5 w-5 shrink-0 text-orange-500" />
                    <div class="min-w-0">
                        <p class="font-semibold">What your supervisor asked you to fix</p>
                        <p class="mt-1 whitespace-pre-line text-orange-800">{{ $logbook->supervisor_comment }}</p>
                    </div>
                </div>
            @endif

            {{-- Week details --}}
            <section class="card" data-reveal>
                <div class="card-header">
                    <span class="card-icon bg-sky-50 text-sky-600"><x-icon name="calendar" class="h-5 w-5" /></span>
                    <div>
                        <h2 class="font-semibold">Week details</h2>
                        <p class="text-xs text-zinc-500">Which week is this entry for?</p>
                    </div>
                </div>
                <div class="grid gap-6 p-6 sm:grid-cols-2">
                    <x-field name="week_no" label="Week no.">
                        <select id="week_no" name="week_no" required data-select @class(['input', 'input-error' => $errors->has('week_no')])>
                            @foreach (range(1, App\Models\Logbook::MAX_WEEKS) as $week)
                                <option value="{{ $week }}" @selected(old('week_no', $logbook->week_no) == $week)
                                        @disabled(in_array($week, $usedWeeks)) @if (in_array($week, $usedWeeks)) data-badge="Logged" @endif>Week {{ $week }}</option>
                            @endforeach
                        </select>
                    </x-field>
                    <x-field name="entry_date" label="Tarikh (Date)">
                        <x-icon-input icon="calendar" name="entry_date" required data-datepicker placeholder="Pick a date"
                                      value="{{ old('entry_date', $logbook->entry_date?->format('Y-m-d')) }}" />
                    </x-field>
                </div>

                {{-- Meeting with the supervisor --}}
                @php
                    $mode = old('meeting_mode', $logbook->meeting_mode ?? 'face_to_face');
                @endphp
                <div class="border-t border-zinc-100 px-6 pt-4 pb-6" data-meeting>
                    <p class="label">Perjumpaan dengan penyelia (Meeting with supervisor)</p>
                    <div class="grid gap-2 sm:grid-cols-3">
                        @foreach (App\Models\Logbook::MEETING_MODES as $value => $label)
                            <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-zinc-200 px-3 py-2.5 text-sm transition hover:border-brand-400 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                                <input type="radio" name="meeting_mode" value="{{ $value }}" @checked($mode === $value) class="text-brand-600 focus:ring-brand-500" data-meeting-mode>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    @error('meeting_mode') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                    <div @class(['mt-4 grid gap-4 sm:grid-cols-3', 'hidden' => $mode === 'none']) data-meeting-details>
                        <x-field name="meeting_time" label="Masa (Time)">
                            <x-icon-input icon="clock" name="meeting_time" type="time" value="{{ old('meeting_time', $logbook->meeting_time ? substr($logbook->meeting_time, 0, 5) : '') }}" />
                        </x-field>
                        <x-field name="meeting_minutes" label="Tempoh (Minutes)">
                            <x-icon-input icon="clock" name="meeting_minutes" type="number" min="5" max="480" step="5" value="{{ old('meeting_minutes', $logbook->meeting_minutes) }}" placeholder="30" />
                        </x-field>
                        <x-field name="meeting_place" label="Tempat / Pautan (Place or link)">
                            <x-icon-input icon="building" name="meeting_place" value="{{ old('meeting_place', $logbook->meeting_place) }}" placeholder="Room or Teams link" />
                        </x-field>
                    </div>
                </div>
            </section>

            {{-- Progress --}}
            <section class="card" data-reveal>
                <div class="card-header">
                    <span class="card-icon bg-brand-50 text-brand-600"><x-icon name="document" class="h-5 w-5" /></span>
                    <div>
                        <h2 class="font-semibold">Progress report</h2>
                        <p class="text-xs text-zinc-500">Be specific, since your supervisor signs off on this</p>
                    </div>
                </div>
                <div class="grid gap-6 p-6 sm:grid-cols-2">
                    <x-field name="progress" label="Kemajuan / Perkara Dibincang" class="sm:col-span-2" hint="Progress made and matters discussed with your supervisor.">
                        <textarea id="progress" name="progress" rows="5" required placeholder="e.g. Completed the login module and presented the ERD…"
                                  @class(['input', 'input-error' => $errors->has('progress')])>{{ old('progress', $logbook->progress) }}</textarea>
                    </x-field>
                    <x-field name="current_status" label="Status Semasa (Current status)">
                        <textarea id="current_status" name="current_status" rows="4" required placeholder="Where does the project stand now?"
                                  @class(['input', 'input-error' => $errors->has('current_status')])>{{ old('current_status', $logbook->current_status) }}</textarea>
                    </x-field>
                    <x-field name="problem" label="Masalah (Problems)">
                        <textarea id="problem" name="problem" rows="4" required placeholder="Any blockers? Write “None” if there are none."
                                  @class(['input', 'input-error' => $errors->has('problem')])>{{ old('problem', $logbook->problem) }}</textarea>
                    </x-field>
                    <x-field name="next_week_task" label="Tugasan Minggu Hadapan (Next week's task)" class="sm:col-span-2">
                        <textarea id="next_week_task" name="next_week_task" rows="3" required placeholder="What will you work on next week?"
                                  @class(['input', 'input-error' => $errors->has('next_week_task')])>{{ old('next_week_task', $logbook->next_week_task) }}</textarea>
                    </x-field>
                </div>
                <div class="border-t border-zinc-100 px-6 py-5">
                    @if ($editing && $logbook->student_signature)
                        <div class="mb-4 flex items-center gap-4 rounded-xl bg-zinc-50 px-4 py-3">
                            <img src="{{ $logbook->student_signature }}" alt="Your current signature" class="h-10 w-32 object-contain object-left">
                            <p class="text-xs text-zinc-500">Your current signature is kept. Sign below only if you want to replace it.</p>
                        </div>
                    @endif
                    <x-signature-pad name="student_signature" label="Tandatangan Pelajar (Your signature)" :optional="$editing"
                                     :hint="$editing ? 'Optional when editing.' : 'Sign to confirm this entry is true. Use your mouse or finger.'"
                                     empty-message="Please sign in the signature box before submitting your entry." class="max-w-md" />
                </div>
                <div class="flex justify-end gap-3 rounded-b-2xl border-t border-zinc-100 bg-zinc-50/60 px-6 py-4">
                    <a href="{{ route('logbooks.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary"><x-icon name="check" class="h-4 w-4" /> {{ $editing ? ($logbook->needsRevision() ? 'Save & resend' : 'Save changes') : 'Sign & submit' }}</button>
                </div>
            </section>
        </div>

        {{-- Side panel --}}
        <aside class="space-y-5" data-reveal-group>
            <section class="card p-6" data-reveal>
                <p class="text-xs font-semibold tracking-[0.08em] text-zinc-500 uppercase">Submitting as</p>
                <div class="mt-4 flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-sm font-bold text-white">{{ auth()->user()->initials() }}</span>
                    <div class="min-w-0">
                        <p class="truncate font-semibold">{{ auth()->user()->name }}</p>
                        <p class="text-sm text-zinc-500">{{ auth()->user()->username }}</p>
                    </div>
                </div>
                <div class="mt-5 flex items-center gap-3 rounded-xl bg-zinc-50 p-3">
                    <x-icon name="identification" class="h-5 w-5 text-zinc-400" />
                    <div class="min-w-0 text-sm">
                        <p class="text-xs text-zinc-500">Reviewed by</p>
                        <p class="truncate font-medium">{{ auth()->user()->supervisor?->name ?? 'No supervisor assigned' }}</p>
                    </div>
                </div>
            </section>

            <section class="card overflow-hidden" data-reveal>
                <div class="bg-gradient-to-br from-sky-50 to-white p-6">
                    <div class="flex items-center gap-2 text-sky-700">
                        <x-icon name="sparkles" class="h-5 w-5" />
                        <h3 class="font-semibold">Writing tips</h3>
                    </div>
                    <ul class="mt-4 space-y-3 text-sm text-zinc-600">
                        <li class="flex gap-2.5"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" /> Mention concrete outputs: features built, chapters written, diagrams done.</li>
                        <li class="flex gap-2.5"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" /> Note decisions agreed with your supervisor.</li>
                        <li class="flex gap-2.5"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" /> Keep next week's tasks small enough to finish.</li>
                    </ul>
                </div>
            </section>

            <section class="flex gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800" data-reveal>
                <x-icon name="warning" class="h-5 w-5 shrink-0 text-amber-500" />
                <p>Once your supervisor approves and signs this entry it's locked. If they send it back, you can fix it and send it again.</p>
            </section>
        </aside>
    </form>
</x-layouts.app>
