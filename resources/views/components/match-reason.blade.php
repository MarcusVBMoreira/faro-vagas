@props(['reason', 'type' => 'strength'])

@php
    $k = $reason['k'] ?? '';
    $v = $reason['v'] ?? null;
    $text = match ($k) {
        'skill' => $v,
        'nice' => __(':skill (diferencial)', ['skill' => $v]),
        'area' => __('Mesma área que você busca'),
        'level' => $type === 'strength' ? __('Nível que você procura') : __('Nível: :level', ['level' => \App\Enums\JobLevel::tryFrom((string) $v)?->label() ?? $v]),
        'modality' => $type === 'strength' ? __('Modelo de trabalho que você quer') : __('Modelo: :modality', ['modality' => \App\Enums\WorkModality::tryFrom((string) $v)?->label() ?? $v]),
        'contract' => __('Contrato: :contract', ['contract' => \App\Enums\ContractType::tryFrom((string) $v)?->label() ?? $v]),
        'education' => __('Pede :education', ['education' => \App\Enums\EducationLevel::tryFrom((string) $v)?->label() ?? $v]),
        default => $v,
    };
@endphp

@if ($text)
    <li {{ $attributes->merge(['class' => $type === 'strength'
        ? 'rounded-sm bg-success/10 px-2 py-1 text-xs font-medium text-success'
        : 'rounded-sm bg-farol-soft px-2 py-1 text-xs font-medium text-ink']) }}>{{ $text }}</li>
@endif
