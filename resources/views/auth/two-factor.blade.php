<x-layouts.auth :title="__('Verificação em duas etapas')" :subtitle="__('Para sua segurança, enviamos um código para :email.', ['email' => $email])">
    <form method="POST" action="{{ route('two-factor.verify') }}" class="space-y-5">
        @csrf
        <x-code-input />
        <label class="flex items-start gap-2 text-sm">
            <input type="checkbox" name="trust_device" value="1" class="mt-0.5 h-4 w-4 rounded border-line accent-noite" @checked(old('trust_device', $remember))>
            <span>{{ __('Não pedir código neste navegador por :days dias', ['days' => \App\Http\Controllers\Auth\TwoFactorController::TRUST_DAYS]) }}</span>
        </label>
        <button type="submit" class="btn btn-primary h-11 w-full">{{ __('Entrar') }}</button>
    </form>

    <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-line pt-5 text-sm">
        <form method="POST" action="{{ route('two-factor.resend') }}">
            @csrf
            <button type="submit" class="btn btn-secondary h-10">{{ __('Reenviar código') }}</button>
        </form>
        <a href="{{ route('login') }}" class="text-accent-text underline">{{ __('Voltar para o login') }}</a>
    </div>
</x-layouts.auth>
