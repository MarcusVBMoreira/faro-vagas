@props([
    'label',
    'name',
    'type' => 'text',
    'value' => null,
    'hint' => null,
])

@php
    $id = $attributes->get('id', $name);
    $isPassword = $type === 'password';
    $current = $isPassword ? null : old($name, $value);
    $inputClass = 'block h-11 w-full rounded-md border border-line bg-raised px-3 text-ink placeholder:text-ink-muted/70 focus:border-farol focus:outline-none focus:ring-2 focus:ring-farol/40';
@endphp

<div>
    <label for="{{ $id }}" class="block text-sm font-medium text-ink">{{ $label }}</label>
    <div class="relative mt-1.5">
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="{{ $type }}"
            @if (! is_null($current)) value="{{ $current }}" @endif
            @error($name) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror
            {{ $attributes->except('id')->merge(['class' => $inputClass.($isPassword ? ' pr-12' : '')]) }}
        >
        @if ($isPassword)
            {{-- Mostrar/ocultar a senha digitada --}}
            <button type="button"
                    class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-md text-ink-muted hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-farol/40"
                    aria-controls="{{ $id }}" aria-pressed="false"
                    aria-label="{{ __('Mostrar senha') }}" title="{{ __('Mostrar senha') }}"
                    data-show="{{ __('Mostrar senha') }}" data-hide="{{ __('Ocultar senha') }}"
                    onclick="const i = document.getElementById(this.getAttribute('aria-controls')); const show = i.type === 'password'; i.type = show ? 'text' : 'password'; this.setAttribute('aria-pressed', show); this.setAttribute('aria-label', show ? this.dataset.hide : this.dataset.show); this.title = this.getAttribute('aria-label'); this.querySelector('[data-eye]').classList.toggle('hidden', show); this.querySelector('[data-eye-off]').classList.toggle('hidden', ! show);">
                <svg data-eye class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg data-eye-off class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.6 5.1A10.6 10.6 0 0 1 12 5c6.4 0 10 7 10 7a17.6 17.6 0 0 1-2.7 3.6"/><path d="M6.6 6.6C3.8 8.4 2 12 2 12s3.6 7 10 7a9.7 9.7 0 0 0 5.4-1.6"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/><path d="m2 2 20 20"/></svg>
            </button>
        @endif
    </div>
    @if ($hint)
        <p class="mt-1 text-xs text-ink-muted">{{ $hint }}</p>
    @endif
    @error($name)
        <p id="{{ $id }}-error" class="mt-1 text-sm text-danger">{{ $message }}</p>
    @enderror
</div>
