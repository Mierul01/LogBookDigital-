@props(['icon', 'name', 'type' => 'text', 'bag' => 'default'])

@php($password = $type === 'password')

<div class="relative" @if ($password) data-password-toggle @endif>
    <x-icon :name="$icon" class="pointer-events-none absolute top-1/2 left-3 h-5 w-5 -translate-y-1/2 text-zinc-400" />

    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
           {{ $attributes->class(['input pl-10', 'pr-10' => $password, 'input-error' => $errors->getBag($bag)->has($name)]) }}>

    @if ($password)
        <button type="button" data-password-toggle-button aria-label="Show password" aria-pressed="false"
                class="absolute top-1/2 right-2 -translate-y-1/2 rounded-md p-1 text-zinc-400 hover:text-zinc-700 focus:ring-2 focus:ring-brand-500/30 focus:outline-none">
            <x-icon name="eye" class="h-5 w-5" data-icon-show />
            <x-icon name="eye-slash" class="hidden h-5 w-5" data-icon-hide />
        </button>
    @endif
</div>
