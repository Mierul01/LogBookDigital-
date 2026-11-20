@props(['logbook'])

@php
    [$label, $tone, $dot] = match (true) {
        $logbook->isReviewed() => ['Approved', 'bg-emerald-50 text-emerald-700 ring-emerald-600/20', 'bg-emerald-500'],
        $logbook->needsRevision() => ['Needs fixing', 'bg-orange-50 text-orange-700 ring-orange-600/20', 'bg-orange-500'],
        default => ['Pending', 'bg-amber-50 text-amber-700 ring-amber-600/20', 'bg-amber-500'],
    };
@endphp

<span class="inline-flex items-center gap-1 whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $tone }}">
    <span class="h-1.5 w-1.5 rounded-full {{ $dot }}"></span> {{ $label }}
</span>
