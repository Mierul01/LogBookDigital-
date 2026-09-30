@if (session('success'))
    <div class="mb-8 flex animate-fade-up items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800 shadow-sm" role="status">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100">
            <x-icon name="check" class="h-5 w-5 text-emerald-600" />
        </span>
        <p class="font-medium">{{ session('success') }}</p>
    </div>
@endif
