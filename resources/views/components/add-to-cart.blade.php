<button
    type="button"
    @click="
        window.dispatchEvent(new CustomEvent('cart-add-item', {
            detail: {
                productId: '{{ $productId }}',
                vendorId: '{{ $vendorId }}',
                price: {{ $price }},
                productName: '{{ $productName }}',
                modifiers: {{ json_encode($modifiers ?? []) }}
            }
        }))
    "
    {{ $attributes->class(['inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors']) }}
>
    {{ $slot }}
</button>
