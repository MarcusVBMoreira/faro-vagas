<x-layouts.auth title="Esqueci a senha" subtitle="Informe seu e-mail e enviaremos um link para criar uma senha nova.">
    <form method="POST" action="{{ route('password.email') }}" class="space-y-5" novalidate>
        @csrf
        <x-field label="E-mail" name="email" type="email" autocomplete="email" required autofocus />
        <button type="submit" class="btn btn-primary h-11 w-full">Enviar link</button>
    </form>

    <x-slot:footer>
        Lembrou? <a href="{{ route('login') }}" class="font-medium text-accent-text underline">Voltar para o login</a>
    </x-slot:footer>
</x-layouts.auth>
