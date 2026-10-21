@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-crema-roast']) }}>
    {{ $value ?? $slot }}
</label>
