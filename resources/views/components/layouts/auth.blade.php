@props(['title', 'subtitle' => null])

<x-layouts.app :title="$title">
    <div class="flex min-h-screen flex-col bg-surface">
        <header class="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-6">
            <a href="{{ route('home') }}" aria-label="{{ __('Faro, página inicial') }}">
                <img src="{{ asset('images/faro-logo.svg') }}" alt="Faro" class="h-8 w-auto">
            </a>
            <x-locale-switcher />
        </header>

        <main class="flex flex-1 items-start justify-center px-6 pb-16 pt-4 sm:pt-10">
            <div class="w-full max-w-md">
                <h1 class="font-display text-3xl font-bold tracking-tight">{{ $title }}</h1>
                @if ($subtitle)
                    <p class="mt-2 text-ink-muted">{{ $subtitle }}</p>
                @endif

                @if (session('status'))
                    <div class="mt-6 rounded-md border border-sinal/30 bg-sinal/10 px-4 py-3 text-sm text-ink" role="status">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="mt-8 rounded-lg border border-line bg-raised p-6 sm:p-8">
                    {{ $slot }}
                </div>

                @isset($footer)
                    <div class="mt-6 text-center text-sm text-ink-muted">{{ $footer }}</div>
                @endisset
            </div>
        </main>
    </div>
</x-layouts.app>
