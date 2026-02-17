<x-guest-layout>
    <div class="text-center mb-6">
        <h2 class="text-xl font-bold text-gray-800">{{ app()->getLocale() === 'en' ? 'Register Your Real Estate Company' : 'Registra tu Inmobiliaria' }}</h2>
        <p class="text-sm text-gray-500 mt-1">{{ app()->getLocale() === 'en' ? 'Start showcasing your projects in 3D' : 'Comienza a mostrar tus proyectos en 3D' }}</p>
    </div>

    <form method="POST" action="{{ route('register.business') }}">
        @csrf

        <div>
            <x-input-label for="name" :value="app()->getLocale() === 'en' ? 'Full Name' : 'Nombre Completo'" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-6">
            <button type="submit" class="w-full flex justify-center py-2.5 px-4 bg-emerald-600 text-white rounded-md font-semibold text-sm hover:bg-emerald-700 transition">
                {{ app()->getLocale() === 'en' ? 'Create Account' : 'Crear Cuenta' }}
            </button>
        </div>

        <div class="mt-4 text-center text-sm text-gray-500">
            <a href="{{ route('login') }}" class="text-emerald-600 hover:underline">{{ __('Already registered?') }}</a>
            <span class="mx-2">|</span>
            <a href="{{ route('register') }}" class="text-gray-500 hover:underline">{{ app()->getLocale() === 'en' ? 'Register as buyer' : 'Registrarse como comprador' }}</a>
        </div>
    </form>
</x-guest-layout>
