<x-layouts.app :title="'Week '.$logbook->week_no" :subtitle="$logbook->student->name.' · '.$logbook->entry_date->format('l, j F Y')">
    <x-slot:actions>
        <a href="{{ route('logbooks.index') }}" class="btn btn-secondary"><x-icon name="arrow-left" class="h-4 w-4" /> <span class="hidden sm:inline">All entries</span></a>
        @can('update', $logbook)
            <a href="{{ route('logbooks.edit', $logbook) }}" class="btn btn-primary"><x-icon name="pencil" class="h-4 w-4" /> Edit</a>
        @endcan
    </x-slot:actions>

    <div class="grid gap-8 lg:grid-cols-3">
        {{-- Entry --}}
        <article class="card animate-fade-up overflow-hidden lg:col-span-2">
            <div class="relative overflow-hidden border-b border-zinc-100 bg-gradient-to-br from-zinc-50 to-white px-6 py-6 sm:px-8">
                <div class="absolute -top-10 -right-10 h-40 w-40 rounded-full bg-brand-100/60 blur-2xl"></div>
                <div class="relative flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <span class="flex h-16 w-16 flex-col items-center justify-center rounded-2xl bg-brand-600 leading-none text-white shadow-lg shadow-brand-600/25">
                            <span class="text-[10px] font-semibold tracking-wider uppercase opacity-80">Week</span>
                            <span class="mt-0.5 text-2xl font-bold">{{ $logbook->week_no }}</span>
                        </span>
                        <div>
                            <h2 class="text-lg font-bold">Log Book Mingguan</h2>
                            <p class="mt-0.5 flex items-center gap-1.5 text-sm text-zinc-500"><x-icon name="calendar" class="h-4 w-4" /> {{ $logbook->entry_date->format('l, j F Y') }}</p>
                        </div>
                    </div>
                    <x-status-badge :logbook="$logbook" />
                </div>
            </div>

            <dl class="divide-y divide-zinc-100" data-reveal-group>
                @foreach ([
                    ['Kemajuan / Perkara Dibincang', 'Progress & matters discussed', $logbook->progress, 'document', 'bg-brand-50 text-brand-600'],
                    ['Status Semasa', 'Current status', $logbook->current_status, 'chart', 'bg-sky-50 text-sky-600'],
                    ['Masalah', 'Problems', $logbook->problem, 'warning', 'bg-amber-50 text-amber-600'],
                    ['Tugasan Minggu Hadapan', 'Next week\'s task', $logbook->next_week_task, 'flag', 'bg-emerald-50 text-emerald-600'],
                ] as [$label, $english, $value, $icon, $tone])
                    <div class="flex gap-4 px-6 py-6 transition-colors hover:bg-zinc-50/60 sm:px-8" data-reveal>
                        <span class="card-icon {{ $tone }}"><x-icon :name="$icon" class="h-5 w-5" /></span>
                        <div class="min-w-0 flex-1">
                            <dt class="text-sm font-semibold text-zinc-900">{{ $label }} <span class="font-normal text-zinc-400">· {{ $english }}</span></dt>
                            <dd class="mt-2 leading-relaxed whitespace-pre-line text-zinc-700">{{ $value }}</dd>
                        </div>
                    </div>
                @endforeach
            </dl>
        </article>

        {{-- Sidebar --}}
        <aside class="space-y-8" data-reveal-group>
            {{-- Student --}}
            <section class="card p-6" data-reveal>
                <p class="text-xs font-semibold tracking-[0.08em] text-zinc-500 uppercase">Student</p>
                <div class="mt-4 flex items-center gap-3">
                    <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-zinc-900 text-sm font-bold text-white">{{ $logbook->student->initials() }}</span>
                    <div class="min-w-0">
                        <p class="truncate font-semibold">{{ $logbook->student->name }}</p>
                        <p class="text-sm text-zinc-500">{{ $logbook->student->username }}</p>
                    </div>
                </div>

                {{-- Status timeline --}}
                <ol class="mt-6 space-y-4 border-t border-zinc-100 pt-5 text-sm">
                    <li class="flex gap-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600"><x-icon name="check" class="h-4 w-4" /></span>
                        <div>
                            <p class="font-medium">Submitted</p>
                            <p class="text-xs text-zinc-500">{{ $logbook->created_at->format('j M Y, g:i a') }}</p>
                        </div>
                    </li>
                    <li class="flex gap-3">
                        @if ($logbook->isReviewed())
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600"><x-icon name="shield" class="h-4 w-4" /></span>
                            <div>
                                <p class="font-medium">Reviewed by {{ $logbook->student->supervisor?->name ?? 'supervisor' }}</p>
                                <p class="text-xs text-zinc-500">{{ $logbook->reviewed_at->format('j M Y, g:i a') }}</p>
                            </div>
                        @else
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600"><x-icon name="clock" class="h-4 w-4" /></span>
                            <div>
                                <p class="font-medium">Awaiting review</p>
                                <p class="text-xs text-zinc-500">{{ $logbook->student->supervisor?->name ?? 'No supervisor assigned' }}</p>
                            </div>
                        @endif
                    </li>
                </ol>
            </section>

            {{-- Review --}}
            <section class="card overflow-hidden" data-reveal>
                <div class="card-header">
                    <span class="card-icon bg-emerald-50 text-emerald-600"><x-icon name="shield" class="h-5 w-5" /></span>
                    <div>
                        <h3 class="font-semibold">Supervisor review</h3>
                        <p class="text-xs text-zinc-500">Komen &amp; tandatangan</p>
                    </div>
                </div>

                @can('review', $logbook)
                    <form method="POST" action="{{ route('logbooks.review', $logbook) }}" class="space-y-5 p-6"
                          data-confirm="Week {{ $logbook->week_no }} of {{ $logbook->student->name }} will be marked as reviewed and locked from further edits by the student." data-confirm-title="Approve &amp; sign?" data-confirm-button="Approve &amp; sign">
                        @csrf @method('PUT')

                        <x-field name="supervisor_comment" label="Komen (Comment)">
                            <textarea id="supervisor_comment" name="supervisor_comment" rows="4" required placeholder="Feedback for the student…"
                                      @class(['input', 'input-error' => $errors->has('supervisor_comment')])>{{ old('supervisor_comment', $logbook->supervisor_comment) }}</textarea>
                        </x-field>

                        <div data-signature-pad>
                            <div class="mb-1.5 flex items-center justify-between">
                                <span class="label mb-0">Tandatangan (Signature)</span>
                                <button type="button" data-signature-clear class="rounded-md px-2 py-1 text-xs font-medium text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900">Clear</button>
                            </div>
                            <canvas class="h-40 w-full cursor-crosshair touch-none rounded-xl border-2 border-dashed border-zinc-300 bg-zinc-50 transition hover:border-brand-400 hover:bg-white"></canvas>
                            <input type="hidden" name="supervisor_signature">
                            @error('supervisor_signature')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @else
                                <p class="mt-1.5 text-xs text-zinc-500">Sign with your mouse or finger.</p>
                            @enderror
                        </div>

                        @if ($logbook->isReviewed())
                            <div class="rounded-xl bg-zinc-50 p-4">
                                <p class="text-xs text-zinc-500">Current signature · {{ $logbook->reviewed_at->format('j M Y, g:i a') }}</p>
                                <img src="{{ $logbook->supervisor_signature }}" alt="Current signature" class="mt-2 h-16 object-contain">
                                <p class="mt-2 text-xs text-zinc-500">To update the review, sign again above.</p>
                            </div>
                        @endif

                        <button type="submit" class="btn btn-primary w-full"><x-icon name="check" class="h-4 w-4" /> {{ $logbook->isReviewed() ? 'Update review' : 'Approve & sign' }}</button>
                    </form>
                @elseif ($logbook->isReviewed())
                    <div class="space-y-5 p-6">
                        <blockquote class="relative rounded-xl bg-emerald-50/60 p-4 pl-5 text-zinc-800">
                            <span class="absolute inset-y-3 left-0 w-1 rounded-full bg-emerald-500"></span>
                            <p class="leading-relaxed whitespace-pre-line">{{ $logbook->supervisor_comment }}</p>
                        </blockquote>
                        <div>
                            <p class="text-xs text-zinc-500">Signature</p>
                            <img src="{{ $logbook->supervisor_signature }}" alt="Supervisor signature" class="mt-2 h-24 w-full rounded-xl border border-zinc-200 bg-white object-contain p-2">
                        </div>
                    </div>
                @else
                    <div class="px-6 py-10 text-center">
                        <span class="mx-auto flex h-14 w-14 animate-float items-center justify-center rounded-2xl bg-amber-50">
                            <x-icon name="clock" class="h-7 w-7 text-amber-500" />
                        </span>
                        <p class="mt-4 font-semibold">Waiting for review</p>
                        <p class="mt-1 text-sm text-zinc-500">Your supervisor hasn't signed this yet. You can still edit it until then.</p>
                    </div>
                @endcan
            </section>

            @can('delete', $logbook)
                <form method="POST" action="{{ route('logbooks.destroy', $logbook) }}" data-reveal data-confirm="The week {{ $logbook->week_no }} entry will be permanently deleted. This cannot be undone." data-confirm-title="Delete this entry?" data-confirm-button="Yes, delete" data-confirm-tone="danger">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger w-full"><x-icon name="trash" class="h-4 w-4" /> Delete entry</button>
                </form>
            @endcan
        </aside>
    </div>
</x-layouts.app>
