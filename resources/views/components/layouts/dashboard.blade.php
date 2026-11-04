@props(['title'])

<x-layouts.app :title="$title">
    <header class="sticky top-0 z-30 bg-noite/95 text-white shadow-md shadow-black/10 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-3">
            <a href="{{ route('profile.show') }}" aria-label="Faro">
                <img src="{{ asset('images/faro-logo-dark.svg') }}" alt="Faro" class="h-8 w-auto">
            </a>
            <nav class="flex items-center gap-1 text-sm">
                <a href="{{ route('profile.show') }}"
                   class="hidden rounded-md px-3 py-2 sm:inline-block {{ request()->routeIs('profile.*') ? 'bg-noite-2 text-white' : 'text-white/75 hover:text-white' }}">
                    {{ __('Meu perfil') }}
                </a>
                <span class="hidden px-3 py-2 text-white/40 md:inline" title="{{ __('Em breve') }}">{{ __('Vagas para você') }}</span>
                <x-locale-switcher dark />
                <details class="relative">
                    <summary class="flex cursor-pointer list-none items-center gap-2 rounded-md p-1 hover:bg-noite-2" aria-label="{{ __('Menu da conta') }}">
                        <x-avatar :user="auth()->user()" />
                    </summary>
                    <div class="absolute right-0 z-20 mt-1 w-56 overflow-hidden rounded-md border border-line bg-raised py-1 text-sm text-ink shadow-lg">
                        <p class="truncate px-3 py-2 text-xs text-ink-muted">{{ auth()->user()->email }}</p>
                        <a href="{{ route('profile.show') }}" class="block px-3 py-2 hover:bg-sunken">{{ __('Meu perfil') }}</a>
                        <a href="{{ route('profile.show') }}#seguranca" class="block px-3 py-2 hover:bg-sunken">{{ __('Segurança') }}</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full px-3 py-2 text-left hover:bg-sunken">{{ __('Sair') }}</button>
                        </form>
                    </div>
                </details>
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-6 py-10">
        @if (session('status'))
            <div class="mb-6 rounded-md border border-sinal/30 bg-sinal/10 px-4 py-3 text-sm" role="status">
                {{ session('status') }}
            </div>
        @endif

        {{ $slot }}
    </main>
</x-layouts.app>
