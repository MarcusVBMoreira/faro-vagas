<x-layouts.auth :title="__('Criar conta')" :subtitle="__('Grátis e em 1 minuto. Depois é só enviar o currículo.')">
    <form method="POST" action="{{ route('register') }}" class="space-y-5" novalidate>
        @csrf
        <x-field :label="__('Nome completo')" name="name" autocomplete="name" required autofocus />
        <x-field :label="__('E-mail')" name="email" type="email" autocomplete="email" required
                 :hint="__('Vamos enviar um código para confirmar que o e-mail é seu.')" />
        <x-field :label="__('Senha')" name="password" type="password" autocomplete="new-password" required
                 :hint="__('Mínimo de 8 caracteres, com letras e números.')" />
        <x-field :label="__('Confirme a senha')" name="password_confirmation" type="password" autocomplete="new-password" required />

        <div>
            <label class="flex items-start gap-3 text-sm">
                <input type="checkbox" name="terms" value="1" class="mt-0.5 h-4 w-4 rounded border-line accent-noite" @checked(old('terms'))>
                <span class="text-ink-muted">
                    {{ __('Concordo com o uso dos meus dados para encontrar vagas, conforme a') }}
                    <a href="{{ route('privacy') }}" class="font-medium text-accent-text underline" target="_blank">{{ __('política de privacidade') }}</a>.
                </span>
            </label>
            @error('terms') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="btn btn-primary h-11 w-full">{{ __('Criar conta') }}</button>
    </form>

    <x-slot:footer>
        {{ __('Já tem conta?') }} <a href="{{ route('login') }}" class="font-medium text-accent-text underline">{{ __('Entrar') }}</a>
    </x-slot:footer>
</x-layouts.auth>
