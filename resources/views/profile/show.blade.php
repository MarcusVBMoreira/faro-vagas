@php
    use Illuminate\Support\Str;

    $firstName = Str::before($user->name, ' ');
    $analyzing = $resume && ! $resume->isFinished();
    $skillsText = old('skills', $profile->skills->pluck('name')->implode(', '));
    $selModalities = old('desired_modalities', $profile->desired_modalities ?? []);
    $selContracts = old('desired_contracts', $profile->desired_contracts ?? []);
    $selLicenses = old('driver_license', $profile->driver_license ?? []);
    $langRows = old('languages', $profile->languages ?? []);
    $langRows = array_pad($langRows, max(count($langRows) + 1, 3), ['language' => '', 'level' => '']);
    $pcdValue = old('pcd', $profile->is_pcd === null ? '' : ($profile->is_pcd ? 'sim' : 'nao'));

    $input = 'mt-1.5 block h-11 w-full rounded-md border border-line bg-raised px-3 text-ink focus:border-farol focus:outline-none focus:ring-2 focus:ring-farol/40';
    $card = 'scroll-mt-20 rounded-lg border border-line bg-raised p-6';
    $chip = 'flex cursor-pointer items-center gap-2 rounded-md border border-line px-3 py-2 text-sm has-[:checked]:border-noite has-[:checked]:bg-sunken';
    $h2 = 'font-display text-xl font-semibold';
    $sections = [
        'curriculo' => __('Currículo'),
        'objetivo' => __('Objetivo profissional'),
        'sobre' => __('Sobre você'),
        'escolaridade' => __('Escolaridade e idiomas'),
        'habilidades' => __('Habilidades'),
        'experiencias' => __('Experiências'),
        'formacao' => __('Formação'),
        'diversidade' => __('Diversidade'),
        'seguranca' => __('Segurança'),
    ];
@endphp

<x-layouts.dashboard :title="__('Meu perfil')">
    <div class="grid gap-8 lg:grid-cols-[260px_1fr]">
        {{-- Coluna lateral --}}
        <aside class="space-y-6 lg:sticky lg:top-20 lg:self-start">
            <div class="rounded-lg border border-line bg-raised p-5 text-center">
                <x-avatar :user="$user" size="h-24 w-24 text-3xl" class="mx-auto" />
                <p class="mt-3 font-display text-lg font-semibold">{{ $user->name }}</p>
                @if ($profile->desired_role)
                    <p class="text-sm text-ink-muted">{{ $profile->desired_role }}</p>
                @endif

                <form method="POST" action="{{ route('avatar.store') }}" enctype="multipart/form-data" class="mt-4">
                    @csrf
                    <label class="btn btn-secondary h-9 cursor-pointer text-xs">
                        {{ $user->avatar_path ? __('Trocar foto') : __('Adicionar foto (opcional)') }}
                        <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" class="sr-only" onchange="this.form.submit()">
                    </label>
                </form>
                @if ($user->avatar_path)
                    <form method="POST" action="{{ route('avatar.destroy') }}" class="mt-1">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-ink-muted underline">{{ __('Remover foto') }}</button>
                    </form>
                @endif
                @error('avatar') <p class="mt-2 text-xs text-danger">{{ $message }}</p> @enderror

                <div class="mt-5 border-t border-line pt-4 text-left">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium">{{ __('Perfil completo') }}</span>
                        <span class="font-semibold text-sinal">{{ $completeness['percent'] }}%</span>
                    </div>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-sunken" role="progressbar" aria-valuenow="{{ $completeness['percent'] }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="h-full rounded-full bg-sinal" style="width: {{ $completeness['percent'] }}%"></div>
                    </div>
                    @if ($completeness['missing'])
                        <p class="mt-2 text-xs text-ink-muted">{{ __('Falta:') }} {{ implode(', ', array_slice($completeness['missing'], 0, 3)) }}</p>
                    @endif
                </div>
            </div>

            <nav class="hidden rounded-lg border border-line bg-raised p-2 text-sm lg:block" aria-label="{{ __('Seções do perfil') }}">
                @foreach ($sections as $id => $label)
                    <a href="#{{ $id }}" class="block rounded-md px-3 py-2 text-ink-muted hover:bg-sunken hover:text-ink">{{ $label }}</a>
                @endforeach
            </nav>
        </aside>

        <div class="min-w-0 space-y-6">
            <div>
                <h1 class="font-display text-3xl font-bold tracking-tight">{{ __('Olá, :name', ['name' => $firstName]) }}</h1>
                <p class="mt-1 text-ink-muted">{{ __('Quanto mais completo o perfil, melhores as vagas que o Faro encontra para você.') }}</p>
            </div>

            {{-- Currículo --}}
            <section id="curriculo" class="{{ $card }}">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="{{ $h2 }}">{{ __('Currículo') }}</h2>
                        <p class="mt-1 text-sm text-ink-muted">{{ __('PDF de até 5 MB. A IA preenche o perfil para você. Ela roda num servidor próprio do Faro: seu currículo não vai para serviços de terceiros.') }}</p>
                    </div>
                    @if ($resume)
                        <div id="resume-status" class="flex items-center gap-2 rounded-full px-3 py-1 text-sm font-medium
                            {{ match ($resume->status) { 'done' => 'bg-sinal/10 text-sinal', 'failed' => 'bg-danger/10 text-danger', default => 'bg-farol-soft text-ink' } }}">
                            @if ($analyzing) <span class="h-2 w-2 animate-pulse rounded-full bg-farol"></span> @endif
                            <span data-label>{{ $resume->statusLabel() }}</span>
                        </div>
                    @endif
                </div>

                @if ($resume)
                    <p class="mt-4 text-sm">
                        <span class="font-medium">{{ $resume->original_name }}</span>
                        <span class="text-ink-muted">· {{ __('enviado') }} {{ $resume->created_at->diffForHumans() }}</span>
                    </p>
                    @if ($analyzing)
                        <p class="mt-2 text-sm text-ink-muted">{{ __('Pode levar alguns minutos. A página atualiza sozinha quando terminar, e você pode sair e voltar depois.') }}</p>
                    @endif
                    @if ($resume->status === 'failed' && $resume->error_message)
                        <p class="mt-2 rounded-md bg-danger/10 px-3 py-2 text-sm text-danger">{{ __($resume->error_message) }}</p>
                    @endif
                @endif

                <form method="POST" action="{{ route('resume.store') }}" enctype="multipart/form-data" class="mt-5 flex flex-wrap items-center gap-3">
                    @csrf
                    <label class="flex min-w-0 flex-1 cursor-pointer items-center gap-3 rounded-md border border-dashed border-line bg-surface px-4 py-3 text-sm hover:border-farol">
                        <svg class="h-5 w-5 shrink-0 text-ink-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="M12 18v-6"/><path d="m9 15 3-3 3 3"/></svg>
                        <span class="truncate text-ink-muted" data-file-name>{{ $resume ? __('Escolher outro PDF') : __('Escolher o PDF do currículo') }}</span>
                        <input type="file" name="resume" accept="application/pdf,.pdf" class="sr-only" required
                               onchange="this.closest('label').querySelector('[data-file-name]').textContent = this.files[0]?.name ?? ''">
                    </label>
                    <button type="submit" class="btn btn-primary h-11" @disabled($analyzing)>
                        {{ $resume ? __('Enviar e analisar de novo') : __('Enviar e analisar') }}
                    </button>
                </form>
                @error('resume') <p class="mt-2 text-sm text-danger">{{ $message }}</p> @enderror
            </section>

            {{-- Formulário principal do perfil --}}
            <form method="POST" action="{{ route('profile.update') }}" class="space-y-6">
                @csrf
                @method('PUT')

                @if ($errors->any() && ! $errors->hasAny(['resume', 'avatar', 'role', 'course', 'current_password', 'confirm_email']))
                    <div class="rounded-md border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger" role="alert">
                        {{ __('Confira os campos destacados abaixo.') }}
                    </div>
                @endif

                <section id="objetivo" class="{{ $card }}">
                    <h2 class="{{ $h2 }}">{{ __('Objetivo profissional') }}</h2>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('O que você procura. É a base do match com as vagas.') }}</p>

                    <div class="mt-6 grid gap-5 sm:grid-cols-2">
                        <x-field :label="__('Cargo desejado')" name="desired_role" :value="$profile->desired_role" maxlength="120"
                                 :placeholder="__('Ex.: Assistente administrativo, Vendedor, Enfermeiro')" />
                        <div>
                            <label for="area" class="block text-sm font-medium">{{ __('Área') }}</label>
                            <select id="area" name="area" class="{{ $input }}">
                                <option value="">{{ __('Selecione') }}</option>
                                @foreach ($areas as $key => $label)
                                    <option value="{{ $key }}" @selected(old('area', $profile->area) === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('area') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="desired_level" class="block text-sm font-medium">{{ __('Nível') }}</label>
                            <select id="desired_level" name="desired_level" class="{{ $input }}">
                                <option value="">{{ __('Selecione') }}</option>
                                @foreach ($levels as $level)
                                    <option value="{{ $level->value }}" @selected(old('desired_level', $profile->desired_level?->value) === $level->value)>{{ $level->label() }}</option>
                                @endforeach
                            </select>
                            @error('desired_level') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                        </div>
                        <x-field :label="__('Pretensão salarial (R$ por mês)')" name="salary_expectation" type="number" min="0" step="100"
                                 :value="$profile->salary_expectation" :hint="__('Opcional. Ajuda a filtrar vagas fora da sua faixa.')" />
                        <div>
                            <label for="availability" class="block text-sm font-medium">{{ __('Disponibilidade para começar') }}</label>
                            <select id="availability" name="availability" class="{{ $input }}">
                                <option value="">{{ __('Selecione') }}</option>
                                @foreach ($availability as $key => $label)
                                    <option value="{{ $key }}" @selected(old('availability', $profile->availability) === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <label class="flex items-center gap-3 self-end rounded-md border border-line px-3 py-2.5 text-sm">
                            <input type="checkbox" name="open_to_work" value="1" class="h-4 w-4 accent-noite" @checked(old('open_to_work', $profile->open_to_work))>
                            {{ __('Estou aberto a oportunidades') }}
                        </label>
                    </div>

                    <fieldset class="mt-6">
                        <legend class="text-sm font-medium">{{ __('Tipos de contrato que aceita') }}</legend>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($contracts as $contract)
                                <label class="{{ $chip }}">
                                    <input type="checkbox" name="desired_contracts[]" value="{{ $contract->value }}" class="h-4 w-4 accent-noite" @checked(in_array($contract->value, $selContracts, true))>
                                    {{ $contract->label() }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <fieldset class="mt-5">
                        <legend class="text-sm font-medium">{{ __('Modelo de trabalho') }}</legend>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($modalities as $modality)
                                <label class="{{ $chip }}">
                                    <input type="checkbox" name="desired_modalities[]" value="{{ $modality->value }}" class="h-4 w-4 accent-noite" @checked(in_array($modality->value, $selModalities, true))>
                                    {{ $modality->label() }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                </section>

                <section id="sobre" class="{{ $card }}">
                    <h2 class="{{ $h2 }}">{{ __('Sobre você') }}</h2>

                    <div class="mt-6 space-y-5">
                        <x-field :label="__('Título do perfil')" name="headline" :value="$profile->headline" maxlength="160"
                                 :placeholder="__('Ex.: Assistente administrativo com experiência em faturamento')" />
                        <div>
                            <label for="summary" class="block text-sm font-medium">{{ __('Resumo profissional') }}</label>
                            <textarea id="summary" name="summary" rows="3" maxlength="1000"
                                      class="mt-1.5 block w-full rounded-md border border-line bg-raised px-3 py-2 text-ink focus:border-farol focus:outline-none focus:ring-2 focus:ring-farol/40"
                                      placeholder="{{ __('Em poucas frases: sua experiência, pontos fortes e o que você busca.') }}">{{ old('summary', $profile->summary) }}</textarea>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-field :label="__('Cidade')" name="city" :value="$profile->city" maxlength="80" autocomplete="address-level2" />
                            <div>
                                <label for="state" class="block text-sm font-medium">{{ __('Estado') }}</label>
                                <select id="state" name="state" class="{{ $input }}">
                                    <option value="">{{ __('Selecione') }}</option>
                                    @foreach ($states as $uf)
                                        <option value="{{ $uf }}" @selected(old('state', $profile->state) === $uf)>{{ $uf }}</option>
                                    @endforeach
                                </select>
                                @error('state') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                            </div>
                            <x-field :label="__('WhatsApp / telefone')" name="phone" type="tel" :value="$profile->phone" autocomplete="tel" placeholder="(11) 91234-5678" />
                            <x-field :label="__('LinkedIn')" name="linkedin_url" type="url" :value="$profile->linkedin_url" placeholder="https://www.linkedin.com/in/..." />
                            <div class="sm:col-span-2"><x-field :label="__('Portfólio ou site')" name="portfolio_url" type="url" :value="$profile->portfolio_url" placeholder="https://" /></div>
                        </div>

                        <fieldset>
                            <legend class="text-sm font-medium">{{ __('Carteira de motorista (CNH)') }}</legend>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach ($licenses as $license)
                                    <label class="{{ $chip }}">
                                        <input type="checkbox" name="driver_license[]" value="{{ $license }}" class="h-4 w-4 accent-noite" @checked(in_array($license, $selLicenses, true))>
                                        {{ $license }}
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>

                        <div class="flex flex-wrap gap-3 text-sm">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="willing_to_relocate" value="1" class="h-4 w-4 accent-noite" @checked(old('willing_to_relocate', $profile->willing_to_relocate))>
                                {{ __('Aceito mudar de cidade') }}
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="willing_to_travel" value="1" class="h-4 w-4 accent-noite" @checked(old('willing_to_travel', $profile->willing_to_travel))>
                                {{ __('Tenho disponibilidade para viajar') }}
                            </label>
                        </div>
                    </div>
                </section>

                <section id="escolaridade" class="{{ $card }}">
                    <h2 class="{{ $h2 }}">{{ __('Escolaridade e idiomas') }}</h2>

                    <div class="mt-6 grid gap-5 sm:grid-cols-3">
                        <div class="sm:col-span-3">
                            <label for="education_level" class="block text-sm font-medium">{{ __('Maior escolaridade') }}</label>
                            <select id="education_level" name="education_level" class="{{ $input }}">
                                <option value="">{{ __('Selecione') }}</option>
                                @foreach ($educationLevels as $level)
                                    <option value="{{ $level->value }}" @selected(old('education_level', $profile->education_level?->value) === $level->value)>{{ $level->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <x-field :label="__('Semestre atual (se estiver cursando)')" name="current_semester" type="number" min="1" max="12" :value="$profile->current_semester" />
                        <x-field :label="__('Ano de conclusão')" name="graduation_year" type="number" min="1960" max="2045" :value="$profile->graduation_year" />
                    </div>

                    <fieldset class="mt-6">
                        <legend class="text-sm font-medium">{{ __('Idiomas') }}</legend>
                        <div class="mt-2 space-y-2">
                            @foreach ($langRows as $i => $row)
                                <div class="grid grid-cols-2 gap-2">
                                    <select name="languages[{{ $i }}][language]" class="{{ $input }} mt-0" aria-label="{{ __('Idioma') }}">
                                        <option value="">{{ __('Idioma') }}</option>
                                        @foreach ($languageOptions as $key => $label)
                                            <option value="{{ $key }}" @selected(($row['language'] ?? '') === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <select name="languages[{{ $i }}][level]" class="{{ $input }} mt-0" aria-label="{{ __('Nível') }}">
                                        <option value="">{{ __('Nível') }}</option>
                                        @foreach ($languageLevels as $key => $label)
                                            <option value="{{ $key }}" @selected(($row['level'] ?? '') === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endforeach
                        </div>
                        <p class="mt-1 text-xs text-ink-muted">{{ __('Salve o perfil para abrir mais uma linha.') }}</p>
                    </fieldset>
                </section>

                <section id="habilidades" class="{{ $card }}">
                    <h2 class="{{ $h2 }}">{{ __('Habilidades') }}</h2>
                    <label for="skills" class="mt-1 block text-sm text-ink-muted">{{ __('Separe por vírgula. Ex.: Excel, atendimento ao cliente, vendas, empilhadeira, NR-35') }}</label>
                    <textarea id="skills" name="skills" rows="3" maxlength="2000"
                              class="mt-3 block w-full rounded-md border border-line bg-raised px-3 py-2 text-ink focus:border-farol focus:outline-none focus:ring-2 focus:ring-farol/40">{{ $skillsText }}</textarea>
                    @error('skills') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror

                    @if ($profile->skills->isNotEmpty())
                        <ul class="mt-3 flex flex-wrap gap-2" aria-label="{{ __('Habilidades salvas') }}">
                            @foreach ($profile->skills->sortBy('name') as $skill)
                                <li class="flex items-center gap-1.5 rounded-sm bg-sunken px-2 py-1 text-xs font-medium">
                                    {{ $skill->name }}
                                    @if ($skill->pivot->source === 'ai')
                                        <span class="rounded-sm bg-sinal/10 px-1 text-[10px] font-semibold uppercase text-sinal" title="{{ __('Encontrada pela IA no seu currículo') }}">IA</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                <section id="diversidade" class="{{ $card }}">
                    <h2 class="{{ $h2 }}">{{ __('Diversidade (opcional)') }}</h2>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('Muitas empresas têm vagas afirmativas para pessoas com deficiência (PcD). Essa informação é opcional, só serve para mostrar essas vagas para você e nunca entra na nota de compatibilidade.') }}</p>
                    <fieldset class="mt-4">
                        <legend class="text-sm font-medium">{{ __('Você é uma pessoa com deficiência (PcD)?') }}</legend>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach (['' => __('Prefiro não informar'), 'sim' => __('Sim'), 'nao' => __('Não')] as $value => $label)
                                <label class="{{ $chip }}">
                                    <input type="radio" name="pcd" value="{{ $value }}" class="h-4 w-4 accent-noite" @checked($pcdValue === $value)>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                </section>

                <div class="sticky bottom-4 z-10 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-line bg-raised/95 p-4 shadow-lg backdrop-blur">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="digest_enabled" value="1" class="h-4 w-4 accent-noite" @checked(old('digest_enabled', $profile->digest_enabled))>
                        {{ __('Receber por e-mail as melhores vagas novas') }}
                    </label>
                    <button type="submit" class="btn btn-primary h-11 px-6">{{ __('Salvar perfil') }}</button>
                </div>
            </form>

            {{-- Experiências --}}
            <section id="experiencias" class="{{ $card }}">
                <h2 class="{{ $h2 }}">{{ __('Experiências') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('Empregos, estágios, trabalhos voluntários ou como autônomo.') }}</p>

                @forelse ($profile->experiences as $experience)
                    <article class="mt-4 flex items-start justify-between gap-4 border-t border-line pt-4">
                        <div>
                            <p class="font-medium">{{ $experience->role }}</p>
                            <p class="text-sm text-ink-muted">{{ collect([$experience->company, $experience->location])->filter()->implode(' · ') }}</p>
                            @if ($experience->period())
                                <p class="text-xs text-ink-muted">{{ $experience->period() }}</p>
                            @endif
                            @if ($experience->description)
                                <p class="mt-2 text-sm">{{ $experience->description }}</p>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('experiences.destroy', $experience) }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-sm text-ink-muted underline hover:text-danger">{{ __('Remover') }}</button>
                        </form>
                    </article>
                @empty
                    <p class="mt-4 text-sm text-ink-muted">{{ __('Nenhuma experiência ainda. Primeiro emprego? Tudo bem: conte cursos, projetos e trabalhos voluntários.') }}</p>
                @endforelse

                <details class="mt-5 rounded-md border border-line p-4" @if ($errors->has('role')) open @endif>
                    <summary class="cursor-pointer text-sm font-medium text-accent-text">+ {{ __('Adicionar experiência') }}</summary>
                    <form method="POST" action="{{ route('experiences.store') }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                        @csrf
                        <x-field :label="__('Cargo')" name="role" required maxlength="120" />
                        <x-field :label="__('Empresa')" name="company" maxlength="120" />
                        <x-field :label="__('Início')" name="start_month" type="month" />
                        <x-field :label="__('Fim')" name="end_month" type="month" />
                        <label class="flex items-center gap-2 text-sm sm:col-span-2">
                            <input type="checkbox" name="is_current" value="1" class="h-4 w-4 accent-noite"> {{ __('Trabalho aqui atualmente') }}
                        </label>
                        <div class="sm:col-span-2">
                            <label for="description" class="block text-sm font-medium">{{ __('O que você fazia') }}</label>
                            <textarea id="description" name="description" rows="2" maxlength="1000" class="mt-1.5 block w-full rounded-md border border-line bg-raised px-3 py-2"></textarea>
                        </div>
                        <div class="sm:col-span-2"><button type="submit" class="btn btn-secondary h-10">{{ __('Adicionar') }}</button></div>
                    </form>
                </details>
            </section>

            {{-- Formação --}}
            <section id="formacao" class="{{ $card }}">
                <h2 class="{{ $h2 }}">{{ __('Formação') }}</h2>

                @forelse ($profile->educations as $education)
                    <article class="mt-4 flex items-start justify-between gap-4 border-t border-line pt-4">
                        <div>
                            <p class="font-medium">{{ $education->course }}</p>
                            <p class="text-sm text-ink-muted">{{ collect([$education->institution, $education->level?->label()])->filter()->implode(' · ') }}</p>
                            <p class="text-xs text-ink-muted">
                                {{ collect([$education->start_year, $education->end_year])->filter()->implode(' – ') }}
                                @if ($education->status) · {{ __(\App\Support\Brazil::EDUCATION_STATUS[$education->status] ?? $education->status) }} @endif
                            </p>
                        </div>
                        <form method="POST" action="{{ route('educations.destroy', $education) }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-sm text-ink-muted underline hover:text-danger">{{ __('Remover') }}</button>
                        </form>
                    </article>
                @empty
                    <p class="mt-4 text-sm text-ink-muted">{{ __('Nenhuma formação ainda.') }}</p>
                @endforelse

                <details class="mt-5 rounded-md border border-line p-4" @if ($errors->has('course')) open @endif>
                    <summary class="cursor-pointer text-sm font-medium text-accent-text">+ {{ __('Adicionar formação') }}</summary>
                    <form method="POST" action="{{ route('educations.store') }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                        @csrf
                        <x-field :label="__('Curso')" name="course" required maxlength="150" :placeholder="__('Ex.: Técnico em Enfermagem, Ensino médio, Administração')" />
                        <x-field :label="__('Instituição')" name="institution" maxlength="150" />
                        <div>
                            <label for="edu_level" class="block text-sm font-medium">{{ __('Nível') }}</label>
                            <select id="edu_level" name="level" class="{{ $input }}">
                                <option value="">{{ __('Selecione') }}</option>
                                @foreach ($educationLevels as $level)
                                    <option value="{{ $level->value }}">{{ $level->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="edu_status" class="block text-sm font-medium">{{ __('Situação') }}</label>
                            <select id="edu_status" name="status" class="{{ $input }}">
                                <option value="">{{ __('Selecione') }}</option>
                                @foreach ($educationStatus as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <x-field :label="__('Ano de início')" name="start_year" type="number" min="1950" max="2045" />
                        <x-field :label="__('Ano de conclusão')" name="end_year" type="number" min="1950" max="2045" />
                        <div class="sm:col-span-2"><button type="submit" class="btn btn-secondary h-10">{{ __('Adicionar') }}</button></div>
                    </form>
                </details>
            </section>

            {{-- Segurança --}}
            <section id="seguranca" class="{{ $card }}">
                <h2 class="{{ $h2 }}">{{ __('Segurança') }}</h2>
                <form method="POST" action="{{ route('account.security') }}" class="mt-5 max-w-lg space-y-5">
                    @csrf @method('PUT')
                    <label class="flex items-start gap-3 rounded-md border border-line p-4">
                        <input type="checkbox" name="two_factor_email" value="1" class="mt-1 h-4 w-4 accent-noite" @checked(old('two_factor_email', $user->two_factor_email))>
                        <span>
                            <span class="block font-medium">{{ __('Verificação em duas etapas por e-mail') }}</span>
                            <span class="block text-sm text-ink-muted">{{ __('A cada login, além da senha, pedimos um código enviado para o seu e-mail.') }}</span>
                        </span>
                    </label>
                    <x-field :label="__('Nova senha (opcional)')" name="password" type="password" autocomplete="new-password" />
                    <x-field :label="__('Confirme a nova senha')" name="password_confirmation" type="password" autocomplete="new-password" />
                    <x-field :label="__('Senha atual (para confirmar)')" name="current_password" type="password" autocomplete="current-password" required />
                    <button type="submit" class="btn btn-primary h-11">{{ __('Salvar segurança') }}</button>
                </form>

                <div class="mt-8 border-t border-line pt-6">
                    <h3 class="font-semibold text-danger">{{ __('Excluir conta') }}</h3>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('Apaga definitivamente sua conta, foto, currículos, perfil e candidaturas. Não dá para desfazer.') }}</p>
                    <details class="mt-3" @if ($errors->has('confirm_email')) open @endif>
                        <summary class="cursor-pointer text-sm font-medium text-danger">{{ __('Quero excluir minha conta') }}</summary>
                        <form method="POST" action="{{ route('account.destroy') }}" class="mt-4 max-w-md space-y-4">
                            @csrf @method('DELETE')
                            <x-field :label="__('Digite seu e-mail para confirmar')" name="confirm_email" type="email" autocomplete="off" :placeholder="$user->email" />
                            <button type="submit" class="btn h-11 bg-danger text-white hover:brightness-95">{{ __('Excluir minha conta e meus dados') }}</button>
                        </form>
                    </details>
                </div>
            </section>
        </div>
    </div>

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
                }, 5000);
            })();
        </script>
    @endif
</x-layouts.dashboard>
