@php($editing = $student->exists)

<x-layouts.app :title="$editing ? 'Edit Student' : 'Add Student'"
               :subtitle="$editing ? $student->name.' · '.$student->username : 'Create a login for a student you supervise'">
    <x-slot:actions>
        <a href="{{ route('students.index') }}" class="btn btn-secondary"><x-icon name="arrow-left" class="h-4 w-4" /> <span class="hidden sm:inline">Back</span></a>
    </x-slot:actions>

    <div class="grid gap-8 lg:grid-cols-3">
        <form method="POST" action="{{ $editing ? route('students.update', $student) : route('students.store') }}" class="card animate-fade-up lg:col-span-2">
            @csrf
            @if ($editing) @method('PUT') @endif

            <div class="card-header">
                <span class="card-icon bg-brand-50 text-brand-600"><x-icon name="academic-cap" class="h-5 w-5" /></span>
                <div>
                    <h2 class="font-semibold">{{ $editing ? 'Student details' : 'New student' }}</h2>
                    <p class="text-xs text-zinc-500">The student logs in with their matric number and this password</p>
                </div>
            </div>

            <div class="grid gap-6 p-6 sm:grid-cols-2">
                <x-field name="username" label="Matric no.">
                    <x-icon-input icon="identification" name="username" value="{{ old('username', $student->username) }}" required placeholder="A192910" class="uppercase" />
                </x-field>
                <x-field name="name" label="Full name">
                    <x-icon-input icon="user" name="name" value="{{ old('name', $student->name) }}" required placeholder="As per student card" />
                </x-field>
                <x-field name="email" label="Email" hint="Optional.">
                    <x-icon-input icon="envelope" name="email" type="email" value="{{ old('email', $student->email) }}" placeholder="a192910@siswa.ukm.edu.my" />
                </x-field>
                <x-field name="password" label="Password" :hint="$editing ? 'Leave blank to keep the current password.' : 'At least 6 characters.'">
                    <x-icon-input icon="lock" name="password" type="password" autocomplete="new-password" :required="! $editing" />
                </x-field>
            </div>

            <div class="flex justify-end gap-3 rounded-b-2xl border-t border-zinc-100 bg-zinc-50/60 px-6 py-4">
                <a href="{{ route('students.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><x-icon name="check" class="h-4 w-4" /> {{ $editing ? 'Save changes' : 'Register student' }}</button>
            </div>
        </form>

        <aside class="space-y-8" data-reveal-group>
            <section class="card overflow-hidden" data-reveal>
                <div class="bg-gradient-to-br from-sky-50 to-white p-6">
                    <div class="flex items-center gap-2 text-sky-700">
                        <x-icon name="sparkles" class="h-5 w-5" />
                        <h3 class="font-semibold">What happens next</h3>
                    </div>
                    <ol class="mt-4 space-y-4 text-sm text-zinc-600">
                        @foreach (['Share the matric number and password with the student.', 'They log in and write one entry per week.', 'You review, comment and sign each entry.'] as $i => $step)
                            <li class="flex gap-3">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white text-xs font-bold text-sky-700 ring-1 ring-sky-200">{{ $i + 1 }}</span>
                                {{ $step }}
                            </li>
                        @endforeach
                    </ol>
                </div>
            </section>
            @if ($editing)
                <section class="flex gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800" data-reveal>
                    <x-icon name="warning" class="h-5 w-5 shrink-0 text-amber-500" />
                    <p>Changing the matric number changes what the student types to log in. Let them know.</p>
                </section>
            @endif
        </aside>
    </div>
</x-layouts.app>
