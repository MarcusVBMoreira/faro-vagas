<x-layouts.dashboard :title="__('Vagas para você')">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-display text-3xl font-bold tracking-tight">{{ __('Vagas para você') }}</h1>
            <p class="mt-1 text-ink-muted">
                {{ __('As vagas mais compatíveis com o seu perfil, com a nota e o porquê.') }}
                @if ($computedAt) {{ __('Calculado :time.', ['time' => $computedAt->diffForHumans()]) }} @endif
            </p>
        </div>
        @if ($hasData)
            <form method="POST" action="{{ route('matches.refresh') }}" data-loading="{{ __('Recalculando…') }}">
                @csrf
                <button type="submit" class="btn btn-secondary h-10">{{ __('Recalcular') }}</button>
            </form>
        @endif
    </div>

    @if (! $hasData)
        <div class="mt-8 rounded-lg border border-dashed border-line p-10 text-center">
            <p class="font-medium">{{ __('Precisamos conhecer você primeiro.') }}</p>
            <p class="mt-1 text-sm text-ink-muted">{{ __('Envie seu currículo ou preencha cargo desejado, área e habilidades no perfil.') }}</p>
            <a href="{{ route('profile.show') }}" class="btn btn-primary mt-5 h-11">{{ __('Completar meu perfil') }}</a>
        </div>
    @elseif ($matches->isEmpty())
        <div class="mt-8 rounded-lg border border-dashed border-line p-10 text-center">
            <p class="font-medium">{{ __('Estamos calculando suas vagas.') }}</p>
            <p class="mt-1 text-sm text-ink-muted">{{ __('Leva alguns minutos. Volte daqui a pouco ou veja todas as vagas enquanto isso.') }}</p>
            <a href="{{ route('jobs.index') }}" class="btn btn-secondary mt-5 h-10">{{ __('Ver todas as vagas') }}</a>
        </div>
    @else
        @if (($hidden ?? 0) > 0)
            <p class="mt-4 rounded-md bg-sunken px-4 py-3 text-sm">
                {{ __(':count vagas ficaram de fora por nível ou escolaridade (por exemplo, pleno, sênior ou com diploma exigido).', ['count' => $hidden]) }}
                <a href="{{ route('profile.show') }}#objetivo" class="font-medium text-accent-text underline">{{ __('Ajustar níveis') }}</a>
            </p>
        @endif

        @if (! empty($learn))
            <section class="mt-4 rounded-lg border border-line bg-raised p-4" aria-labelledby="learn-title">
                <h2 id="learn-title" class="text-sm font-semibold">{{ __('O que mais aparece nas suas vagas e você ainda não tem') }}</h2>
                <ul class="mt-2 flex flex-wrap gap-1.5">
                    @foreach ($learn as $skill => $count)
                        <li class="rounded-sm bg-farol-soft px-2 py-1 text-xs font-medium">{{ $skill }} <span class="text-ink-muted">· {{ trans_choice(':count vaga|:count vagas', $count, ['count' => $count]) }}</span></li>
                    @endforeach
                </ul>
                <p class="mt-2 text-xs text-ink-muted">{{ __('Aprender uma delas, ou colocar no perfil se você já sabe, aumenta sua nota em várias vagas de uma vez.') }}</p>
            </section>
        @endif

        <ol class="mt-6 grid gap-4">
            @foreach ($matches as $match)
                @php
                    $job = $match->jobPosting;
                    $tone = $match->score >= 75 ? 'bg-sinal text-white' : ($match->score >= 50 ? 'bg-sinal/10 text-sinal' : 'bg-sunken text-ink-muted');
                @endphp
                <li class="rounded-lg border border-line bg-raised p-5">
                    <div class="flex items-start gap-4">
                        <div class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-lg {{ $tone }}" aria-label="{{ __(':percent% compatível', ['percent' => $match->score]) }}">
                            <span class="font-display text-xl font-bold leading-none">{{ $match->score }}</span>
                            <span class="text-[10px] font-semibold uppercase">%</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <h2 class="font-display text-lg font-semibold leading-snug">
                                    <a href="{{ route('jobs.show', $job) }}" class="hover:underline">{{ $job->title }}</a>
                                </h2>
                                <span class="text-xs text-ink-muted">{{ $job->published_at?->diffForHumans() }}</span>
                            </div>
                            <p class="text-sm text-ink-muted">
                                {{ collect([$job->company, $job->locationLabel(), $job->modality?->label(), $job->contractLabel()])->filter()->implode(' · ') }}
                            </p>

                            @if ($match->strengths)
                                <div class="mt-3">
                                    <p class="text-xs font-semibold text-success">{{ __('Você tem:') }}</p>
                                    <ul class="mt-1 flex flex-wrap gap-1.5">
                                        @foreach ($match->strengths as $reason) <x-match-reason :reason="$reason" type="strength" /> @endforeach
                                    </ul>
                                </div>
                            @endif
                            @if ($match->gaps)
                                <div class="mt-3">
                                    <p class="text-xs font-semibold text-accent-text">{{ __('Falta:') }}</p>
                                    <ul class="mt-1 flex flex-wrap gap-1.5">
                                        @foreach ($match->gaps as $reason) <x-match-reason :reason="$reason" type="gap" /> @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    @foreach (['good' => __('Bom match'), 'bad' => __('Não é pra mim')] as $value => $label)
                                        <form method="POST" action="{{ route('matches.feedback', $match) }}">
                                            @csrf
                                            <input type="hidden" name="feedback" value="{{ $value }}">
                                            <button type="submit" @class([
                                                'rounded-md border px-3 py-1.5 text-xs font-medium',
                                                'border-sinal bg-sinal/10 text-sinal' => $match->feedback === $value,
                                                'border-line text-ink-muted hover:text-ink' => $match->feedback !== $value,
                                            ]) @if ($match->feedback === $value) aria-pressed="true" @endif>{{ $label }}</button>
                                        </form>
                                    @endforeach
                                </div>
                                <div class="flex items-center gap-3">
                                    @if (isset($saved[$job->id]))
                                        <a href="{{ route('applications.index') }}" class="text-xs font-medium text-sinal">✓ {{ __('No seu quadro') }}</a>
                                    @else
                                        <form method="POST" action="{{ route('applications.store') }}">
                                            @csrf
                                            <input type="hidden" name="job_posting_id" value="{{ $job->id }}">
                                            <button type="submit" class="rounded-md border border-line px-3 py-1.5 text-xs font-medium hover:border-ink-muted">{{ __('Salvar vaga') }}</button>
                                        </form>
                                    @endif
                                    <a href="{{ route('jobs.show', $job) }}" class="text-sm font-medium text-accent-text underline">{{ __('Ver detalhes') }}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
            @endforeach
        </ol>
        <p class="mt-6 text-xs text-ink-muted">{{ __('Como a nota é calculada: habilidades pedidas (40%), semelhança entre o seu perfil e a vaga medida pela IA (30%), área (15%), nível (10%) e modelo de trabalho e contrato (5%). Informações sensíveis, como PcD, nunca entram na conta.') }}</p>
    @endif
</x-layouts.dashboard>
