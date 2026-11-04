@props(['title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' · Faro' : 'Faro · '.__('A IA que encontra as vagas certas para você') }}</title>
    <meta name="description" content="{{ __('Envie seu currículo e o Faro usa IA para encontrar as vagas mais compatíveis com você, em todas as áreas.') }}">
    <meta name="theme-color" content="#0E1630">

    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600|space-grotesk:600,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    {{ $slot }}

    {{-- Tela de carregamento (controlada por resources/js/app.js) --}}
    <div id="page-loading" class="fixed inset-0 z-[100] hidden items-center justify-center bg-noite/50 backdrop-blur-sm"
         role="status" aria-live="polite" aria-hidden="true" data-default="{{ __('Carregando…') }}">
        <div class="flex items-center gap-3 rounded-lg bg-raised px-6 py-4 text-ink shadow-2xl">
            <svg class="h-6 w-6 text-farol motion-safe:animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity="0.25" stroke-width="3"/>
                <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
            </svg>
            <span class="font-medium" data-loading-text>{{ __('Carregando…') }}</span>
        </div>
    </div>
</body>
</html>
