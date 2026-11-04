@props(['title'])

<x-layouts.app :title="$title">
    <header class="bg-noite text-white">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-6 py-4">
            <a href="{{ route('profile.show') }}" aria-label="Faro">
                <img src="{{ asset('images/faro-logo-dark.svg') }}" alt="Faro" class="h-8 w-auto">
            </a>
            <nav class="flex items-center gap-1 text-sm">
                <a href="{{ route('profile.show') }}"
                   class="rounded-md px-3 py-2 {{ request()->routeIs('profile.*') ? 'bg-noite-2 text-white' : 'text-white/75 hover:text-white' }}">
                    Meu perfil
                </a>
                <span class="hidden px-3 py-2 text-white/40 sm:inline" title="Chega na semana 3">Vagas para você</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-md px-3 py-2 text-white/75 hover:text-white">Sair</button>
                </form>
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-6 py-10">
        @if (session('status'))
            <div class="mb-6 rounded-md border border-sinal/30 bg-sinal/10 px-4 py-3 text-sm" role="status">
                {{ session('status') }}
            </div>
        @endif

        {{ $slot }}
    </main>
</x-layouts.app>
