{{-- Campo do código de 6 dígitos (aceita colar; o teclado numérico abre no celular). --}}
<div>
    <label for="code" class="block text-sm font-medium">{{ __('Código de 6 dígitos') }}</label>
    <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="7" required autofocus
           pattern="[0-9 ]*" placeholder="000000"
           class="mt-1.5 block h-14 w-full rounded-md border border-line bg-raised px-3 text-center font-display text-3xl tracking-[0.5em] text-ink focus:border-farol focus:outline-none focus:ring-2 focus:ring-farol/40"
           @error('code') aria-invalid="true" aria-describedby="code-error" @enderror>
    @error('code') <p id="code-error" class="mt-2 text-sm text-danger">{{ $message }}</p> @enderror
</div>
