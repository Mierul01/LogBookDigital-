@php($editing = $semester->exists)

<x-layouts.app :title="$editing ? 'Edit Semester' : 'Add Semester'" subtitle="{{ __('Week 1 starts on the start date; each semester has 14 weeks') }}">
    <x-slot:actions>
        <a href="{{ route('admin.semesters.index') }}" class="btn btn-secondary"><x-icon name="list" class="h-4 w-4" /> <span class="hidden sm:inline">{{ __('All semesters') }}</span></a>
    </x-slot:actions>

    <form method="POST" action="{{ $editing ? route('admin.semesters.update', $semester) : route('admin.semesters.store') }}" class="card max-w-2xl animate-fade-up">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="card-header">
            <span class="card-icon bg-brand-50 text-brand-600"><x-icon name="calendar" class="h-5 w-5" /></span>
            <div>
                <h2 class="font-semibold">{{ __('Semester details') }}</h2>
                <p class="text-xs text-zinc-500">{{ __('Students see the week numbers and due dates from this') }}</p>
            </div>
        </div>

        <div class="grid gap-5 p-6 sm:grid-cols-2">
            <x-field name="name" label="{{ __('Name') }}" class="sm:col-span-2">
                <x-icon-input icon="academic-cap" name="name" value="{{ old('name', $semester->name) }}" required placeholder="{{ __('Semester 1 2026/2027') }}" />
            </x-field>
            <x-field name="starts_on" label="{{ __('Week 1 starts on') }}" hint="{{ __('Usually the Monday of the first lecture week.') }}">
                <x-icon-input icon="calendar" name="starts_on" required data-datepicker value="{{ old('starts_on', $semester->starts_on?->format('Y-m-d')) }}" />
            </x-field>
            <x-field name="due_days" label="{{ __('Days to submit after each week') }}" hint="{{ __('Entries are due this many days after the week\'s Sunday. 3 = Wednesday night.') }}">
                <x-icon-input icon="clock" name="due_days" type="number" min="0" max="14" required value="{{ old('due_days', $semester->due_days ?? 3) }}" />
            </x-field>
            <label class="flex items-center gap-2.5 self-center text-sm text-zinc-700 sm:col-span-2">
                <input type="checkbox" name="is_current" value="1" @checked(old('is_current', $semester->is_current)) class="h-4 w-4 rounded border-zinc-300 text-brand-600 focus:ring-brand-500">
                {{ __('Make this the current semester') }}
            </label>
        </div>

        <div class="flex justify-end gap-3 rounded-b-2xl border-t border-zinc-100 bg-zinc-50/60 px-6 py-4">
            <a href="{{ route('admin.semesters.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
            <button type="submit" class="btn btn-primary"><x-icon name="check" class="h-4 w-4" /> {{ __($editing ? 'Save changes' : 'Add semester') }}</button>
        </div>
    </form>
</x-layouts.app>
