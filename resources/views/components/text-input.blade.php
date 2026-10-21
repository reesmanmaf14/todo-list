@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-crema-latte bg-white text-crema-roast placeholder:text-crema-muted focus:border-crema-espresso focus:ring-crema-espresso/25 focus:ring-4 rounded-lg shadow-none transition duration-150']) }}>
