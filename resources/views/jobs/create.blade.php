<x-layouts.dashboard :title="__('Adicionar vaga')">
    <a href="{{ route('jobs.index') }}" class="text-sm text-ink-muted underline">← {{ __('Voltar para as vagas') }}</a>

    <div class="mt-4 max-w-2xl rounded-lg border border-line bg-raised p-6">
        <h1 class="font-display text-2xl font-bold">{{ __('Adicionar vaga') }}</h1>
        <p class="mt-1 text-sm text-ink-muted">{{ __('Achou uma vaga em outro lugar (LinkedIn, site da empresa, grupo de WhatsApp)? Cole aqui: ela entra na sua lista e no seu match. Só você vê as vagas que adicionar.') }}</p>

        <form method="POST" action="{{ route('jobs.store') }}" class="mt-6 space-y-5" data-loading="{{ __('Salvando vaga…') }}">
            @csrf
            <x-field :label="__('Cargo')" name="title" required maxlength="250" :placeholder="__('Ex.: Auxiliar de logística')" />
            <div class="grid gap-5 sm:grid-cols-2">
                <x-field :label="__('Empresa')" name="company" maxlength="250" />
                <x-field :label="__('Link da vaga (opcional)')" name="url" type="url" maxlength="500" placeholder="https://" />
                <x-field :label="__('Cidade')" name="city" maxlength="80" />
                <div>
                    <label for="state" class="block text-sm font-medium">{{ __('Estado') }}</label>
                    <select id="state" name="state" class="mt-1.5 block h-11 w-full rounded-md border border-line bg-raised px-3">
                        <option value="">{{ __('Selecione') }}</option>
                        @foreach ($states as $uf)
                            <option value="{{ $uf }}" @selected(old('state') === $uf)>{{ $uf }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="remote" value="1" class="h-4 w-4 accent-noite" @checked(old('remote'))>
                {{ __('Vaga remota') }}
            </label>
            <div>
                <label for="description" class="block text-sm font-medium">{{ __('Descrição da vaga') }}</label>
                <textarea id="description" name="description" rows="10" required minlength="30" maxlength="20000"
                          class="mt-1.5 block w-full rounded-md border border-line bg-raised px-3 py-2 text-sm focus:border-farol focus:outline-none focus:ring-2 focus:ring-farol/40"
                          placeholder="{{ __('Cole aqui o texto completo da vaga: atividades, requisitos, benefícios.') }}">{{ old('description') }}</textarea>
                @error('description') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="btn btn-primary h-11">{{ __('Adicionar vaga') }}</button>
        </form>
    </div>
</x-layouts.dashboard>
