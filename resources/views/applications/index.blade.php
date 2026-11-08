@php
    $tones = [
        'saved' => 'border-t-line', 'applied' => 'border-t-farol', 'test' => 'border-t-farol',
        'interview' => 'border-t-sinal', 'offer' => 'border-t-success', 'rejected' => 'border-t-ink-muted',
    ];
@endphp

<x-layouts.dashboard :title="__('Candidaturas')">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-display text-3xl font-bold tracking-tight">{{ __('Candidaturas') }}</h1>
            <p class="mt-1 text-ink-muted">{{ __('Arraste os cartões entre as etapas, ou use o seletor em cada cartão.') }}</p>
        </div>
        <a href="{{ route('matches.index') }}" class="btn btn-secondary h-10">{{ __('Ver vagas para você') }}</a>
    </div>

    {{-- Resumo por etapa --}}
    <dl class="mt-6 grid grid-cols-3 gap-2 sm:grid-cols-6">
        @foreach ($columns as $column)
            <div class="rounded-md border border-line bg-raised px-3 py-2">
                <dt class="truncate text-xs text-ink-muted">{{ $column->label() }}</dt>
                <dd class="font-display text-xl font-bold" data-count="{{ $column->value }}">{{ $applications->get($column->value)?->count() ?? 0 }}</dd>
            </div>
        @endforeach
    </dl>

    @if ($total === 0)
        <div class="mt-8 rounded-lg border border-dashed border-line p-10 text-center">
            <p class="font-medium">{{ __('Seu quadro está vazio.') }}</p>
            <p class="mt-1 text-sm text-ink-muted">{{ __('Clique em "Salvar vaga" em qualquer vaga para acompanhar sua candidatura aqui.') }}</p>
        </div>
    @endif

    <div class="mt-6 flex snap-x gap-4 overflow-x-auto pb-4" data-board data-reorder-url="{{ route('applications.reorder') }}">
        @foreach ($columns as $column)
            <section class="flex w-72 shrink-0 snap-start flex-col rounded-lg border border-t-4 border-line bg-sunken {{ $tones[$column->value] }}" aria-labelledby="col-{{ $column->value }}">
                <h2 id="col-{{ $column->value }}" class="px-3 pt-3 text-sm font-semibold">{{ $column->label() }}</h2>
                <ol class="flex min-h-24 flex-1 flex-col gap-2 p-3" data-column="{{ $column->value }}">
                    @foreach ($applications->get($column->value, collect()) as $application)
                        @php $job = $application->jobPosting; @endphp
                        <li class="cursor-grab rounded-md border border-line bg-raised p-3 shadow-sm active:cursor-grabbing" data-id="{{ $application->id }}">
                            <a href="{{ route('jobs.show', $job) }}" class="font-medium leading-snug hover:underline">{{ $job->title }}</a>
                            <p class="mt-0.5 text-xs text-ink-muted">{{ collect([$job->company, $job->locationLabel()])->filter()->implode(' · ') }}</p>
                            @if ($job->status !== 'active')
                                <p class="mt-1 text-xs text-danger">{{ __('Vaga encerrada na fonte') }}</p>
                            @endif
                            @if ($application->applied_at)
                                <p class="mt-1 text-xs text-ink-muted">{{ __('Candidatura em :date', ['date' => $application->applied_at->translatedFormat('d M')]) }}</p>
                            @endif

                            <details class="mt-2 text-sm">
                                <summary class="cursor-pointer text-xs font-medium text-accent-text">{{ $application->notes ? __('Ver anotações') : __('Anotar / mover') }}</summary>
                                <form method="POST" action="{{ route('applications.update', $application) }}" class="mt-2 space-y-2" data-no-loading>
                                    @csrf @method('PATCH')
                                    <label class="sr-only" for="notes-{{ $application->id }}">{{ __('Anotações') }}</label>
                                    <textarea id="notes-{{ $application->id }}" name="notes" rows="3" maxlength="5000"
                                              class="block w-full rounded-md border border-line bg-raised px-2 py-1 text-sm"
                                              placeholder="{{ __('Ex.: entrevista dia 12 às 14h com a Ana do RH') }}">{{ $application->notes }}</textarea>
                                    <button type="submit" class="btn btn-secondary h-8 text-xs">{{ __('Salvar anotação') }}</button>
                                </form>
                                <form method="POST" action="{{ route('applications.update', $application) }}" class="mt-2 flex gap-2">
                                    @csrf @method('PATCH')
                                    <label class="sr-only" for="status-{{ $application->id }}">{{ __('Etapa') }}</label>
                                    <select id="status-{{ $application->id }}" name="status" class="h-8 flex-1 rounded-md border border-line bg-raised px-2 text-xs">
                                        @foreach ($columns as $option)
                                            <option value="{{ $option->value }}" @selected($option === $application->status)>{{ $option->label() }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-secondary h-8 text-xs">{{ __('Mover') }}</button>
                                </form>
                                <form method="POST" action="{{ route('applications.destroy', $application) }}" class="mt-2">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs text-ink-muted underline hover:text-danger">{{ __('Tirar do quadro') }}</button>
                                </form>
                            </details>
                        </li>
                    @endforeach
                </ol>
            </section>
        @endforeach
    </div>
</x-layouts.dashboard>
