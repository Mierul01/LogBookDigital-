@props(['logbook', 'dark' => false])

@php
    [$label, $light, $darkTone, $dot] = match (true) {
        $logbook->isReviewed() => ['Approved', 'bg-emerald-50 text-emerald-700 ring-emerald-600/20', 'bg-emerald-500/15 text-emerald-300 ring-emerald-400/25', 'bg-emerald-500'],
        $logbook->needsRevision() => ['Needs fixing', 'bg-orange-50 text-orange-700 ring-orange-600/20', 'bg-orange-500/15 text-orange-300 ring-orange-400/25', 'bg-orange-500'],
        default => ['Pending', 'bg-amber-50 text-amber-700 ring-amber-600/20', 'bg-amber-400/15 text-amber-300 ring-amber-400/25', 'bg-amber-500'],
    };
@endphp

<span class="inline-flex items-center gap-1 whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $dark ? $darkTone : $light }}">
    <span class="h-1.5 w-1.5 rounded-full {{ $dot }}"></span> {{ __($label) }}
</span>
