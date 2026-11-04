@props(['job'])

{{-- Crédito da fonte, exigido pelos termos das APIs (Adzuna pede o nome com link em cada vaga) --}}
@php
    $slug = $job->source?->slug;
    [$label, $href] = match (true) {
        str_starts_with((string) $slug, 'adzuna') => ['Adzuna', 'https://www.adzuna.com.br'],
        str_starts_with((string) $slug, 'jooble') => ['Jooble', 'https://br.jooble.org'],
        $slug === 'remotive' => ['Remotive', 'https://remotive.com'],
        $job->source?->type?->value === 'github_issues' => ['GitHub · '.($job->source->config['repo'] ?? ''), 'https://github.com/'.($job->source->config['repo'] ?? '')],
        $job->user_id !== null => [__('Adicionada por você'), null],
        default => [null, null],
    };
@endphp

@if ($label)
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 text-xs text-ink-muted']) }}>
        @if ($href)
            {{ __('via') }}
            <a href="{{ $href }}" target="_blank" rel="noopener" class="inline-flex min-h-[23px] min-w-[60px] items-center font-semibold text-ink underline decoration-line underline-offset-2 hover:decoration-ink">{{ $label }}</a>
        @else
            {{ $label }}
        @endif
    </span>
@endif
