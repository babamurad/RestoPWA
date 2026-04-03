<x-layouts.app>
    <x-slot:title>Профиль - RestoPWA</x-slot:title>

    <div class="max-w-lg mx-auto bg-gray-50 min-h-screen shadow-xl relative pb-24" x-data="{ 
        isEditing: false,
        name: '{{ $user->name ?? 'Гость' }}',
        email: '{{ $user->email ?? '' }}'
    }">
        
        {{-- Profile Header --}}
        <div class="bg-white border-b border-gray-100 shadow-sm">
            <div class="px-4 py-8">
                <div class="flex items-center gap-5">
                    <div class="relative group">
                        <div class="w-24 h-24 rounded-full overflow-hidden border-4 border-orange-50 shadow-inner">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name ?? 'Guest') }}&background=FF6B35&color=fff&size=200" 
                                 class="w-full h-full object-cover">
                        </div>
                        <button class="absolute bottom-0 right-0 w-8 h-8 bg-orange-500 text-white rounded-full flex items-center justify-center shadow-lg border-2 border-white hover:bg-orange-600 transition-all active:scale-90">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                        </button>
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <h1 class="text-2xl font-black text-gray-900 leading-tight truncate" x-text="name"></h1>
                        <p class="text-sm font-bold text-gray-400 mt-1 flex items-center gap-1.5 truncate">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                            <span x-text="email"></span>
                        </p>
                        
                        <div class="flex items-center gap-2 mt-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-green-50 text-green-600 uppercase tracking-widest border border-green-100">Активен</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-orange-50 text-orange-600 uppercase tracking-widest border border-orange-100 italic">Premium</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <main class="px-4 py-6 space-y-6">
            
            {{-- Personal Info Section --}}
            <section class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm shadow-gray-200/50 animate-slide-up">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-black text-gray-900 uppercase tracking-tight">Личные данные</h2>
                    <button @click="isEditing = !isEditing" 
                            class="text-xs font-bold text-orange-500 uppercase tracking-wider bg-orange-50 px-3 py-1.5 rounded-xl hover:bg-orange-100 transition-all active:scale-95">
                        <span x-text="isEditing ? 'Сохранить' : 'Изменить'"></span>
                    </button>
                </div>
                
                <div class="space-y-4">
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest px-1">ФИО</label>
                        <input type="text" x-model="name" :disabled="!isEditing"
                               class="w-full bg-gray-50 border-gray-100 rounded-2xl px-4 py-3 text-sm font-semibold transition-all focus:bg-white focus:ring-2 focus:ring-orange-100 focus:border-orange-200 disabled:opacity-75">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest px-1">Email</label>
                        <input type="email" x-model="email" :disabled="!isEditing"
                               class="w-full bg-gray-50 border-gray-100 rounded-2xl px-4 py-3 text-sm font-semibold transition-all focus:bg-white focus:ring-2 focus:ring-orange-100 focus:border-orange-200 disabled:opacity-75">
                    </div>
                </div>
            </section>

            {{-- Quick Stats / Menu --}}
            <section class="grid grid-cols-2 gap-4 animate-slide-up" style="animation-delay: 0.1s">
                <a href="{{ route('orders.index') }}" class="flex flex-col items-center justify-center p-6 bg-white rounded-3xl border border-gray-100 shadow-sm card-hover group">
                    <div class="w-12 h-12 bg-blue-50 text-blue-500 rounded-2xl flex items-center justify-center mb-3 group-hover:bg-blue-500 group-hover:text-white transition-all duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                    </div>
                    <span class="font-bold text-gray-900 text-sm">Заказы</span>
                    <span class="text-[10px] text-gray-400 font-bold uppercase mt-1 tracking-widest">История</span>
                </a>
                <div class="flex flex-col items-center justify-center p-6 bg-white rounded-3xl border border-gray-100 shadow-sm card-hover group cursor-pointer">
                    <div class="w-12 h-12 bg-amber-50 text-amber-500 rounded-2xl flex items-center justify-center mb-3 group-hover:bg-amber-500 group-hover:text-white transition-all duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                    </div>
                    <span class="font-bold text-gray-900 text-sm">Адреса</span>
                    <span class="text-[10px] text-gray-400 font-bold uppercase mt-1 tracking-widest">3 локации</span>
                </div>
            </section>

            {{-- Settings and Actions --}}
            <section class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden animate-slide-up" style="animation-delay: 0.2s">
                <button class="w-full flex items-center gap-4 p-5 hover:bg-gray-50 transition-all border-b border-gray-50 group">
                    <div class="w-10 h-10 bg-gray-50 text-gray-400 rounded-xl flex items-center justify-center group-hover:text-orange-500 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.1a2 2 0 0 1-1-1.72v-.51a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
                    </div>
                    <span class="flex-1 text-left font-bold text-gray-900 text-sm">Настройки PWA</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-gray-300 group-hover:translate-x-1 transition-transform"><path d="m9 18 6-6-6-6"/></svg>
                </button>
                
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-4 p-5 hover:bg-red-50 transition-all text-red-500 group">
                        <div class="w-10 h-10 bg-red-50 text-red-400 rounded-xl flex items-center justify-center group-hover:bg-red-500 group-hover:text-white transition-all duration-300">
                             <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
                        </div>
                        <span class="font-bold text-sm">Выйти из аккаунта</span>
                    </button>
                </form>
            </section>

        </main>

        <x-bottom-nav active="profile" />

    </div>
</x-layouts.app>
