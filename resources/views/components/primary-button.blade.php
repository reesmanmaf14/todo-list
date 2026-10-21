<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-5 py-2.5 bg-crema-espresso border border-transparent rounded-lg font-medium text-sm text-white hover:bg-crema-espresso-hover focus:bg-crema-espresso-hover active:bg-crema-espresso-hover focus:outline-none focus:ring-4 focus:ring-crema-espresso/25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
