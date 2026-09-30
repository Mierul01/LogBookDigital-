<x-layouts.guest title="Register">
    <h1 class="text-2xl font-bold tracking-tight">Create a supervisor account</h1>
    <p class="mt-1 text-sm text-zinc-500">Students don't register here — their supervisor adds them.</p>

    <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-5">
        @csrf

        <x-field name="name" label="Full name">
            <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name"
                   @class(['input', 'input-error' => $errors->has('name')])>
        </x-field>

        <x-field name="username" label="Username" hint="Used to log in. Letters, numbers, dashes.">
            <x-icon-input icon="user" name="username" value="{{ old('username') }}" required autocomplete="username" />
        </x-field>

        <x-field name="email" label="Email">
            <x-icon-input icon="envelope" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" />
        </x-field>

        <x-field name="password" label="Password" hint="At least 8 characters.">
            <x-icon-input icon="lock" name="password" type="password" required autocomplete="new-password" />
        </x-field>

        <x-field name="password_confirmation" label="Confirm password">
            <x-icon-input icon="lock" name="password_confirmation" type="password" required autocomplete="new-password" />
        </x-field>

        <button type="submit" class="btn btn-primary w-full">Create account</button>
    </form>

    <p class="mt-8 text-center text-sm text-zinc-500">
        Already registered?
        <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:text-brand-700">Log in</a>
    </p>
</x-layouts.guest>
