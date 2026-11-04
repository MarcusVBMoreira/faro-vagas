@props(['dark' => false])

@php
    $locales = \App\Models\User::LOCALES;
    $current = app()->getLocale();
@endphp

<details class="relative" {{ $attributes }}>
    <summary class="flex cursor-pointer list-none items-center gap-1.5 rounded-md px-2.5 py-2 text-sm {{ $dark ? 'text-white/80 hover:text-white' : 'text-ink-muted hover:text-ink' }}"
             aria-label="{{ __('Idioma') }}">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a14 14 0 0 1 0 18a14 14 0 0 1 0-18"/></svg>
        <span>{{ strtoupper(substr($current, 0, 2)) }}</span>
    </summary>
    <ul class="absolute right-0 z-20 mt-1 w-40 overflow-hidden rounded-md border border-line bg-raised py-1 text-sm text-ink shadow-lg">
        @foreach ($locales as $code => $name)
            <li>
                <a href="{{ route('locale', $code) }}" lang="{{ str_replace('_', '-', $code) }}"
                   class="flex items-center justify-between px-3 py-2 hover:bg-sunken {{ $code === $current ? 'font-semibold' : '' }}">
                    {{ $name }}
                    @if ($code === $current)
                        <svg class="h-4 w-4 text-sinal" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                    @endif
                </a>
            </li>
        @endforeach
    </ul>
</details>
