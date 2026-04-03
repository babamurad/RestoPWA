@props(['showSearch' => true, 'showProfile' => true])

<header class="sticky top-0 z-40 bg-white/95 backdrop-blur-sm border-b border-gray-100">
    <div class="flex items-center gap-3 px-4 h-14">
        <div class="flex items-center gap-2">
            <div class="flex items-center justify-center w-8 h-8 bg-orange-500 rounded-lg">
                <span class="text-white font-bold text-sm">R</span>
            </div>
            <span class="font-bold text-xl gradient-text">RestoPWA</span>
        </div>
        @if($showSearch)
            <button onclick="showSearch()" class="flex-1 flex items-center gap-2 px-4 py-2 bg-gray-100 rounded-full text-gray-500 hover:bg-gray-200 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <span class="text-sm">Найти ресторан...</span>
            </button>
        @endif
        @if($showProfile)
            <button class="flex items-center justify-center w-10 h-10 rounded-full hover:bg-gray-100 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-700"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </button>
        @endif
    </div>
</header>

<div class="flex items-center gap-2 px-4 py-2 bg-orange-50 border-b border-orange-100">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-orange-500"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
    <span class="text-sm text-orange-700 truncate">{{ $address ?? 'ул. Ленина, 15, кв. 42' }}</span>
</div>