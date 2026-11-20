<x-layouts.guest title="Set a new password">
    <h1 class="text-2xl font-bold tracking-tight">Set a new password</h1>
    <p class="mt-1 text-sm text-zinc-500">Use at least 8 characters.</p>

    <form method="POST" action="{{ route('password.update') }}" class="mt-8 space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <x-field name="email" label="Email">
            <x-icon-input icon="envelope" name="email" type="email" value="{{ old('email', $email) }}" required autocomplete="email" />
        </x-field>

        <x-field name="password" label="New password">
            <x-icon-input icon="lock" name="password" type="password" required autofocus autocomplete="new-password" />
        </x-field>

        <x-field name="password_confirmation" label="Confirm new password">
            <x-icon-input icon="lock" name="password_confirmation" type="password" required autocomplete="new-password" />
        </x-field>

        <button type="submit" class="btn btn-primary w-full">Save new password</button>
    </form>
</x-layouts.guest>
