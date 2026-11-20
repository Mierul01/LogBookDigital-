{{-- Tiny trend line for a stat tile. --}}
@props(['values', 'color' => '#5583c9'])

@php
    $w = 200;
    $h = 48;
    // Headroom above the highest value so a flat series reads as a line, not a filled block.
    $points = App\Support\Chart::points($values, $w, $h, max($values ?: [0]) * 1.35, 6, 3);
    $line = App\Support\Chart::smoothPath($points, $h - 3);
    $id = 'spark-'.Illuminate\Support\Str::random(8);
@endphp

<svg viewBox="0 0 {{ $w }} {{ $h }}" preserveAspectRatio="none" {{ $attributes->merge(['class' => 'h-12 w-full']) }} aria-hidden="true">
    <defs>
        <linearGradient id="{{ $id }}" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="{{ $color }}" stop-opacity="0.45" />
            <stop offset="1" stop-color="{{ $color }}" stop-opacity="0" />
        </linearGradient>
    </defs>
    @if ($line)
        <path d="{{ $line }} L{{ $w }},{{ $h }} L0,{{ $h }} Z" fill="url(#{{ $id }})" />
        <path d="{{ $line }}" fill="none" stroke="{{ $color }}" stroke-width="2" stroke-linecap="round" vector-effect="non-scaling-stroke" />
    @endif
</svg>
