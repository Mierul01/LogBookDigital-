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
            <input id="username" name="username" type="text" value="{{ old('username') }}" required autocomplete="username"
                   @class(['input', 'input-error' => $errors->has('username')])>
        </x-field>

        <x-field name="email" label="Email">
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email"
                   @class(['input', 'input-error' => $errors->has('email')])>
        </x-field>

        <div class="grid gap-5 sm:grid-cols-2">
            <x-field name="password" label="Password">
                <input id="password" name="password" type="password" required autocomplete="new-password"
                       @class(['input', 'input-error' => $errors->has('password')])>
            </x-field>
            <x-field name="password_confirmation" label="Confirm">
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="input">
            </x-field>
        </div>

        <button type="submit" class="btn btn-primary w-full">Create account</button>
    </form>

    <p class="mt-8 text-center text-sm text-zinc-500">
        Already registered?
        <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:text-brand-700">Log in</a>
    </p>
</x-layouts.guest>
