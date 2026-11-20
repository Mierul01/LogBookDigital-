{{--
    Signature box. The drawing is posted as a PNG data URL in the hidden input.
    required-for: only needed when the form is sent with that decision button (e.g. "approve").
    optional: an empty box is allowed (keeps the old signature when editing).
--}}
@props(['name', 'label' => 'Tandatangan (Signature)', 'hint' => 'Sign with your mouse or finger.', 'requiredFor' => null, 'optional' => false, 'emptyMessage' => 'Please sign in the signature box first.'])

<div data-signature-pad data-empty-message="{{ $emptyMessage }}"
     @if ($requiredFor) data-required-for="{{ $requiredFor }}" @endif
     @if ($optional) data-optional @endif {{ $attributes }}>
    <div class="mb-1.5 flex items-center justify-between">
        <span class="label mb-0">{{ $label }}</span>
        <button type="button" data-signature-clear class="rounded-md px-2 py-1 text-xs font-medium text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900">Clear</button>
    </div>
    <canvas class="h-36 w-full cursor-crosshair touch-none rounded-xl border-2 border-dashed border-zinc-300 bg-zinc-50 transition hover:border-brand-400 hover:bg-white"></canvas>
    <input type="hidden" name="{{ $name }}">
    @error($name)
        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
    @else
        <p class="mt-1.5 text-xs text-zinc-500">{{ $hint }}</p>
    @enderror
</div>
