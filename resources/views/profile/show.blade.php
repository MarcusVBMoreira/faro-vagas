@php
    $firstName = \Illuminate\Support\Str::before($user->name, ' ');
    $analyzing = $resume && ! $resume->isFinished();
    $skillsText = old('skills', $profile->skills->pluck('name')->implode(', '));
    $selectedModalities = old('desired_modalities', $profile->desired_modalities ?? []);
    $input = 'mt-1.5 block h-11 w-full rounded-md border border-line bg-raised px-3 text-ink focus:border-farol focus:outline-none focus:ring-2 focus:ring-farol/40';
@endphp

<x-layouts.dashboard title="Meu perfil">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-display text-3xl font-bold tracking-tight">Olá, {{ $firstName }}</h1>
            <p class="mt-1 text-ink-muted">Seu perfil é o que o Faro usa para encontrar as vagas certas.</p>
        </div>
    </div>

    {{-- Currículo --}}
    <section class="mt-8 rounded-lg border border-line bg-raised p-6" aria-labelledby="curriculo">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 id="curriculo" class="font-display text-xl font-semibold">Currículo</h2>
                <p class="mt-1 text-sm text-ink-muted">PDF de até 5 MB. A IA roda num servidor próprio do Faro: seu currículo não vai para serviços de terceiros.</p>
            </div>

            @if ($resume)
                <div id="resume-status" class="flex items-center gap-2 rounded-full px-3 py-1 text-sm font-medium
                    {{ match ($resume->status) {
                        'done' => 'bg-sinal/10 text-sinal',
                        'failed' => 'bg-danger/10 text-danger',
                        default => 'bg-farol-soft text-ink',
                    } }}">
                    @if ($analyzing)
                        <span class="h-2 w-2 animate-pulse rounded-full bg-farol"></span>
                    @endif
                    <span data-label>{{ $resume->statusLabel() }}</span>
                </div>
            @endif
        </div>

        @if ($resume)
            <p class="mt-4 text-sm">
                <span class="font-medium">{{ $resume->original_name }}</span>
                <span class="text-ink-muted">· enviado {{ $resume->created_at->diffForHumans() }}</span>
            </p>
            @if ($analyzing)
                <p class="mt-2 text-sm text-ink-muted">Isso leva cerca de 1 minuto. A página atualiza sozinha quando terminar.</p>
            @endif
            @if ($resume->status === 'failed' && $resume->error_message)
                <p class="mt-2 rounded-md bg-danger/10 px-3 py-2 text-sm text-danger">{{ $resume->error_message }}</p>
            @endif
        @endif

        <form method="POST" action="{{ route('resume.store') }}" enctype="multipart/form-data" class="mt-5 flex flex-wrap items-center gap-3">
            @csrf
            <label class="flex min-w-0 flex-1 cursor-pointer items-center gap-3 rounded-md border border-dashed border-line bg-surface px-4 py-3 text-sm hover:border-farol">
                <svg class="h-5 w-5 shrink-0 text-ink-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="M12 18v-6"/><path d="m9 15 3-3 3 3"/></svg>
                <span class="truncate text-ink-muted" data-file-name>{{ $resume ? 'Escolher outro PDF' : 'Escolher o PDF do currículo' }}</span>
                <input type="file" name="resume" accept="application/pdf,.pdf" class="sr-only" required
                       onchange="this.closest('label').querySelector('[data-file-name]').textContent = this.files[0]?.name ?? ''">
            </label>
            <button type="submit" class="btn btn-primary h-11" @disabled($analyzing)>
                {{ $resume ? 'Enviar e analisar de novo' : 'Enviar e analisar' }}
            </button>
        </form>
        @error('resume') <p class="mt-2 text-sm text-danger">{{ $message }}</p> @enderror
    </section>

    {{-- Perfil --}}
    <section class="mt-6 rounded-lg border border-line bg-raised p-6" aria-labelledby="perfil">
        <h2 id="perfil" class="font-display text-xl font-semibold">Seu perfil</h2>
        <p class="mt-1 text-sm text-ink-muted">
            @if ($resume?->status === 'done')
                Preenchido pela IA a partir do seu currículo. Confira e corrija o que precisar.
            @else
                Envie o currículo para a IA preencher, ou preencha você mesmo.
            @endif
        </p>

        <form method="POST" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
            @csrf
            @method('PUT')

            <x-field label="Título" name="headline" :value="$profile->headline" maxlength="160"
                     placeholder="Ex.: Estudante de Engenharia de Software focado em back-end" />

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="desired_level" class="block text-sm font-medium">Nível que você busca</label>
                    <select id="desired_level" name="desired_level" class="{{ $input }}">
                        <option value="">Selecione</option>
                        @foreach ($levels as $level)
                            <option value="{{ $level->value }}" @selected(old('desired_level', $profile->desired_level?->value) === $level->value)>{{ $level->label() }}</option>
                        @endforeach
                    </select>
                    @error('desired_level') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
                <x-field label="Área" name="area" :value="$profile->area" maxlength="60" placeholder="Ex.: back-end, dados, front-end" />

                <x-field label="Semestre atual" name="current_semester" type="number" min="1" max="12" :value="$profile->current_semester" />
                <x-field label="Ano previsto de formatura" name="graduation_year" type="number" min="2000" max="2045" :value="$profile->graduation_year" />

                <x-field label="Cidade" name="city" :value="$profile->city" maxlength="80" autocomplete="address-level2" />
                <div>
                    <label for="state" class="block text-sm font-medium">Estado</label>
                    <select id="state" name="state" class="{{ $input }}">
                        <option value="">Selecione</option>
                        @foreach ($states as $uf)
                            <option value="{{ $uf }}" @selected(old('state', $profile->state) === $uf)>{{ $uf }}</option>
                        @endforeach
                    </select>
                    @error('state') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <fieldset>
                    <legend class="text-sm font-medium">Modalidades que aceita</legend>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($modalities as $modality)
                            <label class="flex cursor-pointer items-center gap-2 rounded-md border border-line px-3 py-2 text-sm has-[:checked]:border-noite has-[:checked]:bg-sunken">
                                <input type="checkbox" name="desired_modalities[]" value="{{ $modality->value }}" class="h-4 w-4 accent-noite"
                                       @checked(in_array($modality->value, $selectedModalities, true))>
                                {{ $modality->label() }}
                            </label>
                        @endforeach
                    </div>
                    @error('desired_modalities.*') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </fieldset>
                <div>
                    <label for="english_level" class="block text-sm font-medium">Inglês</label>
                    <select id="english_level" name="english_level" class="{{ $input }}">
                        <option value="">Selecione</option>
                        @foreach ($englishLevels as $value => $label)
                            <option value="{{ $value }}" @selected(old('english_level', $profile->english_level) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label for="skills" class="block text-sm font-medium">Habilidades</label>
                <p class="mt-0.5 text-xs text-ink-muted">Separe por vírgula. Ex.: PHP, MySQL, Git, Laravel</p>
                <textarea id="skills" name="skills" rows="3" maxlength="2000"
                          class="mt-1.5 block w-full rounded-md border border-line bg-raised px-3 py-2 text-ink focus:border-farol focus:outline-none focus:ring-2 focus:ring-farol/40">{{ $skillsText }}</textarea>
                @error('skills') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror

                @if ($profile->skills->isNotEmpty())
                    <ul class="mt-3 flex flex-wrap gap-2" aria-label="Habilidades salvas">
                        @foreach ($profile->skills->sortBy('name') as $skill)
                            <li class="flex items-center gap-1.5 rounded-sm bg-sunken px-2 py-1 text-xs font-medium">
                                {{ $skill->name }}
                                @if ($skill->pivot->source === 'ai')
                                    <span class="rounded-sm bg-sinal/10 px-1 text-[10px] font-semibold uppercase text-sinal" title="Encontrada pela IA no seu currículo">IA</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <label class="flex items-start gap-3 text-sm">
                <input type="checkbox" name="digest_enabled" value="1" class="mt-0.5 h-4 w-4 accent-noite" @checked(old('digest_enabled', $profile->digest_enabled))>
                <span>Quero receber por e-mail as melhores vagas novas (chega na semana 4).</span>
            </label>

            <div class="flex justify-end border-t border-line pt-5">
                <button type="submit" class="btn btn-primary h-11 px-6">Salvar perfil</button>
            </div>
        </form>
    </section>

    {{-- Excluir conta --}}
    <section class="mt-6 rounded-lg border border-danger/30 bg-raised p-6" aria-labelledby="excluir">
        <h2 id="excluir" class="font-display text-xl font-semibold">Excluir conta</h2>
        <p class="mt-1 text-sm text-ink-muted">Apaga definitivamente sua conta, currículos, perfil e candidaturas. Não dá para desfazer.</p>
        <details class="mt-4" @if ($errors->has('confirm_email')) open @endif>
            <summary class="cursor-pointer text-sm font-medium text-danger">Quero excluir minha conta</summary>
            <form method="POST" action="{{ route('account.destroy') }}" class="mt-4 max-w-md space-y-4">
                @csrf
                @method('DELETE')
                <x-field label="Digite seu e-mail para confirmar" name="confirm_email" type="email" autocomplete="off" :placeholder="$user->email" />
                <button type="submit" class="btn h-11 bg-danger text-white hover:brightness-95">Excluir minha conta e meus dados</button>
            </form>
        </details>
    </section>

    @if ($analyzing)
        <script>
            (function poll() {
                setTimeout(async () => {
                    try {
                        const res = await fetch(@json(route('resume.status')), { headers: { 'Accept': 'application/json' } });
                        const data = await res.json();
                        if (data.finished) { window.location.reload(); return; }
                        const label = document.querySelector('#resume-status [data-label]');
                        if (label && data.label) label.textContent = data.label;
                    } catch (e) { /* tenta de novo */ }
                    poll();
                }, 4000);
            })();
        </script>
    @endif
</x-layouts.dashboard>
