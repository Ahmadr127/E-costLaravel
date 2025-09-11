@props([
    'title' => '',
    'show' => 'open',
    'maxWidth' => '2xl',
    'align' => 'center' // 'start' | 'center' | 'end'
])

<div
    x-cloak
    x-show="{{ $show }}"
    x-transition.opacity
    class="fixed inset-0 z-50 flex items-center {{ $align === 'end' ? 'justify-end' : ($align === 'start' ? 'justify-start' : 'justify-center') }}"
    @keydown.escape.window="{{ $show }} = false"
>
    <!-- Overlay -->
    <div class="fixed inset-0 bg-black/50" @click="{{ $show }} = false"></div>

    <!-- Modal Panel -->
    <div class="relative bg-white rounded-lg shadow-xl w-full max-w-{{ $maxWidth }} mx-4 max-h-[85vh] flex flex-col">
        <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800">{{ $title }}</h3>
            <button type="button" class="text-gray-400 hover:text-gray-600" @click="{{ $show }} = false">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-5 overflow-y-auto text-left">
            {{ $slot }}
        </div>
    </div>
</div>
