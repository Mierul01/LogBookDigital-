@props(['name', 'label', 'hint' => null, 'bag' => 'default'])

@php($error = $errors->getBag($bag)->first($name))

<div {{ $attributes }}>
    <label for="{{ $name }}" class="label">{{ __($label) }}</label>
    {{ $slot }}
    @if ($error)
        <p class="mt-1.5 text-sm text-red-600">{{ $error }}</p>
    @elseif ($hint)
        <p class="mt-1.5 text-xs text-zinc-500">{{ __($hint) }}</p>
    @endif
</div>
