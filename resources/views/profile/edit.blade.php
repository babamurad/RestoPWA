<x-layouts.app>
    <x-slot:title>Профиль - RestoPWA</x-slot:title>

    <div class="bg-gray-50 min-h-screen pb-24">
    <div class="max-w-lg mx-auto md:max-w-4xl lg:max-w-6xl bg-white min-h-screen shadow-xl relative md:shadow-none md:bg-transparent">
        <div class="md:py-10" x-data="{ 
            isEditing: false,
            name: '{{ $user->name ?? 'Гость' }}',
            email: '{{ $user->email ?? '' }}'
        }">
            
            {{-- Desktop Title --}}
            <div class="hidden md:block mb-8 px-4">
                <h1 class="text-3xl font-bold text-gray-900">Профиль</h1>
            </div>

            <div class="md:grid md:grid-cols-3 md:gap-8 px-4">
                {{-- Left Column: Profile Card & Stats --}}
                <div class="col-span-1 space-y-6">
                    {{-- Profile Header/Card --}}
                    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
                        <div class="p-6 text-center">
                            <div class="relative inline-block group mb-4">
                                <div class="w-24 h-24 md:w-32 md:h-32 mx-auto rounded-full overflow-hidden border-4 border-orange-100 shadow-md">
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name ?? 'Guest') }}&background=FF6B35&color=fff&size=200" 
                                         class="w-full h-full object-cover">
                                </div>
                                <button class="absolute bottom-1 right-1 w-8 h-8 bg-orange-500 text-white rounded-full flex items-center justify-center shadow-lg border-2 border-white hover:bg-orange-600 transition-all active:scale-90">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                                </button>
                            </div>
                            
                            <h2 class="text-xl font-bold text-gray-900 leading-tight truncate px-2" x-text="name"></h2>
                            <p class="text-sm font-medium text-gray-400 mt-1 flex items-center justify-center gap-1.5 px-2 truncate">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                <span x-text="email"></span>
                            </p>
                        </div>
                        
                        {{-- Stats Grid inside card --}}
                        <div class="grid grid-cols-3 border-t border-gray-50 bg-gray-50/50">
                            <div class="p-4 text-center border-r border-gray-50">
                                <p class="text-lg font-bold text-gray-900 leading-none mb-1">4.9</p>
                                <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest">Рейтинг</p>
                            </div>
                            <div class="p-4 text-center border-r border-gray-50">
                                <p class="text-lg font-bold text-gray-900 leading-none mb-1">{{ count($user->orders ?? []) }}</p>
                                <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest">Заказы</p>
                            </div>
                            <div class="p-4 text-center">
                                <p class="text-lg font-bold text-gray-900 leading-none mb-1">2</p>
                                <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest">Карты</p>
                            </div>
                        </div>
                    </div>

                    {{-- Info Section (Desktop) --}}
                    <div class="hidden md:block bg-orange-50 rounded-2xl p-5 border-2 border-dashed border-orange-100 text-center">
                        <p class="text-sm font-medium text-orange-700 leading-relaxed italic">
                            Вы с нами уже более 6 месяцев.<br>
                            <span class="text-orange-600 font-bold not-italic">Спасибо за доверие!</span>
                        </p>
                    </div>
                </div>

                {{-- Right Column: Settings & Actions --}}
                <div class="col-span-2 space-y-6 mt-6 md:mt-0">
                    {{-- Account Settings Grid --}}
                    <section class="bg-white rounded-3xl border border-gray-100 overflow-hidden shadow-sm">
                        <div class="grid grid-cols-1 sm:grid-cols-2 divide-y divide-gray-50 sm:divide-y-0 sm:divide-x">
                            <button class="flex items-center gap-4 p-5 hover:bg-gray-50 transition-all group">
                                <div class="w-12 h-12 bg-gray-50 rounded-2xl flex items-center justify-center text-gray-400 group-hover:text-orange-500 group-hover:bg-white transition-all shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                </div>
                                <div class="flex-1 text-left">
                                    <p class="font-bold text-gray-900 text-base">Мои адреса</p>
                                    <p class="text-[10px] text-gray-400 font-medium font-inter">Управление точками доставки</p>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-gray-300 group-hover:translate-x-1 transition-transform"><path d="m9 18 6-6-6-6"/></svg>
                            </button>
                            <button class="flex items-center gap-4 p-5 hover:bg-gray-50 transition-all group">
                                <div class="w-12 h-12 bg-gray-50 rounded-2xl flex items-center justify-center text-gray-400 group-hover:text-orange-500 group-hover:bg-white transition-all shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                                </div>
                                <div class="flex-1 text-left">
                                    <p class="font-bold text-gray-900 text-base">Способы оплаты</p>
                                    <p class="text-[10px] text-gray-400 font-medium font-inter">Ваши карты и кошельки</p>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-gray-300 group-hover:translate-x-1 transition-transform"><path d="m9 18 6-6-6-6"/></svg>
                            </button>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 divide-y divide-gray-50 sm:divide-y-0 sm:divide-x border-t border-gray-50">
                            <button class="flex items-center gap-4 p-5 hover:bg-gray-50 transition-all group">
                                <div class="w-12 h-12 bg-gray-50 rounded-2xl flex items-center justify-center text-gray-400 group-hover:text-orange-500 group-hover:bg-white transition-all shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                                </div>
                                <div class="flex-1 text-left relative">
                                    <p class="font-bold text-gray-900 text-base">Уведомления</p>
                                    <p class="text-[10px] text-gray-400 font-medium font-inter">Настройка оповещений</p>
                                    <div class="absolute right-0 top-0 px-2 py-0.5 bg-orange-500 text-white text-[9px] font-bold rounded-full">3</div>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-gray-300 group-hover:translate-x-1 transition-transform"><path d="m9 18 6-6-6-6"/></svg>
                            </button>
                            <button class="flex items-center gap-4 p-5 hover:bg-gray-50 transition-all group">
                                <div class="w-12 h-12 bg-gray-50 rounded-2xl flex items-center justify-center text-gray-400 group-hover:text-orange-500 group-hover:bg-white transition-all shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 1 1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
                                </div>
                                <div class="flex-1 text-left">
                                    <p class="font-bold text-gray-900 text-base">PWA Настройки</p>
                                    <p class="text-[10px] text-gray-400 font-medium font-inter">Офлайн и кэширование</p>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-gray-300 group-hover:translate-x-1 transition-transform"><path d="m9 18 6-6-6-6"/></svg>
                            </button>
                        </div>
                    </section>

                    {{-- Actions --}}
                    <div class="flex gap-4 pt-4">
                        <form action="{{ route('logout') }}" method="POST" class="flex-1">
                            @csrf
                            <button type="submit" class="w-full py-4 text-red-500 font-bold rounded-2xl hover:bg-red-50 transition-all border border-transparent hover:border-red-100 flex items-center justify-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                                Выйти из аккаунта
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <x-bottom-nav active="profile" />
    </div>

            <x-bottom-nav active="profile" />

        </div>
    </div>
</x-layouts.app>
