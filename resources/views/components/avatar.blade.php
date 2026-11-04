@props(['user', 'size' => 'h-9 w-9 text-sm'])

@if ($url = $user->avatarUrl())
    <img src="{{ $url }}" alt="{{ __('Foto de :name', ['name' => $user->name]) }}" {{ $attributes->merge(['class' => "$size rounded-full object-cover"]) }}>
@else
    <span {{ $attributes->merge(['class' => "$size inline-flex items-center justify-center rounded-full bg-farol font-semibold text-on-farol"]) }} aria-hidden="true">
        {{ $user->initials() }}
    </span>
@endif
