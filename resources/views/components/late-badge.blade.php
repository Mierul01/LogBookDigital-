{{-- Small "Late" tag for entries first submitted after the week's deadline. --}}
@props(['logbook'])

@if ($logbook->isLate())
    <span class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2 py-0.5 text-[11px] font-semibold text-red-600 ring-1 ring-red-500/20 ring-inset" title="Submitted {{ $logbook->created_at->format('j M Y, g:i a') }}, due {{ $logbook->semester->dueAt($logbook->week_no)->format('j M Y') }}">
        <x-icon name="clock" class="h-3 w-3" /> Late
    </span>
@endif
