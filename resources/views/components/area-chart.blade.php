{{--
    Area chart with up to two series. Lines are SVG; dots, labels and tooltips are HTML so
    they keep their shape while the chart stretches to the card width.
    series: [['label' => 'Submitted', 'values' => [...], 'color' => '#hex', 'area' => true], ...]
--}}
@props(['series', 'labels'])

@php
    $w = 1000;
    $h = 300;
    $max = App\Support\Chart::niceMax(max(array_merge([0], ...array_column($series, 'values'))));
    $count = count($labels);
    $xAt = fn (int $i) => $count > 1 ? $i / ($count - 1) * 100 : 50;
@endphp

<div {{ $attributes->merge(['class' => 'flex h-[210px] gap-2']) }}>
    {{-- Y axis --}}
    <div class="relative mb-6 w-6 shrink-0">
        @foreach ([1, 0.75, 0.5, 0.25, 0] as $step)
            <span class="absolute right-0 -translate-y-1/2 text-[11px] text-zinc-500" style="top: {{ (1 - $step) * 100 }}%">{{ round($max * $step) }}</span>
        @endforeach
    </div>

    <div class="flex min-w-0 flex-1 flex-col">
        <div class="relative flex-1">
            @foreach ([0, 25, 50, 75, 100] as $line)
                <div class="absolute inset-x-0 border-t border-dashed border-zinc-200" style="top: {{ $line }}%"></div>
            @endforeach

            <svg viewBox="0 0 {{ $w }} {{ $h }}" preserveAspectRatio="none" class="absolute inset-0 h-full w-full overflow-visible" aria-hidden="true">
                <defs>
                    @foreach ($series as $i => $s)
                        <linearGradient id="area-{{ $i }}" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0" stop-color="{{ $s['color'] }}" stop-opacity="0.45" />
                            <stop offset="1" stop-color="{{ $s['color'] }}" stop-opacity="0" />
                        </linearGradient>
                    @endforeach
                </defs>
                @foreach ($series as $i => $s)
                    @php($line = App\Support\Chart::smoothPath(App\Support\Chart::points($s['values'], $w, $h, $max), $h))
                    @if ($s['area'] ?? false)
                        <path d="{{ $line }} L{{ $w }},{{ $h }} L0,{{ $h }} Z" fill="url(#area-{{ $i }})" />
                    @endif
                    <path d="{{ $line }}" fill="none" stroke="{{ $s['color'] }}" stroke-width="{{ ($s['area'] ?? false) ? 2.5 : 2 }}"
                          @unless ($s['area'] ?? false) stroke-dasharray="6 5" @endunless vector-effect="non-scaling-stroke" stroke-linecap="round" />
                @endforeach
            </svg>

            {{-- One hover column per label, with dots and a tooltip --}}
            @foreach ($labels as $i => $label)
                <div class="group absolute inset-y-0 w-8 -translate-x-1/2" style="left: {{ $xAt($i) }}%">
                    <div class="absolute inset-y-0 left-1/2 border-l border-transparent transition group-hover:border-zinc-300"></div>
                    @foreach ($series as $s)
                        <span class="absolute left-1/2 h-2.5 w-2.5 -translate-x-1/2 -translate-y-1/2 rounded-full ring-2 ring-white transition group-hover:scale-150"
                              style="top: {{ 100 - $s['values'][$i] / $max * 100 }}%; background: {{ $s['color'] }}"></span>
                    @endforeach
                    @php($topY = 100 - max(array_map(fn ($s) => $s['values'][$i], $series)) / $max * 100)
                    <div class="chart-tip left-1/2 group-hover:opacity-100" style="top: calc({{ $topY }}% - 10px)">
                        <p class="font-semibold">{{ $label }}</p>
                        @foreach ($series as $s)
                            <p class="flex items-center gap-1.5 text-slate-300"><span class="h-2 w-2 rounded-full" style="background: {{ $s['color'] }}"></span>{{ __($s['label']) }}: <span class="font-semibold text-white">{{ $s['values'][$i] }}</span></p>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        {{-- X axis --}}
        <div class="relative mt-1.5 h-4.5">
            @foreach ($labels as $i => $label)
                <span class="absolute -translate-x-1/2 text-[11px] whitespace-nowrap text-zinc-500" style="left: {{ $xAt($i) }}%">{{ $label }}</span>
            @endforeach
        </div>
    </div>
</div>
