@props([
    'variant' => 'default',
    'hoverable' => true,
    'padding' => 'p-6',
])

@php
    $baseClasses = 'rounded-2xl border transition-all duration-300';

    $variants = [
        'default' => 'bg-white border-gray-200',
        'elevated' => 'bg-white border-gray-200 shadow-lg',
        'accent' => 'bg-primary-50 border-primary-200',
        'secondary' => 'bg-secondary-50 border-secondary-200',
    ];

    $variantClasses = $variants[$variant] ?? $variants['default'];
    $hoverClass = $hoverable ? 'hover:shadow-xl hover:-translate-y-1' : '';
@endphp

<div {{ $attributes->merge(['class' => "$baseClasses $variantClasses $hoverClass $padding"]) }}>
    {{ $slot }}
</div>
