<x-layouts.app :title="'Week '.$logbook->week_no.' · '.$logbook->student->name">
    <x-slot:actions>
        <a href="{{ route('logbooks.index') }}" class="btn btn-secondary"><x-icon name="arrow-left" class="h-4 w-4" /> <span class="hidden sm:inline">All entries</span></a>
        @can('update', $logbook)
            <a href="{{ route('logbooks.edit', $logbook) }}" class="btn btn-primary"><x-icon name="pencil" class="h-4 w-4" /> Edit</a>
        @endcan
    </x-slot:actions>

    <div class="mx-auto grid max-w-6xl gap-6 lg:grid-cols-3">
        {{-- Entry --}}
        <div class="card lg:col-span-2">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 px-6 py-5">
                <div>
                    <h2 class="text-lg font-semibold">Week {{ $logbook->week_no }}</h2>
                    <p class="text-sm text-zinc-500">{{ $logbook->entry_date->format('l, j F Y') }}</p>
                </div>
                <x-status-badge :logbook="$logbook" />
            </div>
            <dl class="divide-y divide-zinc-100">
                @foreach ([
                    'Kemajuan / Perkara Dibincang' => $logbook->progress,
                    'Status Semasa' => $logbook->current_status,
                    'Masalah' => $logbook->problem,
                    'Tugasan Minggu Hadapan' => $logbook->next_week_task,
                ] as $label => $value)
                    <div class="px-6 py-5">
                        <dt class="text-xs font-semibold tracking-wide text-zinc-500 uppercase">{{ $label }}</dt>
                        <dd class="mt-2 whitespace-pre-line text-zinc-800">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        {{-- Sidebar: student + review --}}
        <div class="space-y-6">
            <div class="card p-6">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-full bg-zinc-900 text-sm font-bold text-white">{{ $logbook->student->initials() }}</span>
                    <div>
                        <p class="font-semibold">{{ $logbook->student->name }}</p>
                        <p class="text-sm text-zinc-500">{{ $logbook->student->username }}</p>
                    </div>
                </div>
                <p class="mt-4 text-sm text-zinc-500">Supervisor</p>
                <p class="font-medium">{{ $logbook->student->supervisor?->name ?? '—' }}</p>
            </div>

            <div class="card">
                <div class="border-b border-zinc-200 px-6 py-4">
                    <h3 class="font-semibold">Supervisor review</h3>
                </div>

                @can('review', $logbook)
                    <form method="POST" action="{{ route('logbooks.review', $logbook) }}" class="space-y-5 p-6">
                        @csrf @method('PUT')

                        <x-field name="supervisor_comment" label="Komen (Comment)">
                            <textarea id="supervisor_comment" name="supervisor_comment" rows="4" required
                                      @class(['input', 'input-error' => $errors->has('supervisor_comment')])>{{ old('supervisor_comment', $logbook->supervisor_comment) }}</textarea>
                        </x-field>

                        <div data-signature-pad>
                            <div class="mb-1.5 flex items-center justify-between">
                                <span class="label mb-0">Tandatangan (Signature)</span>
                                <button type="button" data-signature-clear class="text-xs font-medium text-zinc-500 hover:text-zinc-900">Clear</button>
                            </div>
                            <canvas class="h-40 w-full touch-none rounded-lg border border-dashed border-zinc-300 bg-zinc-50"></canvas>
                            <input type="hidden" name="supervisor_signature">
                            @error('supervisor_signature')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @else
                                <p class="mt-1.5 text-xs text-zinc-500">Sign with your mouse or finger.</p>
                            @enderror
                        </div>

                        @if ($logbook->isReviewed())
                            <div class="rounded-lg bg-zinc-50 p-3">
                                <p class="text-xs text-zinc-500">Current signature · {{ $logbook->reviewed_at->format('j M Y, g:i a') }}</p>
                                <img src="{{ $logbook->supervisor_signature }}" alt="Current signature" class="mt-2 h-16 object-contain">
                                <p class="mt-2 text-xs text-zinc-500">To update the review, sign again above.</p>
                            </div>
                        @endif

                        <button type="submit" class="btn btn-primary w-full"><x-icon name="check" class="h-4 w-4" /> {{ $logbook->isReviewed() ? 'Update review' : 'Approve & sign' }}</button>
                    </form>
                @elseif ($logbook->isReviewed())
                    <div class="space-y-4 p-6">
                        <p class="whitespace-pre-line text-zinc-800">{{ $logbook->supervisor_comment }}</p>
                        <img src="{{ $logbook->supervisor_signature }}" alt="Supervisor signature" class="h-24 rounded-lg border border-zinc-200 bg-white object-contain p-2">
                        <p class="text-xs text-zinc-500">Signed {{ $logbook->reviewed_at->format('j M Y, g:i a') }}</p>
                    </div>
                @else
                    <div class="p-6 text-center">
                        <x-icon name="clock" class="mx-auto h-8 w-8 text-amber-400" />
                        <p class="mt-2 text-sm text-zinc-500">Waiting for your supervisor to review this entry. You can still edit it until then.</p>
                    </div>
                @endcan
            </div>

            @can('delete', $logbook)
                <form method="POST" action="{{ route('logbooks.destroy', $logbook) }}" data-confirm="Delete the week {{ $logbook->week_no }} entry? This cannot be undone.">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger w-full"><x-icon name="trash" class="h-4 w-4" /> Delete entry</button>
                </form>
            @endcan
        </div>
    </div>
</x-layouts.app>
