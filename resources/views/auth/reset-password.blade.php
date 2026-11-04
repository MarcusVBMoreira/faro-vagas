<x-layouts.auth title="Nova senha">
    <form method="POST" action="{{ route('password.store') }}" class="space-y-5" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-field label="E-mail" name="email" type="email" :value="$email" autocomplete="email" required />
        <x-field label="Nova senha" name="password" type="password" autocomplete="new-password" required autofocus
                 hint="Mínimo de 8 caracteres, com letras e números." />
        <x-field label="Confirme a nova senha" name="password_confirmation" type="password" autocomplete="new-password" required />
        <button type="submit" class="btn btn-primary h-11 w-full">Salvar nova senha</button>
    </form>
</x-layouts.auth>
