@php
    $weeks = range(1, App\Models\Logbook::MAX_WEEKS);
    $approved = $logbooks->filter->isReviewed()->count();
    $meetings = $logbooks->filter->hadMeeting()->count();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Logbook · {{ $student->name }} · {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        @page { size: A4; margin: 14mm; }
        @media print {
            body { background: white !important; }
            .sheet { box-shadow: none !important; border: 0 !important; margin: 0 !important; padding: 0 !important; max-width: none !important; }
            .week { break-inside: avoid; }
        }
    </style>
</head>
<body class="bg-zinc-100 font-sans text-zinc-900 antialiased">
    {{-- Toolbar (not printed) --}}
    <div class="sticky top-0 z-10 border-b border-zinc-200 bg-white/90 backdrop-blur print:hidden">
        <div class="mx-auto flex max-w-4xl items-center justify-between gap-3 px-4 py-3">
            <a href="{{ route('logbooks.index', auth()->user()->isSupervisor() ? ['student' => $student->id] : []) }}" class="btn btn-secondary"><x-icon name="arrow-left" class="h-4 w-4" /> Back</a>
            <p class="hidden text-sm text-zinc-500 sm:block">Print or choose “Save as PDF” to keep a copy.</p>
            <button type="button" onclick="window.print()" class="btn btn-primary"><x-icon name="printer" class="h-4 w-4" /> Print / PDF</button>
        </div>
    </div>

    <main class="sheet mx-auto my-6 max-w-4xl rounded-2xl border border-zinc-200 bg-white px-8 py-8 shadow-sm sm:px-10">
        {{-- Cover --}}
        <header class="flex items-start justify-between gap-6 border-b-2 border-brand-600 pb-5">
            <div>
                <img src="{{ asset('images/ukm.png') }}" alt="Universiti Kebangsaan Malaysia" class="h-14 object-contain">
                <h1 class="mt-4 text-xl font-bold tracking-tight text-brand-900">Log Book Projek Tahun Akhir</h1>
                <p class="text-sm text-zinc-500">Final Year Project Weekly Logbook</p>
            </div>
            <div class="shrink-0 rounded-xl bg-brand-50 px-4 py-3 text-right">
                <p class="text-2xl font-bold text-brand-700">{{ $approved }}<span class="text-base text-brand-400">/{{ count($weeks) }}</span></p>
                <p class="text-xs text-brand-700/80">weeks approved</p>
                <p class="mt-1 text-xs text-brand-700/80">{{ $meetings }} {{ Str::plural('meeting', $meetings) }} held</p>
            </div>
        </header>

        <dl class="grid grid-cols-2 gap-x-6 gap-y-3 border-b border-zinc-200 py-5 text-sm sm:grid-cols-4">
            <div><dt class="text-xs text-zinc-500">Nama Pelajar</dt><dd class="font-semibold">{{ $student->name }}</dd></div>
            <div><dt class="text-xs text-zinc-500">No. Matrik</dt><dd class="font-semibold">{{ $student->username }}</dd></div>
            <div><dt class="text-xs text-zinc-500">Penyelia</dt><dd class="font-semibold">{{ $student->supervisor?->name ?? '—' }}</dd></div>
            <div><dt class="text-xs text-zinc-500">Dicetak</dt><dd class="font-semibold">{{ now()->format('j M Y') }}</dd></div>
            <div class="col-span-2 sm:col-span-3"><dt class="text-xs text-zinc-500">Tajuk Projek</dt><dd class="font-semibold">{{ $project?->title ?? '—' }}</dd></div>
            <div><dt class="text-xs text-zinc-500">Semester · Peringkat</dt><dd class="font-semibold">{{ $semester?->name ?? '—' }}{{ $project ? ' · '.$project->stageLabel() : '' }}</dd></div>
        </dl>

        {{-- Weeks --}}
        <div class="divide-y divide-zinc-200">
            @foreach ($weeks as $week)
                @php($entry = $logbooks->get($week))
                <section class="week py-5">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="flex items-center gap-2.5 font-bold">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-600 text-sm text-white">{{ $week }}</span>
                            Minggu {{ $week }}
                            @if ($entry)
                                <span class="text-sm font-normal text-zinc-500">· {{ $entry->entry_date->format('j M Y') }}</span>
                            @endif
                        </h2>
                        @if ($entry)
                            <span class="flex items-center gap-1.5"><x-late-badge :logbook="$entry" /> <x-status-badge :logbook="$entry" /></span>
                        @endif
                    </div>
                    @if ($entry)
                        <p class="mt-1.5 text-xs text-zinc-500">Perjumpaan: {{ $entry->meetingSummary() }}</p>
                    @endif

                    @if ($entry)
                        <dl class="mt-3 grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                            @foreach ([
                                'Kemajuan / Perkara Dibincang' => $entry->progress,
                                'Status Semasa' => $entry->current_status,
                                'Masalah' => $entry->problem,
                                'Tugasan Minggu Hadapan' => $entry->next_week_task,
                            ] as $label => $value)
                                <div>
                                    <dt class="text-xs font-semibold text-zinc-500">{{ __($label) }}</dt>
                                    <dd class="mt-0.5 leading-relaxed whitespace-pre-line text-zinc-800">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>

                        @if ($entry->attachments->isNotEmpty())
                            <p class="mt-2 text-xs text-zinc-500"><span class="font-semibold">Lampiran:</span> {{ $entry->attachments->pluck('original_name')->implode(', ') }}</p>
                        @endif

                        @if ($entry->supervisor_comment)
                            <div class="mt-3 rounded-lg bg-zinc-50 px-3 py-2 text-sm">
                                <span class="text-xs font-semibold text-zinc-500">Komen Penyelia:</span>
                                <span class="whitespace-pre-line text-zinc-800">{{ $entry->supervisor_comment }}</span>
                            </div>
                        @endif

                        <div class="mt-3 grid grid-cols-2 gap-6 text-xs text-zinc-500">
                            <div>
                                @if ($entry->student_signature)
                                    <img src="{{ $entry->student_signature }}" alt="Student signature" class="h-12 w-40 object-contain object-left">
                                @else
                                    <div class="h-12"></div>
                                @endif
                                <p class="border-t border-dashed border-zinc-300 pt-1">Tandatangan Pelajar</p>
                            </div>
                            <div>
                                @if ($entry->isReviewed())
                                    <img src="{{ $entry->supervisor_signature }}" alt="Supervisor signature" class="h-12 w-40 object-contain object-left">
                                @else
                                    <div class="h-12"></div>
                                @endif
                                <p class="border-t border-dashed border-zinc-300 pt-1">Tandatangan Penyelia @if ($entry->isReviewed()) · {{ $entry->reviewed_at->format('j M Y') }} @endif</p>
                            </div>
                        </div>
                    @else
                        <p class="mt-2 text-sm text-zinc-400">No entry for this week.@if ($semester && $semester->dueAt($week)->isPast()) <span class="font-semibold text-red-600">Overdue (due {{ $semester->dueAt($week)->format('j M Y') }}).</span>@endif</p>
                    @endif
                </section>
            @endforeach
        </div>
    </main>
</body>
</html>
