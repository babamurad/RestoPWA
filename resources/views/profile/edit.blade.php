<x-layouts.app>
    <x-slot:title>Профиль - RestoPWA</x-slot:title>

    <div class="bg-gray-50 min-h-screen pb-24">
        <div class="max-w-lg mx-auto bg-white min-h-screen shadow-xl relative" x-data="{ 
            isEditing: false,
            name: '{{ $user->name ?? 'Гость' }}',
            email: '{{ $user->email ?? '' }}'
        }">
            
            {{-- Profile Header --}}
            <div class="bg-white border-b border-gray-100">
                <div class="px-4 py-8">
                    <div class="flex items-center gap-5">
                        <div class="relative group">
                            <div class="w-20 h-20 rounded-full overflow-hidden border-4 border-orange-100 shadow-sm">
                                <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name ?? 'Guest') }}&background=FF6B35&color=fff&size=200" 
                                     class="w-full h-full object-cover">
                            </div>
                            <button class="absolute bottom-0 right-0 w-7 h-7 bg-orange-500 text-white rounded-full flex items-center justify-center shadow-lg border-2 border-white hover:bg-orange-600 transition-all active:scale-90">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                            </button>
                        </div>
                        
                        <div class="flex-1 min-w-0">
                            <h1 class="text-xl font-bold text-gray-900 leading-tight truncate" x-text="name"></h1>
                            <p class="text-sm font-medium text-gray-400 mt-0.5 flex items-center gap-1.5 truncate">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                <span x-text="email"></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <main class="px-4 py-6 space-y-6">
                
                {{-- Stats Grid --}}
                <div class="grid grid-cols-3 gap-3">
                    <div class="bg-white p-4 rounded-2xl border border-gray-100 text-center shadow-sm">
                        <div class="w-10 h-10 mx-auto bg-orange-50 rounded-xl flex items-center justify-center mb-2 text-orange-500">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        </div>
                        <p class="text-lg font-bold text-gray-900">4.9</p>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Рейтинг</p>
                    </div>
                    <div class="bg-white p-4 rounded-2xl border border-gray-100 text-center shadow-sm">
                        <div class="w-10 h-10 mx-auto bg-green-50 rounded-xl flex items-center justify-center mb-2 text-green-500">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                        </div>
                        <p class="text-lg font-bold text-gray-900">{{ count($user->orders ?? []) }}</p>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Заказов</p>
                    </div>
                    <div class="bg-white p-4 rounded-2xl border border-gray-100 text-center shadow-sm">
                        <div class="w-10 h-10 mx-auto bg-blue-50 rounded-xl flex items-center justify-center mb-2 text-blue-500">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                        </div>
                        <p class="text-lg font-bold text-gray-900">2</p>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Карты</p>
                    </div>
                </div>

                {{-- Account Settings --}}
                <section class="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm shadow-gray-200/20">
                    <button class="w-full flex items-center gap-3 p-4 hover:bg-gray-50 transition-colors border-b border-gray-50 group">
                        <div class="w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center text-gray-400 group-hover:text-orange-500 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                        </div>
                        <span class="flex-1 text-left font-bold text-gray-900 text-sm">Мои адреса</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-gray-300 group-hover:translate-x-1 transition-transform"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                    <button class="w-full flex items-center gap-3 p-4 hover:bg-gray-50 transition-colors border-b border-gray-50 group">
                        <div class="w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center text-gray-400 group-hover:text-orange-500 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                        </div>
                        <span class="flex-1 text-left font-bold text-gray-900 text-sm">Способы оплаты</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-gray-300 group-hover:translate-x-1 transition-transform"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                    <button class="w-full flex items-center gap-3 p-4 hover:bg-gray-50 transition-colors border-b border-gray-50 group">
                        <div class="w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center text-gray-400 group-hover:text-orange-500 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                        </div>
                        <span class="flex-1 text-left font-bold text-gray-900 text-sm">Уведомления</span>
                        <div class="px-2 py-0.5 bg-orange-500 text-white text-[10px] font-bold rounded-full">3</div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-gray-300 group-hover:translate-x-1 transition-transform"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                    <button class="w-full flex items-center gap-3 p-4 hover:bg-gray-50 transition-colors group">
                        <div class="w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center text-gray-400 group-hover:text-orange-500 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 1 1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
                        </div>
                        <span class="flex-1 text-left font-bold text-gray-900 text-sm">Настройки PWA</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-gray-300 group-hover:translate-x-1 transition-transform"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                </section>

                {{-- Info Section --}}
                <section class="bg-gray-50 rounded-2xl p-5 border-2 border-dashed border-gray-200 text-center">
                    <p class="text-sm font-medium text-gray-500 leading-relaxed">
                        Вы с нами уже более 6 месяцев.<br>
                        <span class="text-orange-500 font-bold">Спасибо, что выбираете нас!</span>
                    </p>
                </section>

                {{-- Actions --}}
                <div class="space-y-3 pt-4">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full py-4 text-red-500 font-bold rounded-2xl hover:bg-red-50 transition-all touch-feedback active:scale-95">
                            Выйти из аккаунта
                        </button>
                    </form>
                </div>

            </main>

            <x-bottom-nav active="profile" />

        </div>
    </div>
</x-layouts.app>
