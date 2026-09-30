@props(['icon', 'name', 'type' => 'text', 'bag' => 'default'])

@php($password = $type === 'password')

<div class="input-icon-wrap relative" @if ($password) data-password-toggle @endif>
    <x-icon :name="$icon" class="input-icon pointer-events-none absolute top-1/2 left-3.5 z-10 h-5 w-5 -translate-y-1/2 text-zinc-400 transition-colors duration-200" />

    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
           {{ $attributes->class(['input pl-11', 'pr-11' => $password, 'input-error' => $errors->getBag($bag)->has($name)]) }}>

    @if ($password)
        <button type="button" data-password-toggle-button aria-label="Show password" aria-pressed="false"
                class="absolute top-1/2 right-2.5 -translate-y-1/2 rounded-lg p-1.5 text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-700 focus:ring-2 focus:ring-brand-500/30 focus:outline-none">
            <x-icon name="eye" class="h-5 w-5" data-icon-show />
            <x-icon name="eye-slash" class="hidden h-5 w-5" data-icon-hide />
        </button>
    @endif
</div>
