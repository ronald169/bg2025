@props([
    'variant' => 'primary',
    'size' => 'md',
    'rounded' => 'full',
    'icon' => null,
    'iconPosition' => 'left',
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-semibold transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';

    $variants = [
        'primary' => 'bg-primary-500 text-white hover:bg-primary-600 focus:ring-primary-500',
        'secondary' => 'bg-secondary-500 text-white hover:bg-secondary-600 focus:ring-secondary-500',
        'outline' => 'border-2 border-primary-500 text-primary-600 hover:bg-primary-50 focus:ring-primary-500',
        'ghost' => 'text-gray-700 hover:bg-gray-100 focus:ring-gray-500',
        'danger' => 'bg-red-500 text-white hover:bg-red-600 focus:ring-red-500',
        'success' => 'bg-green-500 text-white hover:bg-green-600 focus:ring-green-500',
    ];

    $sizes = [
        'xs' => 'px-3 py-1.5 text-xs',
        'sm' => 'px-4 py-2 text-sm',
        'md' => 'px-6 py-3 text-base',
        'lg' => 'px-8 py-4 text-lg',
    ];

    $roundedClasses = [
        'none' => 'rounded-none',
        'sm' => 'rounded-md',
        'md' => 'rounded-lg',
        'lg' => 'rounded-xl',
        'full' => 'rounded-full',
    ];

    $variantClasses = $variants[$variant] ?? $variants['primary'];
    $sizeClasses = $sizes[$size] ?? $sizes['md'];
    $roundedClass = $roundedClasses[$rounded] ?? $roundedClasses['full'];
@endphp

<button {{ $attributes->merge(['class' => "$baseClasses $variantClasses $sizeClasses $roundedClass"]) }}>
    @if($icon && $iconPosition === 'left')
        <x-icon :name="$icon" class="w-5 h-5 mr-2" />
    @endif

    {{ $slot }}

    @if($icon && $iconPosition === 'right')
        <x-icon :name="$icon" class="w-5 h-5 ml-2" />
    @endif
</button>
