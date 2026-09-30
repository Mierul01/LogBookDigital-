@if (session('success'))
    <div class="mb-6 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
        <x-icon name="check" class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" />
        <p>{{ session('success') }}</p>
    </div>
@endif
