@php($editing = $student->exists)

<x-layouts.app :title="$editing ? 'Edit '.$student->name : 'Add student'">
    <x-slot:actions>
        <a href="{{ route('students.index') }}" class="btn btn-secondary"><x-icon name="arrow-left" class="h-4 w-4" /> Back</a>
    </x-slot:actions>

    <form method="POST" action="{{ $editing ? route('students.update', $student) : route('students.store') }}" class="mx-auto max-w-2xl">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="card">
            <div class="border-b border-zinc-200 px-6 py-5">
                <h2 class="font-semibold">{{ $editing ? 'Student details' : 'Register a new student' }}</h2>
                <p class="mt-1 text-sm text-zinc-500">The student logs in with their matric number and this password.</p>
            </div>

            <div class="grid gap-6 p-6 sm:grid-cols-2">
                <x-field name="username" label="Matric no.">
                    <input id="username" name="username" type="text" required value="{{ old('username', $student->username) }}" placeholder="A192910"
                           @class(['input uppercase', 'input-error' => $errors->has('username')])>
                </x-field>

                <x-field name="name" label="Full name">
                    <input id="name" name="name" type="text" required value="{{ old('name', $student->name) }}"
                           @class(['input', 'input-error' => $errors->has('name')])>
                </x-field>

                <x-field name="email" label="Email" hint="Optional.">
                    <input id="email" name="email" type="email" value="{{ old('email', $student->email) }}"
                           @class(['input', 'input-error' => $errors->has('email')])>
                </x-field>

                <x-field name="password" label="Password" :hint="$editing ? 'Leave blank to keep the current password.' : 'At least 6 characters.'">
                    <input id="password" name="password" type="password" autocomplete="new-password" @required(! $editing)
                           @class(['input', 'input-error' => $errors->has('password')])>
                </x-field>
            </div>

            <div class="flex justify-end gap-3 rounded-b-2xl border-t border-zinc-200 bg-zinc-50 px-6 py-4">
                <a href="{{ route('students.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">{{ $editing ? 'Save changes' : 'Register student' }}</button>
            </div>
        </div>
    </form>
</x-layouts.app>
