@php
    $editing = $account->exists;
    $role = old('role', $account->role);
@endphp

<x-layouts.app :title="$editing ? 'Edit User' : 'Add User'" :subtitle="$editing ? $account->name.' · '.$account->username : 'Create a login for a student, supervisor or coordinator'">
    <x-slot:actions>
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary"><x-icon name="list" class="h-4 w-4" /> <span class="hidden sm:inline">All users</span></a>
    </x-slot:actions>

    <form method="POST" action="{{ $editing ? route('admin.users.update', $account) : route('admin.users.store') }}" class="card max-w-3xl animate-fade-up" data-role-form>
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="card-header">
            <span class="card-icon bg-brand-50 text-brand-600"><x-icon name="users" class="h-5 w-5" /></span>
            <div>
                <h2 class="font-semibold">{{ $editing ? 'Account details' : 'New account' }}</h2>
                <p class="text-xs text-zinc-500">The user logs in with the username (matric no. for students) and this password</p>
            </div>
        </div>

        <div class="grid gap-5 p-6 sm:grid-cols-2">
            <x-field name="role" label="Role" class="sm:col-span-2">
                <div class="grid gap-2 sm:grid-cols-3">
                    @foreach (['student' => ['Student', 'academic-cap', 'Writes a weekly logbook'], 'supervisor' => ['Supervisor', 'shield', 'Reviews and signs entries'], 'coordinator' => ['Coordinator', 'key', 'Manages accounts and semesters']] as $value => [$label, $icon, $hint])
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-zinc-200 p-3 transition hover:border-brand-400 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                            <input type="radio" name="role" value="{{ $value }}" @checked($role === $value) class="mt-1 text-brand-600 focus:ring-brand-500" data-role-option>
                            <span>
                                <span class="flex items-center gap-1.5 text-sm font-semibold text-zinc-900"><x-icon :name="$icon" class="h-4 w-4 text-brand-600" /> {{ $label }}</span>
                                <span class="mt-0.5 block text-xs text-zinc-500">{{ $hint }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </x-field>

            <x-field name="username" label="Username / Matric no.">
                <x-icon-input icon="identification" name="username" value="{{ old('username', $account->username) }}" required placeholder="A192910 or dr.aisyah" />
            </x-field>
            <x-field name="name" label="Full name">
                <x-icon-input icon="user" name="name" value="{{ old('name', $account->name) }}" required placeholder="As per staff / student card" />
            </x-field>
            <x-field name="email" label="Email" hint="Needed for password reset and email notifications.">
                <x-icon-input icon="envelope" name="email" type="email" value="{{ old('email', $account->email) }}" placeholder="name@ukm.edu.my" />
            </x-field>
            <x-field name="password" label="Password" :hint="$editing ? 'Leave blank to keep the current password.' : 'At least 8 characters.'">
                <x-icon-input icon="lock" name="password" type="password" autocomplete="new-password" :required="! $editing" />
            </x-field>

            <x-field name="supervisor_id" label="Supervisor" hint="Only for students." data-student-only :class="$role === 'student' ? 'sm:col-span-2' : 'hidden sm:col-span-2'">
                <select id="supervisor_id" name="supervisor_id" data-select @class(['input', 'input-error' => $errors->has('supervisor_id')])>
                    <option value="">Not assigned yet</option>
                    @foreach ($supervisors as $s)
                        <option value="{{ $s->id }}" @selected(old('supervisor_id', $account->supervisor_id) == $s->id)>{{ $s->name }} ({{ $s->username }})</option>
                    @endforeach
                </select>
            </x-field>
        </div>

        <div class="flex justify-end gap-3 rounded-b-2xl border-t border-zinc-100 bg-zinc-50/60 px-6 py-4">
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary"><x-icon name="check" class="h-4 w-4" /> {{ $editing ? 'Save changes' : 'Create account' }}</button>
        </div>
    </form>
</x-layouts.app>
