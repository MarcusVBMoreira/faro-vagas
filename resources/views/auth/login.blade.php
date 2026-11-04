<x-layouts.auth title="Entrar" subtitle="Bem-vindo de volta.">
    <form method="POST" action="{{ route('login') }}" class="space-y-5" novalidate>
        @csrf
        <x-field label="E-mail" name="email" type="email" autocomplete="email" required autofocus />
        <x-field label="Senha" name="password" type="password" autocomplete="current-password" required />

        <div class="flex items-center justify-between text-sm">
            <label class="flex items-center gap-2 text-ink-muted">
                <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-line accent-noite">
                Manter conectado
            </label>
            <a href="{{ route('password.request') }}" class="font-medium text-accent-text underline">Esqueci a senha</a>
        </div>

        <button type="submit" class="btn btn-primary h-11 w-full">Entrar</button>
    </form>

    <x-slot:footer>
        Ainda não tem conta? <a href="{{ route('register') }}" class="font-medium text-accent-text underline">Criar conta</a>
    </x-slot:footer>
</x-layouts.auth>
