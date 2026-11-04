<x-layouts.auth :title="__('Esqueci a senha')" :subtitle="__('Informe seu e-mail e enviaremos um link para criar uma senha nova.')">
    <form method="POST" action="{{ route('password.email') }}" class="space-y-5" novalidate>
        @csrf
        <x-field :label="__('E-mail')" name="email" type="email" autocomplete="email" required autofocus />
        <button type="submit" class="btn btn-primary h-11 w-full">{{ __('Enviar link') }}</button>
    </form>

    <x-slot:footer>
        {{ __('Lembrou?') }} <a href="{{ route('login') }}" class="font-medium text-accent-text underline">{{ __('Voltar para o login') }}</a>
    </x-slot:footer>
</x-layouts.auth>
