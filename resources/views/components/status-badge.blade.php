@props(['logbook'])

@if ($logbook->isReviewed())
    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-600/20 ring-inset">
        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Reviewed
    </span>
@else
    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-amber-600/20 ring-inset">
        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> Pending
    </span>
@endif
