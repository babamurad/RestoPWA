<button
    type="button"
    x-data="cartButton('{{ $vendorId }}')"
    @click="openCart()"
    class="relative p-2 hover:bg-gray-100 rounded-full transition-colors"
>
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
    </svg>
    <span
        x-show="badgeCount > 0"
        x-text="badgeCount"
        class="absolute -top-1 -right-1 bg-red-500 text-white text-xs w-5 h-5 flex items-center justify-center rounded-full"
    ></span>
</button>
