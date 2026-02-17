<x-guest-layout>
    <div class="text-center mb-6">
        {{-- Step indicator --}}
        <div class="flex items-center justify-center gap-2 mb-4">
            <div class="w-8 h-8 rounded-full bg-emerald-500 text-white text-sm font-bold flex items-center justify-center">1</div>
            <div class="w-8 h-1 bg-gray-200 rounded"></div>
            <div class="w-8 h-8 rounded-full bg-emerald-500 text-white text-sm font-bold flex items-center justify-center">2</div>
            <div class="w-8 h-1 bg-gray-200 rounded"></div>
            <div class="w-8 h-8 rounded-full bg-gray-200 text-gray-400 text-sm font-bold flex items-center justify-center">3</div>
        </div>
        <h2 class="text-xl font-bold text-gray-800">{{ app()->getLocale() === 'en' ? 'Tell us about your company' : 'Cuentanos sobre tu empresa' }}</h2>
    </div>

    <form method="POST" action="{{ route('onboarding.company.store') }}">
        @csrf

        <div>
            <x-input-label for="company_name" :value="__('billing.company_name')" />
            <x-text-input id="company_name" class="block mt-1 w-full" type="text" name="company_name" :value="old('company_name')" required autofocus />
            <x-input-error :messages="$errors->get('company_name')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="phone" :value="__('billing.phone')" />
            <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone" :value="old('phone')" placeholder="+1 (809) 555-0123" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="country" :value="__('billing.country')" />
            <select name="country" id="country" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                <option value="DO" {{ old('country') === 'DO' ? 'selected' : '' }}>Republica Dominicana</option>
                <option value="MX" {{ old('country') === 'MX' ? 'selected' : '' }}>Mexico</option>
                <option value="CO" {{ old('country') === 'CO' ? 'selected' : '' }}>Colombia</option>
                <option value="PA" {{ old('country') === 'PA' ? 'selected' : '' }}>Panama</option>
                <option value="CR" {{ old('country') === 'CR' ? 'selected' : '' }}>Costa Rica</option>
                <option value="US" {{ old('country') === 'US' ? 'selected' : '' }}>United States</option>
                <option value="ES" {{ old('country') === 'ES' ? 'selected' : '' }}>Espana</option>
            </select>
            <x-input-error :messages="$errors->get('country')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="city" :value="__('billing.city')" />
            <x-text-input id="city" class="block mt-1 w-full" type="text" name="city" :value="old('city')" />
            <x-input-error :messages="$errors->get('city')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="website" value="Website" />
            <x-text-input id="website" class="block mt-1 w-full" type="url" name="website" :value="old('website')" placeholder="https://" />
            <x-input-error :messages="$errors->get('website')" class="mt-2" />
        </div>

        <div class="mt-6">
            <button type="submit" class="w-full flex justify-center py-2.5 px-4 bg-emerald-600 text-white rounded-md font-semibold text-sm hover:bg-emerald-700 transition">
                {{ app()->getLocale() === 'en' ? 'Continue' : 'Continuar' }}
            </button>
        </div>
    </form>
</x-guest-layout>
