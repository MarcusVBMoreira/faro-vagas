<x-layouts.auth :title="__('Confirme seu e-mail')" :subtitle="__('Enviamos um código de 6 dígitos para :email. Ele vale por 15 minutos.', ['email' => $email])">
    <form method="POST" action="{{ route('verification.verify') }}" class="space-y-5">
        @csrf
        <x-code-input />
        <button type="submit" class="btn btn-primary h-11 w-full">{{ __('Confirmar e-mail') }}</button>
    </form>

    <div class="mt-6 border-t border-line pt-5 text-sm text-ink-muted">
        <p>{{ __('Não chegou? Confira a caixa de spam ou peça outro código.') }}</p>
        <div class="mt-3 flex flex-wrap items-center gap-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="btn btn-secondary h-10">{{ __('Reenviar código') }}</button>
            </form>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-accent-text underline">{{ __('Usar outro e-mail') }}</button>
            </form>
        </div>
    </div>
</x-layouts.auth>
