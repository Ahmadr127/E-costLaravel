@props([
    'name',
    'label',
    'type' => 'text',
    'value' => '',
    'placeholder' => '',
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'help' => '',
    'error' => '',
    'options' => [],
    'rows' => 3
])

<div class="space-y-2">
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700">
        {{ $label }}
        @if($required)
        <span class="text-red-500">*</span>
        @endif
    </label>

    @if($type === 'select')
    <select name="{{ $name }}" 
            id="{{ $name }}"
            @if($required) required @endif
            @if($disabled) disabled @endif
            @if($readonly) readonly @endif
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent @error($name) border-red-500 @enderror">
        <option value="">{{ $placeholder ?: 'Pilih ' . strtolower($label) }}</option>
        @foreach($options as $value => $label)
        <option value="{{ $value }}" {{ old($name, $value) == $value ? 'selected' : '' }}>
            {{ $label }}
        </option>
        @endforeach
    </select>

    @elseif($type === 'textarea')
    <textarea name="{{ $name }}" 
              id="{{ $name }}"
              rows="{{ $rows }}"
              @if($required) required @endif
              @if($disabled) disabled @endif
              @if($readonly) readonly @endif
              placeholder="{{ $placeholder }}"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent @error($name) border-red-500 @enderror">{{ old($name, $value) }}</textarea>

    @elseif($type === 'checkbox')
    <div class="flex items-center">
        <input type="checkbox" 
               name="{{ $name }}" 
               id="{{ $name }}"
               value="1"
               @if(old($name, $value)) checked @endif
               @if($disabled) disabled @endif
               @if($readonly) readonly @endif
               class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded @error($name) border-red-500 @enderror">
        <label for="{{ $name }}" class="ml-2 block text-sm text-gray-700">
            {{ $placeholder ?: $label }}
        </label>
    </div>

    @else
    <input type="{{ $type }}" 
           name="{{ $name }}" 
           id="{{ $name }}"
           value="{{ old($name, $value) }}"
           placeholder="{{ $placeholder }}"
           @if($required) required @endif
           @if($disabled) disabled @endif
           @if($readonly) readonly @endif
           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent @error($name) border-red-500 @enderror">
    @endif

    @if($help)
    <p class="text-sm text-gray-500">{{ $help }}</p>
    @endif

    @error($name)
    <p class="text-sm text-red-600 flex items-center">
        <i class="fas fa-exclamation-circle mr-1"></i>
        {{ $message }}
    </p>
    @enderror
</div>
