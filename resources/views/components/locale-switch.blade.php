{{-- EN / BM switch. Saves the choice in a cookie and returns to the same page. --}}
@php
    $current = app()->getLocale();
@endphp

<div {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center rounded-lg border border-zinc-200 p-0.5 text-xs font-semibold']) }} role="group" aria-label="Language">
    @foreach (['en' => 'EN', 'ms' => 'BM'] as $code => $label)
        <a href="{{ route('locale', $code) }}" hreflang="{{ $code }}" title="{{ $code === 'ms' ? 'Bahasa Melayu' : 'English' }}"
           @class(['rounded-md px-2 py-1 transition', 'bg-brand-600 text-white' => $current === $code, 'text-zinc-500 hover:text-zinc-900' => $current !== $code])>{{ $label }}</a>
    @endforeach
</div>
