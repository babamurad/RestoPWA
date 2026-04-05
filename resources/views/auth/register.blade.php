<x-layouts.app>
    <x-slot:title>Регистрация - RestoPWA</x-slot:title>

    <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <h1 class="text-center text-3xl font-black text-gray-900">RestoPWA</h1>
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
                        <label for="phone" class="block text-sm font-medium text-gray-700">Телефон <span class="text-gray-400">(необязательно)</span></label>
                        <div class="mt-1">
                            <input id="phone" name="phone" type="tel"
                                   value="{{ old('phone') }}"
                                   placeholder="+7 (999) 123-45-67"
                                   class="appearance-none block w-full px-3 py-3 border border-gray-300 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 sm:text-sm">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Пароль</label>
                        <div class="mt-1">
                            <input id="password" name="password" type="password" required
                                   class="appearance-none block w-full px-3 py-3 border border-gray-300 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 sm:text-sm @error('password') border-red-500 @enderror">
                        </div>
                        @error('password')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">Минимум 8 символов</p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Подтверждение пароля</label>
                        <div class="mt-1">
                            <input id="password_confirmation" name="password_confirmation" type="password" required
                                   class="appearance-none block w-full px-3 py-3 border border-gray-300 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 sm:text-sm">
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
    </div>
</x-layouts.app>
