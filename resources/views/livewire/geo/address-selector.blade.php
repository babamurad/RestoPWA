<div x-data="{
    searchQuery: @entangle('address'),
    showSuggestions: false,
    debounceTimer: null,
    search() {
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => {
            $wire.searchAddress(this.searchQuery);
            this.showSuggestions = true;
        }, 300);
    },
    select(index) {
        this.showSuggestions = false;
        $wire.selectAddress(index);
    }
}" class="space-y-3">
    
    <div class="relative">
        <div class="flex gap-2">
            <div class="flex-1 relative">
                <input 
                    type="text" 
                    x-model="searchQuery"
                    @input="search()"
                    @focus="showSuggestions = @js(!empty($entangle('suggestions'))))"
                    @blur="setTimeout(() => showSuggestions = false, 200)"
                    placeholder="Введите адрес доставки"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                >
                
                <div 
                    x-show="showSuggestions && @js(!empty($suggestions))"
                    class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto"
                >
                    @foreach($suggestions as $index => $suggestion)
                        <button 
                            type="button"
                            @click="select({{ $index }})"
                            class="w-full px-4 py-3 text-left hover:bg-gray-50 border-b border-gray-100 last:border-b-0 transition-colors"
                        >
                            <p class="text-sm font-medium text-gray-900">{{ $suggestion['address'] }}</p>
                            <p class="text-xs text-gray-500">{{ $suggestion['kind'] }}</p>
                        </button>
                    @endforeach
                </div>
            </div>
            
            <button 
                type="button"
                wire:click="detectLocation"
                wire:loading.attr="disabled"
                class="px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
            >
                <svg wire:loading wire:target="detectLocation" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg wire:loading.remove wire:target="detectLocation" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </button>
        </div>
    </div>

    @if($error)
        <div class="flex items-center gap-2 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
            <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <span>{{ $error }}</span>
        </div>
    @endif

    @if($isInDeliveryZone && $address)
        <div class="flex items-center gap-2 p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
            <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span>Адрес в зоне доставки</span>
        </div>
    @endif
</div>
