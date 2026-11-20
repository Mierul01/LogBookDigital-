<x-layouts.app title="{{ __('Import Users') }}" subtitle="{{ __('Add many students or supervisors at once from a CSV file') }}">
    <x-slot:actions>
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary"><x-icon name="list" class="h-4 w-4" /> <span class="hidden sm:inline">{{ __('All users') }}</span></a>
    </x-slot:actions>

    <div class="grid gap-4 lg:grid-cols-3">
        <form method="POST" action="{{ route('admin.import.store') }}" enctype="multipart/form-data" class="card animate-fade-up lg:col-span-2">
            @csrf
            <div class="card-header">
                <span class="card-icon bg-brand-50 text-brand-600"><x-icon name="document" class="h-5 w-5" /></span>
                <div>
                    <h2 class="font-semibold">{{ __('Upload a CSV file') }}</h2>
                    <p class="text-xs text-zinc-500">{{ __('In Excel: File › Save As › CSV UTF-8 (comma delimited)') }}</p>
                </div>
            </div>
            <div class="p-6">
                <x-field name="file" label="{{ __('CSV file') }}" hint="{{ __('Up to 2,000 rows. Existing usernames are skipped, not changed.') }}">
                    <input id="file" type="file" name="file" accept=".csv,text/csv" required
                           class="block w-full rounded-xl border border-dashed border-zinc-300 bg-zinc-50 px-4 py-6 text-sm text-zinc-600 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:border-brand-400">
                </x-field>
            </div>
            <div class="flex justify-between gap-3 rounded-b-2xl border-t border-zinc-100 bg-zinc-50/60 px-6 py-4">
                <a href="{{ route('admin.import.template') }}" class="btn btn-secondary"><x-icon name="document" class="h-4 w-4" /> {{ __('Download template') }}</a>
                <button type="submit" class="btn btn-primary"><x-icon name="check" class="h-4 w-4" /> {{ __('Import') }}</button>
            </div>
        </form>

        <section class="card p-5 text-sm" data-reveal>
            <h3 class="font-semibold">{{ __('Columns') }}</h3>
            <dl class="mt-3 space-y-2.5 text-zinc-600">
                <div><dt class="font-medium text-zinc-900">username</dt><dd>{{ __('Matric no. for students, staff username for others. Required.') }}</dd></div>
                <div><dt class="font-medium text-zinc-900">name</dt><dd>{{ __('Full name. Required.') }}</dd></div>
                <div><dt class="font-medium text-zinc-900">email</dt><dd>{{ __('Optional, but needed for "Forgot password" and email notices.') }}</dd></div>
                <div><dt class="font-medium text-zinc-900">role</dt><dd>{{ __('student, supervisor or coordinator. Blank means student.') }}</dd></div>
                <div><dt class="font-medium text-zinc-900">supervisor</dt><dd>{{ __('The supervisor\'s username, for students. Import supervisors first.') }}</dd></div>
                <div><dt class="font-medium text-zinc-900">password</dt><dd>{{ __('Optional. If blank, a temporary one is made and shown once below.') }}</dd></div>
            </dl>
        </section>
    </div>

    @if ($results)
        <section class="card mt-4 overflow-hidden" data-reveal>
            <div class="card-header px-5 py-4">
                <span class="card-icon bg-emerald-50 text-emerald-600"><x-icon name="check" class="h-5 w-5" /></span>
                <div>
                    <h2 class="font-semibold">{{ count($results['created']) }} of {{ $results['total'] }} {{ __(Str::plural('row', $results['total'])) }} imported</h2>
                    <p class="text-xs text-zinc-500">{{ __('Temporary passwords are shown only now. Copy them before leaving this page.') }}</p>
                </div>
            </div>

            @if ($results['skipped'])
                <div class="border-b border-zinc-100 bg-red-50/50 px-5 py-3">
                    <p class="text-sm font-semibold text-red-600">{{ count($results['skipped']) }} {{ __(Str::plural('row', count($results['skipped']))) }} skipped</p>
                    <ul class="mt-1.5 max-h-48 space-y-1 overflow-y-auto text-xs text-zinc-600">
                        @foreach ($results['skipped'] as $s)
                            <li><span class="font-semibold">Line {{ $s['line'] }} ({{ $s['username'] }}):</span> {{ $s['reason'] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($results['created'])
                <div class="max-h-96 overflow-auto">
                    <table class="min-w-full text-sm">
                        <thead class="table-head sticky top-0">
                            <tr>
                                <th class="px-5 py-2.5">{{ __('Username') }}</th>
                                <th class="px-5 py-2.5">{{ __('Name') }}</th>
                                <th class="px-5 py-2.5">{{ __('Role') }}</th>
                                <th class="px-5 py-2.5">{{ __('Temporary password') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            @foreach ($results['created'] as $c)
                                <tr class="table-row">
                                    <td class="px-5 py-2 font-medium text-zinc-900">{{ $c['username'] }}</td>
                                    <td class="px-5 py-2 text-zinc-600">{{ $c['name'] }}</td>
                                    <td class="px-5 py-2 text-zinc-600 capitalize">{{ $c['role'] }}</td>
                                    <td class="px-5 py-2 font-mono text-zinc-700">{{ $c['password'] ?? '(from file)' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif
</x-layouts.app>
