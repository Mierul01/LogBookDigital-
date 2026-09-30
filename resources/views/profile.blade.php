<x-layouts.app title="My Profile" subtitle="Manage your personal details and account security">
    {{-- Profile header --}}
    <section class="card animate-fade-up overflow-hidden">
        <div class="relative h-36 overflow-hidden bg-zinc-950 sm:h-44">
            <img src="{{ asset('images/UKMBG.jpg') }}" alt="" class="absolute inset-0 h-full w-full scale-105 object-cover opacity-40">
            <div class="absolute inset-0 bg-gradient-to-r from-zinc-950 via-zinc-950/70 to-brand-900/60"></div>
            <div class="bg-grid absolute inset-0 [mask-image:linear-gradient(to_left,black,transparent)]"></div>
        </div>

        <div class="relative px-6 pb-6 sm:px-8">
            <div class="-mt-14 flex flex-col gap-5 sm:-mt-12 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                    <span class="flex h-28 w-28 animate-scale-in items-center justify-center rounded-3xl bg-gradient-to-br from-brand-500 to-brand-800 text-4xl font-bold text-white shadow-xl ring-4 ring-white sm:h-32 sm:w-32">
                        {{ $user->initials() }}
                    </span>
                    <div class="pb-1">
                        <h2 class="text-2xl font-bold tracking-tight">{{ $user->name }}</h2>
                        <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1 font-medium text-brand-700 ring-1 ring-brand-600/15">
                                <x-icon :name="$user->isStudent() ? 'academic-cap' : 'identification'" class="h-4 w-4" /> {{ ucfirst($user->role) }}
                            </span>
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-3 py-1 font-medium text-zinc-700">
                                <x-icon name="identification" class="h-4 w-4 text-zinc-500" /> {{ $user->username }}
                            </span>
                            <span class="inline-flex items-center gap-1.5 text-zinc-500">
                                <x-icon name="building" class="h-4 w-4" /> Universiti Kebangsaan Malaysia
                            </span>
                        </div>
                    </div>
                </div>
                <a href="{{ route('logbooks.index') }}" class="btn btn-secondary self-start sm:self-auto">
                    <x-icon name="book" class="h-4 w-4" /> {{ $user->isStudent() ? 'My logbook' : 'Student logbooks' }}
                </a>
            </div>

            {{-- Quick stats --}}
            <dl class="mt-8 grid grid-cols-2 gap-4 border-t border-zinc-100 pt-6 lg:grid-cols-4">
                @php
                    $quick = $user->isStudent()
                        ? [
                            ['Weeks logged', $stats['total'].' / '.$stats['weeks'], 'calendar'],
                            ['Reviewed', $stats['reviewed'], 'shield'],
                            ['Awaiting review', $stats['total'] - $stats['reviewed'], 'clock'],
                            ['Last activity', $stats['last_entry']?->diffForHumans() ?? '—', 'sparkles'],
                        ]
                        : [
                            ['Students', $stats['students'], 'users'],
                            ['Entries received', $stats['total'], 'document'],
                            ['Signed', $stats['reviewed'], 'shield'],
                            ['Last activity', $stats['last_entry']?->diffForHumans() ?? '—', 'sparkles'],
                        ];
                @endphp
                @foreach ($quick as [$label, $value, $icon])
                    <div class="group flex items-center gap-3 rounded-2xl p-2 transition hover:bg-zinc-50">
                        <span class="card-icon bg-zinc-100 text-zinc-600 transition duration-300 group-hover:bg-brand-600 group-hover:text-white">
                            <x-icon :name="$icon" class="h-5 w-5" />
                        </span>
                        <div class="min-w-0">
                            <dt class="truncate text-xs text-zinc-500">{{ $label }}</dt>
                            <dd class="truncate text-base font-bold text-zinc-900">{{ $value }}</dd>
                        </div>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    <div class="mt-8 grid gap-8 lg:grid-cols-3">
        {{-- Left: account details --}}
        <div class="space-y-8" data-reveal-group>
            <section class="card" data-reveal>
                <div class="card-header">
                    <span class="card-icon bg-sky-50 text-sky-600"><x-icon name="identification" class="h-5 w-5" /></span>
                    <div>
                        <h3 class="font-semibold">Account details</h3>
                        <p class="text-xs text-zinc-500">Read-only information</p>
                    </div>
                </div>
                <dl class="divide-y divide-zinc-100 px-6">
                    @foreach (array_filter([
                        [$user->isStudent() ? 'Matric no.' : 'Username', $user->username],
                        ['Role', ucfirst($user->role)],
                        $user->isStudent() ? ['Supervisor', $user->supervisor?->name ?? 'Not assigned'] : null,
                        ['Email', $user->email ?? '—'],
                        ['Member since', $user->created_at->format('j F Y')],
                    ]) as [$label, $value])
                        <div class="flex items-center justify-between gap-4 py-3.5 text-sm">
                            <dt class="text-zinc-500">{{ $label }}</dt>
                            <dd class="truncate text-right font-medium text-zinc-900">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            <section class="card overflow-hidden" data-reveal>
                <div class="flex gap-4 bg-gradient-to-br from-amber-50 to-white p-6">
                    <span class="card-icon bg-amber-100 text-amber-600"><x-icon name="key" class="h-5 w-5" /></span>
                    <div>
                        <h3 class="font-semibold">Keep your account safe</h3>
                        <ul class="mt-2 space-y-1.5 text-sm text-zinc-600">
                            <li class="flex gap-2"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" /> Use at least 8 characters</li>
                            <li class="flex gap-2"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" /> Mix letters, numbers and symbols</li>
                            <li class="flex gap-2"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" /> Don't reuse your UKM email password</li>
                        </ul>
                    </div>
                </div>
            </section>
        </div>

        {{-- Right: forms --}}
        <div class="space-y-8 lg:col-span-2" data-reveal-group>
            <form method="POST" action="{{ route('profile.update') }}" class="card" data-reveal>
                @csrf @method('PATCH')
                <div class="card-header">
                    <span class="card-icon bg-brand-50 text-brand-600"><x-icon name="user" class="h-5 w-5" /></span>
                    <div>
                        <h3 class="font-semibold">Personal information</h3>
                        <p class="text-xs text-zinc-500">Update the name and email shown to your {{ $user->isStudent() ? 'supervisor' : 'students' }}</p>
                    </div>
                </div>
                <div class="grid gap-6 p-6 sm:grid-cols-2">
                    <x-field name="name" label="Full name" class="sm:col-span-2">
                        <x-icon-input icon="user" name="name" value="{{ old('name', $user->name) }}" required autocomplete="name" />
                    </x-field>
                    <x-field name="username" :label="$user->isStudent() ? 'Matric no.' : 'Username'" :hint="$user->isStudent() ? 'Contact your supervisor to change this.' : 'Your username cannot be changed.'">
                        <x-icon-input icon="identification" name="username" value="{{ $user->username }}" disabled />
                    </x-field>
                    <x-field name="email" label="Email address">
                        <x-icon-input icon="envelope" name="email" type="email" value="{{ old('email', $user->email) }}" autocomplete="email" placeholder="you@siswa.ukm.edu.my" />
                    </x-field>
                </div>
                <div class="flex items-center justify-end gap-3 rounded-b-2xl border-t border-zinc-100 bg-zinc-50/60 px-6 py-4">
                    <button type="reset" class="btn btn-secondary">Reset</button>
                    <button type="submit" class="btn btn-primary"><x-icon name="check" class="h-4 w-4" /> Save changes</button>
                </div>
            </form>

            <form method="POST" action="{{ route('profile.password') }}" class="card" data-reveal>
                @csrf @method('PUT')
                <div class="card-header">
                    <span class="card-icon bg-zinc-100 text-zinc-700"><x-icon name="lock" class="h-5 w-5" /></span>
                    <div>
                        <h3 class="font-semibold">Change password</h3>
                        <p class="text-xs text-zinc-500">You'll stay signed in on this device</p>
                    </div>
                </div>
                <div class="grid gap-6 p-6 sm:grid-cols-2">
                    <x-field name="current_password" label="Current password" bag="password" class="sm:col-span-2">
                        <x-icon-input icon="lock" name="current_password" type="password" bag="password" required autocomplete="current-password" />
                    </x-field>
                    <x-field name="password" label="New password" bag="password" hint="At least 6 characters.">
                        <x-icon-input icon="key" name="password" type="password" bag="password" required autocomplete="new-password" />
                    </x-field>
                    <x-field name="password_confirmation" label="Confirm new password" bag="password">
                        <x-icon-input icon="key" name="password_confirmation" type="password" bag="password" required autocomplete="new-password" />
                    </x-field>
                </div>
                <div class="flex justify-end rounded-b-2xl border-t border-zinc-100 bg-zinc-50/60 px-6 py-4">
                    <button type="submit" class="btn btn-primary"><x-icon name="shield" class="h-4 w-4" /> Update password</button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
