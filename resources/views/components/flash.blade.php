{{-- Messages for the SweetAlert popups in resources/js/app.js. --}}
@php
    $flash = array_filter([
        'title' => session('success_title'),
        'success' => session('success'),
        'toast' => session('toast'),
        'errors' => $errors->any() ? $errors->count() : null,
        'error' => $errors->count() === 1 ? $errors->first() : null,
    ]);
@endphp

@if ($flash)
    <script type="application/json" id="flash-data">@json($flash)</script>
@endif

@if (session('success'))
    <noscript>
        <div class="mb-8 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
    </noscript>
@endif
