<x-layouts.guest title="Forgot password">
    <h1 class="text-2xl font-bold tracking-tight">Forgot your password?</h1>
    <p class="mt-1 text-sm text-zinc-500">Enter the email on your account and we'll send you a link to set a new one.</p>

    <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5">
        @csrf

        <x-field name="email" label="Email">
            <x-icon-input icon="envelope" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="you@siswa.ukm.edu.my" />
        </x-field>

        <button type="submit" class="btn btn-primary w-full">Send reset link</button>
    </form>

    <p class="mt-6 text-center text-sm text-zinc-500">
        No email on your account? Ask your supervisor or FYP coordinator to reset it.
    </p>
    <p class="mt-3 text-center text-sm">
        <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:text-brand-700">Back to log in</a>
    </p>
</x-layouts.guest>
