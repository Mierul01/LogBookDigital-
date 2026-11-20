<x-layouts.guest title="Log in">
    <h1 class="text-2xl font-bold tracking-tight">Welcome back</h1>
    <p class="mt-1 text-sm text-zinc-500">Sign in to continue your logbook.</p>

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
        @csrf

        <x-field name="username" label="Matric No. / Username">
            <x-icon-input icon="user" name="username" value="{{ old('username') }}" required autofocus autocomplete="username" placeholder="e.g. A192910" />
        </x-field>

        <x-field name="password" label="Password">
            <x-icon-input icon="lock" name="password" type="password" required autocomplete="current-password" placeholder="Your password" />
        </x-field>

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm text-zinc-600">
                <input type="checkbox" name="remember" class="h-4 w-4 rounded border-zinc-300 text-brand-600 focus:ring-brand-500">
                Remember me
            </label>
            <a href="{{ route('password.request') }}" class="text-sm font-semibold text-brand-600 hover:text-brand-700">Forgot password?</a>
        </div>

        <button type="submit" class="btn btn-primary w-full">Log in</button>
    </form>

    <p class="mt-8 text-center text-sm text-zinc-500">
        No account yet? Ask your FYP coordinator to create one for you.
    </p>
</x-layouts.guest>
