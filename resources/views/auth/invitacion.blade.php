<x-guest-layout>
    <h1 class="text-lg font-semibold text-gray-900">{{ __('agentes.titulo', ['empresa' => $empresa]) }}</h1>
    <p class="mt-2 text-sm text-gray-600">{{ __('agentes.elige_clave', ['correo' => $agente->email]) }}</p>

    <form method="POST" action="{{ route('invitacion.aceptar', $token) }}" class="mt-4">
        @csrf

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full bg-gray-50" type="text" name="name" :value="$agente->name" disabled />
        </div>

        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full bg-gray-50" type="email" name="email" :value="$agente->email" disabled />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autofocus autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
        </div>

        <div class="mt-4">
            <label for="acepto" class="flex items-start gap-2 text-sm text-gray-700">
                <input id="acepto" name="acepto" type="checkbox" value="1" required
                       class="mt-1 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" {{ old('acepto') ? 'checked' : '' }}>
                <span>{!! __('legal.acepto', [
                    'condiciones' => '<a href="'.route('legal.condiciones').'" target="_blank" rel="noopener" class="underline">'.__('legal.condiciones').'</a>',
                    'privacidad' => '<a href="'.route('legal.privacidad').'" target="_blank" rel="noopener" class="underline">'.__('legal.privacidad').'</a>',
                ]) !!}</span>
            </label>
            <x-input-error :messages="$errors->get('acepto')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-6">
            <x-primary-button>{{ __('agentes.aceptar') }}</x-primary-button>
        </div>
    </form>
</x-guest-layout>
