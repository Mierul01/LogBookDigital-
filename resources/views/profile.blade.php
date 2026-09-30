<x-layouts.app title="Profile">
    <div class="mx-auto grid max-w-5xl gap-6 lg:grid-cols-3">
        <div class="card h-fit p-6 text-center">
            <span class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-brand-600 text-2xl font-bold text-white">{{ $user->initials() }}</span>
            <h2 class="mt-4 text-lg font-semibold">{{ $user->name }}</h2>
            <p class="text-sm text-zinc-500">{{ ucfirst($user->role) }} · {{ $user->username }}</p>
            <p class="mt-3 text-xs font-medium tracking-wide text-zinc-400 uppercase">Universiti Kebangsaan Malaysia</p>
            @if ($user->isStudent())
                <div class="mt-5 rounded-lg bg-zinc-50 px-4 py-3 text-left">
                    <p class="text-xs text-zinc-500">Supervisor</p>
                    <p class="text-sm font-medium">{{ $user->supervisor?->name ?? 'Not assigned' }}</p>
                </div>
            @endif
        </div>

        <div class="space-y-6 lg:col-span-2">
            <form method="POST" action="{{ route('profile.update') }}" class="card">
                @csrf @method('PATCH')
                <div class="border-b border-zinc-200 px-6 py-4"><h3 class="font-semibold">Personal information</h3></div>
                <div class="grid gap-6 p-6 sm:grid-cols-2">
                    <x-field name="name" label="Full name" class="sm:col-span-2">
                        <input id="name" name="name" type="text" required value="{{ old('name', $user->name) }}" @class(['input', 'input-error' => $errors->has('name')])>
                    </x-field>
                    <x-field name="username" label="{{ $user->isStudent() ? 'Matric no.' : 'Username' }}" hint="Contact your supervisor to change this.">
                        <input id="username" type="text" value="{{ $user->username }}" disabled class="input">
                    </x-field>
                    <x-field name="email" label="Email">
                        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" @class(['input', 'input-error' => $errors->has('email')])>
                    </x-field>
                </div>
                <div class="flex justify-end rounded-b-2xl border-t border-zinc-200 bg-zinc-50 px-6 py-4">
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>

            <form method="POST" action="{{ route('profile.password') }}" class="card">
                @csrf @method('PUT')
                <div class="border-b border-zinc-200 px-6 py-4"><h3 class="font-semibold">Change password</h3></div>
                <div class="grid gap-6 p-6 sm:grid-cols-3">
                    <x-field name="current_password" label="Current" bag="password">
                        <input id="current_password" name="current_password" type="password" required autocomplete="current-password"
                               @class(['input', 'input-error' => $errors->password->has('current_password')])>
                    </x-field>
                    <x-field name="password" label="New" bag="password">
                        <input id="password" name="password" type="password" required autocomplete="new-password"
                               @class(['input', 'input-error' => $errors->password->has('password')])>
                    </x-field>
                    <x-field name="password_confirmation" label="Confirm new" bag="password">
                        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="input">
                    </x-field>
                </div>
                <div class="flex justify-end rounded-b-2xl border-t border-zinc-200 bg-zinc-50 px-6 py-4">
                    <button type="submit" class="btn btn-primary">Update password</button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
