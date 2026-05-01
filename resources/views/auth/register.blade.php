<x-layouts.app>
    <x-slot:title>Регистрация - RestoPWA</x-slot:title>

    <x-header :showSearch="false" />

    <main class="flex-1 flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-8 pb-24">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <h2 class="mt-6 text-center text-2xl font-bold text-gray-900">Создание аккаунта</h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Уже есть аккаунт? <a href="{{ route('login') }}" class="font-medium text-orange-500 hover:text-orange-600">Войдите</a>
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow-xl sm:rounded-2xl sm:px-10">
                <form class="space-y-6" action="{{ route('register') }}" method="POST" x-data="{ loading: false }" @submit="loading = true">
                    @csrf

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Имя</label>
                        <div class="mt-1">
                            <input id="name" name="name" type="text" required autofocus
                                   value="{{ old('name') }}"
                                   class="appearance-none block w-full px-3 py-3 border border-gray-300 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 sm:text-sm @error('name') border-red-500 @enderror">
                        </div>
                        @error('name')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <div class="mt-1">
                            <input id="email" name="email" type="email" required
                                   value="{{ old('email') }}"
                                   class="appearance-none block w-full px-3 py-3 border border-gray-300 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 sm:text-sm @error('email') border-red-500 @enderror">
                        </div>
                        @error('email')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-gray-700">Номер телефона</label>
                            <input id="phone" name="phone" type="tel"
                                   x-mask="99999999"
                                   value="{{ old('phone', '') }}"
                                   placeholder="12345678"
                                   class="w-full p-4 bg-gray-50 border-none rounded-2xl text-sm font-bold focus:ring-2 focus:ring-orange-500/20 focus:bg-white transition-all @error('phone') ring-2 ring-red-500 @enderror">
                            @error('phone')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="text-[10px] text-gray-400 font-medium px-2">Нужен для связи курьера с вами</p>
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Пароль</label>
                        <div class="mt-1 relative" x-data="{ show: false }">
                            <input id="password" name="password" :type="show ? 'text' : 'password'" required
                                   class="appearance-none block w-full px-3 py-3 pr-10 border border-gray-300 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 sm:text-sm @error('password') border-red-500 @enderror">
                            <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 cursor-pointer">
                                <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg x-show="show" x-cloak xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">Минимум 8 символов</p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Подтверждение пароля</label>
                        <div class="mt-1 relative" x-data="{ show: false }">
                            <input id="password_confirmation" name="password_confirmation" :type="show ? 'text' : 'password'" required
                                   class="appearance-none block w-full px-3 py-3 pr-10 border border-gray-300 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 sm:text-sm">
                            <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 cursor-pointer">
                                <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg x-show="show" x-cloak xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>
                    </div>

                    <div>
                        <button type="submit" :disabled="loading"
                                class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-lg text-sm font-bold text-white bg-orange-500 hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                            <span x-show="!loading">Зарегистрироваться</span>
                            <span x-show="loading" class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Регистрация...
                            </span>
                        </button>
                    </div>
                </form>

                <p class="mt-6 text-center text-xs text-gray-500">
                    Регистрируясь, вы соглашаетесь с нашими
                    <a href="#" class="text-orange-500 hover:text-orange-600">Условиями использования</a>
                    и <a href="#" class="text-orange-500 hover:text-orange-600">Политикой конфиденциальности</a>
                </p>
            </div>
        </div>
    </main>

    <x-bottom-nav active="profile" />
</x-layouts.app>
