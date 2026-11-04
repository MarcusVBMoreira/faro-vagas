@php
    $select = 'block h-10 w-full rounded-md border border-line bg-raised px-3 text-sm text-ink focus:border-farol focus:outline-none focus:ring-2 focus:ring-farol/40';
    $activeFilters = collect($filters)->except('internacional')->filter(fn ($v) => filled($v))->count();
@endphp

<x-layouts.dashboard :title="__('Vagas')">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-display text-3xl font-bold tracking-tight">{{ __('Vagas') }}</h1>
            <p class="mt-1 text-ink-muted">
                {{ __('Grande São Paulo e remotas, de todas as áreas.') }}
                @if ($lastFetch)
                    {{ __('Atualizado :time.', ['time' => \Illuminate\Support\Carbon::parse($lastFetch)->diffForHumans()]) }}
                @endif
            </p>
        </div>
        <a href="{{ route('jobs.create') }}" class="btn btn-secondary h-10">+ {{ __('Adicionar vaga') }}</a>
    </div>

    {{-- Busca e filtros --}}
    <form method="GET" action="{{ route('jobs.index') }}" class="mt-6 rounded-lg border border-line bg-raised p-4" role="search" data-loading="{{ __('Buscando vagas…') }}">
        <div class="flex flex-col gap-3 sm:flex-row">
            <label class="sr-only" for="q">{{ __('Buscar') }}</label>
            <input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" maxlength="100"
                   placeholder="{{ __('Cargo ou empresa (ex.: auxiliar administrativo, enfermagem, vendas)') }}"
                   class="{{ $select }} h-11 flex-1 text-base">
            <button type="submit" class="btn btn-primary h-11 px-6">{{ __('Buscar') }}</button>
        </div>

        <details class="mt-3" @if ($activeFilters > 0 || ($filters['internacional'] ?? '') === 'so') open @endif>
            <summary class="cursor-pointer text-sm font-medium text-accent-text">
                {{ __('Filtros') }} @if ($activeFilters) ({{ $activeFilters }}) @endif
            </summary>
            <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <select name="area" class="{{ $select }}" aria-label="{{ __('Área') }}">
                    <option value="">{{ __('Todas as áreas') }}</option>
                    @foreach ($areas as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['area'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="nivel" class="{{ $select }}" aria-label="{{ __('Nível') }}">
                    <option value="">{{ __('Todos os níveis') }}</option>
                    @foreach ($levels as $level)
                        <option value="{{ $level->value }}" @selected(($filters['nivel'] ?? '') === $level->value)>{{ $level->label() }}</option>
                    @endforeach
                </select>
                <select name="modalidade" class="{{ $select }}" aria-label="{{ __('Modelo de trabalho') }}">
                    <option value="">{{ __('Presencial, híbrido ou remoto') }}</option>
                    @foreach ($modalities as $modality)
                        <option value="{{ $modality->value }}" @selected(($filters['modalidade'] ?? '') === $modality->value)>{{ $modality->label() }}</option>
                    @endforeach
                </select>
                <select name="contrato" class="{{ $select }}" aria-label="{{ __('Contrato') }}">
                    <option value="">{{ __('Todos os contratos') }}</option>
                    @foreach ($contracts as $contract)
                        <option value="{{ $contract->value }}" @selected(($filters['contrato'] ?? '') === $contract->value)>{{ $contract->label() }}</option>
                    @endforeach
                </select>
                <select name="internacional" class="{{ $select }}" aria-label="{{ __('Vagas internacionais') }}">
                    <option value="nao" @selected($filters['internacional'] === 'nao')>{{ __('Só vagas do Brasil') }}</option>
                    <option value="sim" @selected($filters['internacional'] === 'sim')>{{ __('Brasil + internacionais') }}</option>
                    <option value="so" @selected($filters['internacional'] === 'so')>{{ __('Só internacionais') }}</option>
                </select>
            </div>
            <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="pcd" value="1" class="h-4 w-4 accent-noite" @checked(! empty($filters['pcd']))>
                    {{ __('Só vagas afirmativas para PcD') }}
                </label>
                <a href="{{ route('jobs.index') }}" class="text-sm text-ink-muted underline">{{ __('Limpar filtros') }}</a>
            </div>
        </details>
    </form>

    <p class="mt-6 text-sm text-ink-muted">{{ trans_choice(':count vaga encontrada|:count vagas encontradas', $jobs->total(), ['count' => $jobs->total()]) }}</p>

    <div class="mt-3 grid gap-4">
        @forelse ($jobs as $job)
            <article class="rounded-lg border border-line bg-raised p-5 transition hover:border-ink-muted/40">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="font-display text-lg font-semibold leading-snug">
                            <a href="{{ route('jobs.show', $job) }}" class="hover:underline">{{ $job->title }}</a>
                        </h2>
                        <p class="mt-0.5 text-sm text-ink-muted">
                            {{ collect([$job->company, $job->locationLabel()])->filter()->implode(' · ') ?: __('Empresa não informada') }}
                        </p>
                    </div>
                    <span class="shrink-0 text-xs text-ink-muted">{{ $job->published_at?->diffForHumans() }}</span>
                </div>

                <div class="mt-3 flex flex-wrap gap-2 text-xs font-medium">
                    @if ($job->modality) <span class="rounded-sm bg-sunken px-2 py-1">{{ $job->modality->label() }}</span> @endif
                    @if ($job->contractLabel()) <span class="rounded-sm bg-sunken px-2 py-1">{{ $job->contractLabel() }}</span> @endif
                    @if ($job->level) <span class="rounded-sm bg-sunken px-2 py-1">{{ $job->level->label() }}</span> @endif
                    @if ($job->area) <span class="rounded-sm bg-sunken px-2 py-1">{{ \App\Support\Areas::label($job->area) }}</span> @endif
                    @if ($job->is_pcd) <span class="rounded-sm bg-sinal/10 px-2 py-1 text-sinal">{{ __('Vaga PcD') }}</span> @endif
                    @if ($job->is_international) <span class="rounded-sm bg-farol-soft px-2 py-1">{{ __('Internacional · inglês') }}</span> @endif
                    @if ($job->published_at?->isToday()) <span class="rounded-sm bg-farol-soft px-2 py-1">{{ __('Nova hoje') }}</span> @endif
                </div>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <x-job-source-badge :job="$job" />
                    <a href="{{ route('jobs.show', $job) }}" class="text-sm font-medium text-accent-text underline">{{ __('Ver detalhes') }}</a>
                </div>
            </article>
        @empty
            <div class="rounded-lg border border-dashed border-line p-10 text-center">
                <p class="font-medium">{{ __('Nenhuma vaga encontrada com esses filtros.') }}</p>
                <p class="mt-1 text-sm text-ink-muted">{{ __('Tente outra palavra ou limpe os filtros. A coleta roda todo dia de madrugada.') }}</p>
            </div>
        @endforelse
    </div>

    <div class="mt-8">{{ $jobs->links('pagination::simple-tailwind') }}</div>
</x-layouts.dashboard>
