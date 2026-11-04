@props([
    'label',
    'name',
    'type' => 'text',
    'value' => null,
    'hint' => null,
])

@php
    $id = $attributes->get('id', $name);
    $current = $type === 'password' ? null : old($name, $value);
@endphp

<div>
    <label for="{{ $id }}" class="block text-sm font-medium text-ink">{{ $label }}</label>
    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if (! is_null($current)) value="{{ $current }}" @endif
        @error($name) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror
        {{ $attributes->except('id')->merge(['class' => 'mt-1.5 block h-11 w-full rounded-md border border-line bg-raised px-3 text-ink placeholder:text-ink-muted/70 focus:border-farol focus:outline-none focus:ring-2 focus:ring-farol/40']) }}
    >
    @if ($hint)
        <p class="mt-1 text-xs text-ink-muted">{{ $hint }}</p>
    @endif
    @error($name)
        <p id="{{ $id }}-error" class="mt-1 text-sm text-danger">{{ $message }}</p>
    @enderror
</div>
