@props(['name', 'label'])

<div>
    <label for="{{ $name }}" class="dream-label">{{ $label }}</label>

    <input wire:model="{{ $name }}" id="{{ $name }}" {{ $attributes->merge(['type' => 'text', 'class' => 'dream-input']) }}>

    @error($name)
        <p x-data x-init="$dream.pop($el)" class="dream-error">{{ $message }}</p>
    @enderror
</div>
