@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-crema-roast bg-crema-latte-tint border-l-[3px] border-crema-espresso rounded-md px-3 py-2']) }}>
        {{ $status }}
    </div>
@endif
