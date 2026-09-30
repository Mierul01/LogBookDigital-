<x-layouts.guest title="Log in">
    <h1 class="text-2xl font-bold tracking-tight">Welcome back</h1>
    <p class="mt-1 text-sm text-zinc-500">Sign in with your matric number or supervisor username.</p>

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
        @csrf

        <x-field name="username" label="Matric No. / Username">
            <x-icon-input icon="user" name="username" value="{{ old('username') }}" required autofocus autocomplete="username" placeholder="e.g. A192910" />
        </x-field>

        <x-field name="password" label="Password">
            <x-icon-input icon="lock" name="password" type="password" required autocomplete="current-password" placeholder="Your password" />
        </x-field>

        <label class="flex items-center gap-2 text-sm text-zinc-600">
            <input type="checkbox" name="remember" class="h-4 w-4 rounded border-zinc-300 text-brand-600 focus:ring-brand-500">
            Remember me
        </label>

        <button type="submit" class="btn btn-primary w-full">Log in</button>
    </form>

    {{-- Seeded demo accounts (see DatabaseSeeder). Only shown when APP_ENV=local. --}}
    @if (app()->isLocal())
        <div class="mt-8 rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-4">
            <p class="text-xs font-semibold tracking-wide text-zinc-500 uppercase">Demo accounts · click to fill</p>
            <ul class="mt-3 space-y-2">
                @foreach ([
                    ['Supervisor', 'Dr. Aisyah Rahman', 'dr.aisyah'],
                    ['Student', 'Muhamad Amirul Aiman', 'A192910'],
                    ['Student', 'Nurul Huda Ismail', 'A192911'],
                ] as [$role, $name, $username])
                    <li>
                        <button type="button" data-demo-login data-username="{{ $username }}" data-password="password"
                                class="flex w-full items-center justify-between gap-3 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-left text-sm hover:border-brand-500">
                            <span>
                                <span class="block font-medium text-zinc-900">{{ $name }}</span>
                                <span class="block text-xs text-zinc-500">{{ $role }}</span>
                            </span>
                            <span class="font-mono text-xs text-zinc-600">{{ $username }} / password</span>
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <p class="mt-8 text-center text-sm text-zinc-500">
        Supervisor without an account?
        <a href="{{ route('register') }}" class="font-semibold text-brand-600 hover:text-brand-700">Register here</a>
    </p>
</x-layouts.guest>
