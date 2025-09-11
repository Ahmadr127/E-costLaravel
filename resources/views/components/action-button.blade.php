@props([
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'md',
    'icon' => '',
    'disabled' => false,
    'loading' => false,
    'confirm' => false,
    'confirmMessage' => 'Apakah Anda yakin?'
])

@php
$baseClasses = 'inline-flex items-center justify-center font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200';
$variantClasses = [
    'primary' => 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500',
    'secondary' => 'bg-gray-600 text-white hover:bg-gray-700 focus:ring-gray-500',
    'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
    'warning' => 'bg-yellow-600 text-white hover:bg-yellow-700 focus:ring-yellow-500',
    'info' => 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500',
    'outline' => 'border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 focus:ring-green-500',
    'link' => 'text-green-600 hover:text-green-700 underline focus:ring-green-500'
];
$sizeClasses = [
    'xs' => 'px-2 py-1 text-xs',
    'sm' => 'px-3 py-1.5 text-sm',
    'md' => 'px-4 py-2 text-sm',
    'lg' => 'px-6 py-3 text-base',
    'xl' => 'px-8 py-4 text-lg'
];
$disabledClasses = 'opacity-50 cursor-not-allowed';
@endphp

<{{ $type === 'link' ? 'a' : 'button' }}
    {{ $attributes->merge([
        'class' => $baseClasses . ' ' . $variantClasses[$variant] . ' ' . $sizeClasses[$size] . ($disabled || $loading ? ' ' . $disabledClasses : ''),
        'disabled' => $disabled || $loading,
        'onclick' => $confirm ? "return confirm('{$confirmMessage}')" : null
    ]) }}
>
    @if($loading)
    <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>
    @elseif($icon)
    <i class="{{ $icon }} mr-2"></i>
    @endif

    {{ $slot }}

    @if($loading)
    <span>Memproses...</span>
    @endif
</{{ $type === 'link' ? 'a' : 'button' }}>
