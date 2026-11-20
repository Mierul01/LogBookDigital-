@php($supervisorName = $logbook->student->supervisor?->name ?? 'Supervisor')

<x-layouts.app :title="'Week '.$logbook->week_no" :subtitle="$logbook->student->name.' · '.$logbook->entry_date->format('l, j F Y')">
    <x-slot:actions>
        <a href="{{ route('logbooks.index') }}" class="btn btn-secondary"><x-icon name="list" class="h-4 w-4" /> <span class="hidden sm:inline">All entries</span></a>
        <button type="button" onclick="window.print()" class="btn btn-secondary hidden sm:inline-flex"><x-icon name="printer" class="h-4 w-4" /> Print</button>
        @can('update', $logbook)
            <a href="{{ route('logbooks.edit', $logbook) }}" class="btn btn-primary"><x-icon name="pencil" class="h-4 w-4" /> {{ $logbook->needsRevision() ? 'Fix & resend' : 'Edit' }}</a>
        @endcan
    </x-slot:actions>

    @if ($logbook->needsRevision())
        <div class="mb-5 flex animate-fade-up gap-3 rounded-2xl border border-orange-200 bg-orange-50 px-5 py-4 text-sm text-orange-900">
            <x-icon name="warning" class="h-5 w-5 shrink-0 text-orange-500" />
            <div class="min-w-0 flex-1">
                <p class="font-semibold">Sent back for revision by {{ $supervisorName }}</p>
                <p class="mt-1 whitespace-pre-line text-orange-800">{{ $logbook->supervisor_comment }}</p>
                @can('update', $logbook)
                    <a href="{{ route('logbooks.edit', $logbook) }}" class="mt-3 inline-flex items-center gap-1 font-semibold text-orange-700 hover:text-orange-900">Fix this entry <x-icon name="chevron-right" class="h-4 w-4" /></a>
                @endcan
            </div>
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-3">
        {{-- Entry --}}
        <article class="card animate-fade-up overflow-hidden lg:col-span-2">
            <div class="relative overflow-hidden border-b border-zinc-100 bg-gradient-to-br from-brand-50/60 to-white px-6 py-5">
                <div class="absolute -top-10 -right-10 h-40 w-40 rounded-full bg-ukm-gold/15 blur-2xl"></div>
                <div class="relative flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <span class="flex h-14 w-14 flex-col items-center justify-center rounded-2xl bg-brand-600 leading-none text-white shadow-lg shadow-brand-600/25">
                            <span class="text-[10px] font-semibold tracking-wider uppercase opacity-80">Week</span>
                            <span class="mt-0.5 text-2xl font-bold">{{ $logbook->week_no }}</span>
                        </span>
                        <div>
                            <h2 class="text-lg font-bold">Log Book Mingguan</h2>
                            <p class="mt-0.5 flex items-center gap-1.5 text-sm text-zinc-500"><x-icon name="calendar" class="h-4 w-4" /> {{ $logbook->entry_date->format('l, j F Y') }}</p>
                            <p class="mt-0.5 flex items-center gap-1.5 text-sm text-zinc-500"><x-icon name="users" class="h-4 w-4" /> {{ $logbook->meetingSummary() }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5"><x-late-badge :logbook="$logbook" /> <x-status-badge :logbook="$logbook" /></div>
                </div>
            </div>

            <dl class="divide-y divide-zinc-100" data-reveal-group>
                @foreach ([
                    ['Kemajuan / Perkara Dibincang', 'Progress & matters discussed', $logbook->progress, 'document', 'bg-brand-50 text-brand-600'],
                    ['Status Semasa', 'Current status', $logbook->current_status, 'chart', 'bg-sky-50 text-sky-600'],
                    ['Masalah', 'Problems', $logbook->problem, 'warning', 'bg-amber-50 text-amber-600'],
                    ['Tugasan Minggu Hadapan', 'Next week\'s task', $logbook->next_week_task, 'flag', 'bg-emerald-50 text-emerald-600'],
                ] as [$label, $english, $value, $icon, $tone])
                    <div class="flex gap-4 px-6 py-5 transition-colors hover:bg-zinc-50/60" data-reveal>
                        <span class="card-icon {{ $tone }}"><x-icon :name="$icon" class="h-5 w-5" /></span>
                        <div class="min-w-0 flex-1">
                            <dt class="text-sm font-semibold text-zinc-900">{{ $label }} <span class="font-normal text-zinc-400">· {{ $english }}</span></dt>
                            <dd class="mt-1.5 leading-relaxed whitespace-pre-line text-zinc-700">{{ $value }}</dd>
                        </div>
                    </div>
                @endforeach
            </dl>

            @if ($logbook->attachments->isNotEmpty())
                <div class="border-t border-zinc-100 px-6 py-4">
                    <p class="text-sm font-semibold text-zinc-900">Lampiran <span class="font-normal text-zinc-400">· Attachments</span></p>
                    <ul class="mt-2 grid gap-2 sm:grid-cols-2">
                        @foreach ($logbook->attachments as $file)
                            <li>
                                <a href="{{ route('logbooks.attachment', [$logbook, $file]) }}" class="flex items-center gap-3 rounded-xl border border-zinc-200 px-3 py-2 text-sm transition hover:border-brand-400 hover:bg-brand-50">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><x-icon :name="$file->isImage() ? 'eye' : 'document'" class="h-4 w-4" /></span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate font-medium text-zinc-900">{{ $file->original_name }}</span>
                                        <span class="block text-xs text-zinc-500">{{ $file->humanSize() }}</span>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Sign-off block, like the paper logbook --}}
            <div class="grid border-t border-zinc-100 sm:grid-cols-2 sm:divide-x sm:divide-zinc-100">
                <div class="px-6 py-5">
                    <p class="text-xs font-semibold tracking-[0.08em] text-zinc-500 uppercase">Tandatangan Pelajar</p>
                    @if ($logbook->student_signature)
                        <img src="{{ $logbook->student_signature }}" alt="Student signature" class="mt-2 h-16 w-full object-contain object-left">
                    @else
                        <p class="mt-2 flex h-16 items-center text-sm text-zinc-400">Not signed</p>
                    @endif
                    <p class="border-t border-dashed border-zinc-300 pt-1.5 text-xs text-zinc-500">{{ $logbook->student->name }} · {{ ($logbook->submitted_at ?? $logbook->created_at)->format('j M Y') }}</p>
                </div>
                <div class="px-6 py-5">
                    <p class="text-xs font-semibold tracking-[0.08em] text-zinc-500 uppercase">Tandatangan Penyelia</p>
                    @if ($logbook->isReviewed())
                        <img src="{{ $logbook->supervisor_signature }}" alt="Supervisor signature" class="mt-2 h-16 w-full object-contain object-left">
                    @else
                        <p class="mt-2 flex h-16 items-center text-sm text-zinc-400">{{ $logbook->needsRevision() ? 'Sent back for revision' : 'Waiting for approval' }}</p>
                    @endif
                    <p class="border-t border-dashed border-zinc-300 pt-1.5 text-xs text-zinc-500">{{ $supervisorName }}@if ($logbook->isReviewed()) · {{ $logbook->reviewed_at->format('j M Y') }}@endif</p>
                </div>
            </div>
        </article>

        {{-- Sidebar --}}
        <aside class="space-y-5 print:hidden" data-reveal-group>
            {{-- Student and status timeline --}}
            <section class="card p-5" data-reveal>
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-600 text-sm font-bold text-white">{{ $logbook->student->initials() }}</span>
                    <div class="min-w-0">
                        <p class="truncate font-semibold">{{ $logbook->student->name }}</p>
                        <p class="text-sm text-zinc-500">{{ $logbook->student->username }}@if ($logbook->semester) · {{ $logbook->semester->name }}@endif</p>
                        @if ($project = $logbook->student->project($logbook->semester))
                            <p class="mt-0.5 text-xs text-zinc-500">{{ $project->stageLabel() }} · {{ $project->title }}</p>
                        @endif
                    </div>
                </div>

                <ol class="mt-4 space-y-3.5 border-t border-zinc-100 pt-4 text-sm">
                    <li class="flex gap-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-600"><x-icon name="check" class="h-4 w-4" /></span>
                        <div>
                            <p class="font-medium">{{ $logbook->student_signature ? 'Submitted & signed by student' : 'Submitted by student' }}</p>
                            <p class="text-xs text-zinc-500">{{ ($logbook->submitted_at ?? $logbook->created_at)->format('j M Y, g:i a') }}</p>
                        </div>
                    </li>
                    <li class="flex gap-3">
                        @if ($logbook->isReviewed())
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600"><x-icon name="shield" class="h-4 w-4" /></span>
                            <div>
                                <p class="font-medium">Approved by {{ $supervisorName }}</p>
                                <p class="text-xs text-zinc-500">{{ $logbook->reviewed_at->format('j M Y, g:i a') }}</p>
                            </div>
                        @elseif ($logbook->needsRevision())
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-orange-100 text-orange-600"><x-icon name="pencil" class="h-4 w-4" /></span>
                            <div>
                                <p class="font-medium">Sent back for revision</p>
                                <p class="text-xs text-zinc-500">Waiting for the student to fix it</p>
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
                <div class="card-header px-5 py-4">
                    <span class="card-icon bg-emerald-50 text-emerald-600"><x-icon name="shield" class="h-5 w-5" /></span>
                    <div>
                        <h3 class="font-semibold">Supervisor review</h3>
                        <p class="text-xs text-zinc-500">Komen, kelulusan &amp; tandatangan</p>
                    </div>
                </div>

                @can('review', $logbook)
                    <form method="POST" action="{{ route('logbooks.review', $logbook) }}" class="space-y-4 p-5">
                        @csrf @method('PUT')

                        <x-field name="supervisor_comment" label="Komen (Comment)">
                            <textarea id="supervisor_comment" name="supervisor_comment" rows="4" required placeholder="Feedback for the student…"
                                      @class(['input', 'input-error' => $errors->has('supervisor_comment')])>{{ old('supervisor_comment', $logbook->supervisor_comment) }}</textarea>
                        </x-field>

                        <x-signature-pad name="supervisor_signature" required-for="approve"
                                         hint="Needed to approve. Not needed to send back."
                                         empty-message="Please sign in the signature box before approving this entry." />

                        @if ($logbook->isReviewed())
                            <p class="rounded-xl bg-zinc-50 px-4 py-3 text-xs text-zinc-500">Signed on {{ $logbook->reviewed_at->format('j M Y, g:i a') }}. Sign again to update the review, or send it back to unlock it for the student.</p>
                        @endif

                        <div class="grid gap-2.5 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                            <button type="submit" name="decision" value="revision" class="btn bg-white text-orange-700 ring-1 ring-orange-200 ring-inset hover:bg-orange-50 focus:ring-orange-500/20"
                                    data-confirm="Week {{ $logbook->week_no }} goes back to {{ $logbook->student->name }} with your comment so they can fix it and send it again." data-confirm-title="Send back for revision?" data-confirm-button="Send back" data-confirm-icon="question">
                                <x-icon name="arrow-left" class="h-4 w-4" /> Send back
                            </button>
                            <button type="submit" name="decision" value="approve" class="btn btn-primary"
                                    data-confirm="Week {{ $logbook->week_no }} of {{ $logbook->student->name }} will be approved, signed and locked from further edits." data-confirm-title="Approve &amp; sign?" data-confirm-button="Approve &amp; sign">
                                <x-icon name="check" class="h-4 w-4" /> {{ $logbook->isReviewed() ? 'Update' : 'Approve & sign' }}
                            </button>
                        </div>
                    </form>
                @elseif ($logbook->isReviewed())
                    <div class="p-5">
                        <blockquote class="relative rounded-xl bg-emerald-50/60 p-4 pl-5 text-zinc-800">
                            <span class="absolute inset-y-3 left-0 w-1 rounded-full bg-emerald-500"></span>
                            <p class="leading-relaxed whitespace-pre-line">{{ $logbook->supervisor_comment }}</p>
                        </blockquote>
                        <p class="mt-3 text-xs text-zinc-500">Approved and signed. This entry is now locked.</p>
                    </div>
                @elseif ($logbook->needsRevision())
                    <div class="p-5">
                        <blockquote class="relative rounded-xl bg-orange-50/70 p-4 pl-5 text-zinc-800">
                            <span class="absolute inset-y-3 left-0 w-1 rounded-full bg-orange-500"></span>
                            <p class="leading-relaxed whitespace-pre-line">{{ $logbook->supervisor_comment }}</p>
                        </blockquote>
                        <p class="mt-3 text-xs text-zinc-500">Fix the entry and save it. It goes back to your supervisor automatically.</p>
                    </div>
                @else
                    <div class="px-5 py-8 text-center">
                        <span class="mx-auto flex h-12 w-12 animate-float items-center justify-center rounded-2xl bg-amber-50">
                            <x-icon name="clock" class="h-6 w-6 text-amber-500" />
                        </span>
                        <p class="mt-3 font-semibold">Waiting for review</p>
                        <p class="mt-1 text-sm text-zinc-500">{{ auth()->user()->isStudent() ? "Your supervisor hasn't signed this yet. You can still edit it until then." : "The supervisor hasn't reviewed this entry yet." }}</p>
                    </div>
                @endcan
            </section>

            {{-- History --}}
            <section class="card overflow-hidden" data-reveal>
                <div class="card-header px-5 py-4">
                    <span class="card-icon bg-zinc-100 text-zinc-600"><x-icon name="clock" class="h-5 w-5" /></span>
                    <div>
                        <h3 class="font-semibold">History</h3>
                        <p class="text-xs text-zinc-500">Every change to this entry</p>
                    </div>
                </div>
                <ol class="max-h-96 space-y-3 overflow-y-auto px-5 py-4 text-sm">
                    @forelse ($logbook->events as $event)
                        <li class="relative border-l-2 border-zinc-200 pl-4">
                            <span @class(['absolute top-1 -left-[5px] h-2 w-2 rounded-full',
                                'bg-emerald-500' => $event->action === 'approved',
                                'bg-orange-500' => $event->action === 'sent_back',
                                'bg-brand-500' => ! in_array($event->action, ['approved', 'sent_back'])])></span>
                            <p class="font-medium text-zinc-900">{{ $event->label() }}</p>
                            <p class="text-xs text-zinc-500">{{ $event->user?->name ?? 'Deleted user' }} · {{ $event->created_at->format('j M Y, g:i a') }}</p>
                            @if ($event->note)
                                <p class="mt-1 text-xs whitespace-pre-line text-zinc-600">{{ Str::limit($event->note, 300) }}</p>
                            @endif
                            @if ($event->changes)
                                <details class="mt-1 text-xs">
                                    <summary class="cursor-pointer text-brand-600 hover:text-brand-700">{{ count($event->changes) }} {{ Str::plural('field', count($event->changes)) }} changed</summary>
                                    <dl class="mt-1.5 space-y-2">
                                        @foreach ($event->changes as $field => [$old, $new])
                                            <div>
                                                <dt class="font-semibold text-zinc-700">{{ App\Models\LogbookEvent::TRACKED[$field] ?? $field }}</dt>
                                                <dd class="text-red-600 line-through decoration-red-300">{{ Str::limit((string) ($old ?? '—'), 400) }}</dd>
                                                <dd class="text-emerald-700">{{ Str::limit((string) ($new ?? '—'), 400) }}</dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                </details>
                            @endif
                        </li>
                    @empty
                        <li class="text-zinc-500">No history recorded for this entry yet.</li>
                    @endforelse
                </ol>
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
