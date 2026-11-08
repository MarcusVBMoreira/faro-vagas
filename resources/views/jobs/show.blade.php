@php
    $required = $job->skills->where('pivot.is_required', true);
    $desirable = $job->skills->where('pivot.is_required', false);
@endphp

<x-layouts.dashboard :title="$job->title">
    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('jobs.index') }}" class="text-sm text-ink-muted underline">← {{ __('Voltar para as vagas') }}</a>

    <div class="mt-4 grid gap-6 lg:grid-cols-[1fr_300px]">
        <article class="min-w-0 rounded-lg border border-line bg-raised p-6">
            @if ($job->status !== 'active')
                <p class="mb-4 rounded-md bg-sunken px-3 py-2 text-sm">{{ __('Esta vaga não aparece mais na fonte e pode ter sido encerrada.') }}</p>
            @endif

            <h1 class="font-display text-2xl font-bold leading-tight">{{ $job->title }}</h1>
            <p class="mt-1 text-ink-muted">{{ collect([$job->company, $job->locationLabel()])->filter()->implode(' · ') ?: __('Empresa não informada') }}</p>

            <div class="mt-4 flex flex-wrap gap-2 text-xs font-medium">
                @if ($job->modality) <span class="rounded-sm bg-sunken px-2 py-1">{{ $job->modality->label() }}</span> @endif
                @if ($job->contractLabel()) <span class="rounded-sm bg-sunken px-2 py-1">{{ $job->contractLabel() }}</span> @endif
                @if ($job->level) <span class="rounded-sm bg-sunken px-2 py-1">{{ $job->level->label() }}</span> @endif
                @if ($job->area) <span class="rounded-sm bg-sunken px-2 py-1">{{ \App\Support\Areas::label($job->area) }}</span> @endif
                @if ($job->education_level) <span class="rounded-sm bg-sunken px-2 py-1">{{ $job->education_level->label() }}</span> @endif
                @if ($job->is_pcd) <span class="rounded-sm bg-sinal/10 px-2 py-1 text-sinal">{{ __('Vaga PcD') }}</span> @endif
                @if ($job->is_international) <span class="rounded-sm bg-farol-soft px-2 py-1">{{ __('Internacional · inglês') }}</span> @endif
            </div>

            @if ($job->ai_summary)
                <p class="mt-5 flex gap-2 rounded-md bg-sinal/5 p-3 text-sm">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-sinal" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9z"/></svg>
                    <span><span class="sr-only">{{ __('Resumo da IA:') }}</span>{{ $job->ai_summary }}</span>
                </p>
            @endif

            <h2 class="mt-6 font-display text-lg font-semibold">{{ __('Descrição') }}</h2>
            <div class="mt-2 whitespace-pre-line break-words text-sm leading-relaxed">{{ $job->description }}</div>
            @if (mb_strlen($job->description) < 600 && $job->url)
                <p class="mt-3 text-xs text-ink-muted">{{ __('A fonte mostra só um trecho. A descrição completa está no anúncio original.') }}</p>
            @endif
        </article>

        <aside class="space-y-4 lg:sticky lg:top-20 lg:self-start">
            @if ($match)
                <div class="rounded-lg border border-sinal/30 bg-sinal/5 p-5">
                    <p class="font-display text-2xl font-bold text-sinal">{{ __(':percent% compatível', ['percent' => $match->score]) }}</p>
                    @if ($match->strengths)
                        <p class="mt-3 text-xs font-semibold text-success">{{ __('Você tem:') }}</p>
                        <ul class="mt-1 flex flex-wrap gap-1.5">@foreach ($match->strengths as $reason) <x-match-reason :reason="$reason" /> @endforeach</ul>
                    @endif
                    @if ($match->gaps)
                        <p class="mt-3 text-xs font-semibold text-accent-text">{{ __('Falta:') }}</p>
                        <ul class="mt-1 flex flex-wrap gap-1.5">@foreach ($match->gaps as $reason) <x-match-reason :reason="$reason" type="gap" /> @endforeach</ul>
                    @endif
                </div>
            @endif
            <div class="rounded-lg border border-line bg-raised p-5">
                @if ($job->url)
                    <a href="{{ $job->url }}" target="_blank" rel="noopener nofollow" class="btn btn-primary h-11 w-full">{{ __('Ver vaga original e me candidatar') }}</a>
                    <p class="mt-2 text-xs text-ink-muted">{{ __('A candidatura é feita no site da empresa ou da fonte.') }}</p>
                @endif
                @if ($application)
                    <a href="{{ route('applications.index') }}" class="btn btn-secondary mt-3 h-10 w-full">{{ __('No seu quadro: :status', ['status' => $application->status->label()]) }}</a>
                @else
                    <form method="POST" action="{{ route('applications.store') }}" class="mt-3">
                        @csrf
                        <input type="hidden" name="job_posting_id" value="{{ $job->id }}">
                        <button type="submit" class="btn btn-secondary h-10 w-full">{{ __('Salvar vaga') }}</button>
                    </form>
                @endif
                <x-job-source-badge :job="$job" class="mt-3" />
                @if ($job->published_at)
                    <p class="mt-1 text-xs text-ink-muted">{{ __('Publicada :time', ['time' => $job->published_at->diffForHumans()]) }}</p>
                @endif

                @if ($job->user_id === auth()->id())
                    <form method="POST" action="{{ route('jobs.destroy', $job) }}" class="mt-4 border-t border-line pt-4">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-sm text-ink-muted underline hover:text-danger">{{ __('Remover esta vaga') }}</button>
                    </form>
                @endif
            </div>

            <div class="rounded-lg border border-line bg-raised p-5">
                <h2 class="font-semibold">{{ __('Habilidades pedidas') }}</h2>
                @if (! $job->extracted_at)
                    <p class="mt-2 text-sm text-ink-muted">{{ __('A IA ainda vai ler esta vaga. Volte em alguns minutos.') }}</p>
                @elseif ($job->skills->isEmpty())
                    <p class="mt-2 text-sm text-ink-muted">{{ __('A vaga não lista habilidades específicas.') }}</p>
                @else
                    @if ($required->isNotEmpty())
                        <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Obrigatórias') }}</p>
                        <ul class="mt-1 flex flex-wrap gap-1.5">
                            @foreach ($required as $skill) <li class="rounded-sm bg-sunken px-2 py-1 text-xs font-medium">{{ $skill->name }}</li> @endforeach
                        </ul>
                    @endif
                    @if ($desirable->isNotEmpty())
                        <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Diferenciais') }}</p>
                        <ul class="mt-1 flex flex-wrap gap-1.5">
                            @foreach ($desirable as $skill) <li class="rounded-sm border border-line px-2 py-1 text-xs">{{ $skill->name }}</li> @endforeach
                        </ul>
                    @endif
                @endif
            </div>
        </aside>
    </div>
</x-layouts.dashboard>
