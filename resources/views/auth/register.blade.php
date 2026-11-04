<x-layouts.auth title="Criar conta" subtitle="Leva 1 minuto. Depois é só enviar o currículo.">
    <form method="POST" action="{{ route('register') }}" class="space-y-5" novalidate>
        @csrf
        <x-field label="Nome" name="name" autocomplete="name" required autofocus />
        <x-field label="E-mail" name="email" type="email" autocomplete="email" required />
        <x-field label="Senha" name="password" type="password" autocomplete="new-password" required
                 hint="Mínimo de 8 caracteres, com letras e números." />
        <x-field label="Confirme a senha" name="password_confirmation" type="password" autocomplete="new-password" required />

        <div>
            <label class="flex items-start gap-3 text-sm">
                <input type="checkbox" name="terms" value="1" class="mt-0.5 h-4 w-4 rounded border-line accent-noite" @checked(old('terms'))>
                <span class="text-ink-muted">
                    Concordo com o uso dos meus dados para encontrar vagas, conforme a
                    <a href="{{ route('privacy') }}" class="font-medium text-accent-text underline" target="_blank">política de privacidade</a>.
                </span>
            </label>
            @error('terms') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="btn btn-primary h-11 w-full">Criar conta</button>
    </form>

    <x-slot:footer>
        Já tem conta? <a href="{{ route('login') }}" class="font-medium text-accent-text underline">Entrar</a>
    </x-slot:footer>
</x-layouts.auth>
