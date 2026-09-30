@props(['icon', 'label', 'value', 'suffix' => null, 'tone' => 'zinc', 'hint' => null, 'href' => null])

@php
    $tones = [
        'zinc' => 'bg-zinc-100 text-zinc-700',
        'brand' => 'bg-brand-50 text-brand-600',
        'emerald' => 'bg-emerald-50 text-emerald-600',
        'amber' => 'bg-amber-50 text-amber-600',
        'sky' => 'bg-sky-50 text-sky-600',
    ];
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif data-reveal {{ $attributes->class(['card card-hover group block p-6']) }}>
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <p class="text-sm font-medium text-zinc-500">{{ $label }}</p>
            <p class="mt-3 text-3xl font-bold tracking-tight text-zinc-900">
                @if (is_numeric($value))
                    <span data-count="{{ $value }}">0</span>
                @else
                    {{ $value }}
                @endif
                @if ($suffix)<span class="text-base font-medium text-zinc-400">{{ $suffix }}</span>@endif
            </p>
        </div>
        <span class="card-icon {{ $tones[$tone] }} transition duration-300 group-hover:scale-110 group-hover:rotate-6">
            <x-icon :name="$icon" class="h-5 w-5" />
        </span>
    </div>
    @if ($hint || $slot->isNotEmpty())
        <div class="mt-4 text-xs text-zinc-500">
            {{ $slot->isNotEmpty() ? $slot : $hint }}
        </div>
    @endif
</{{ $tag }}>
