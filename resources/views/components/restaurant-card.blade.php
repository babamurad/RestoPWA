@props(['restaurant'])

<a href="{{ route('restaurants.show', $restaurant->slug) }}" class="flex gap-4 p-3 bg-white rounded-2xl shadow-sm border border-gray-100 cursor-pointer card-hover touch-feedback">
    <div class="relative flex-shrink-0 w-24 h-24 overflow-hidden rounded-xl">
        <img src="{{ $restaurant->image_url }}" alt="{{ $restaurant->name }}" class="w-full h-full object-cover" loading="lazy">
    </div>
    <div class="flex flex-col justify-center flex-1 min-w-0">
        <h3 class="font-semibold text-gray-900 truncate">{{ $restaurant->name }}</h3>
        <div class="flex items-center gap-3 mt-1 text-sm text-gray-500">
            <div class="flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="#FBBF24" stroke="#FBBF24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                <span class="font-medium text-gray-700">{{ $restaurant->rating }}</span>
            </div>
            <div class="flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <span>{{ $restaurant->delivery_time }} мин</span>
            </div>
        </div>
        <div class="flex items-center gap-1 mt-1 text-sm text-gray-500">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
            <span class="truncate">{{ $restaurant->categories->pluck('name')->join(' • ') }}</span>
        </div>
        @if($restaurant->delivery_fee == 0)
            <span class="mt-2 text-xs font-medium text-green-600">Бесплатная доставка</span>
        @else
            <span class="mt-2 text-xs text-gray-500">Доставка {{ $restaurant->delivery_fee }} ₽</span>
        @endif
    </div>
</a>