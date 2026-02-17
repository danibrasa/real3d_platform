<x-app-layout>
    <x-slot name="title">{{ __('billing.my_company') }}</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('billing.my_company') }}</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">{{ session('success') }}</div>
            @endif

            <div class="bg-white rounded-lg shadow-sm border p-6">
                <form method="POST" action="{{ route('admin.company-profile.update') }}" enctype="multipart/form-data" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('billing.company_name') }} *</label>
                            <input type="text" name="company_name" value="{{ old('company_name', $profile->company_name) }}" required
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                            @error('company_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('billing.legal_name') }}</label>
                            <input type="text" name="legal_name" value="{{ old('legal_name', $profile->legal_name) }}"
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('billing.tax_id') }}</label>
                            <input type="text" name="tax_id" value="{{ old('tax_id', $profile->tax_id) }}" placeholder="RNC / NIF"
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('billing.phone') }}</label>
                            <input type="text" name="phone" value="{{ old('phone', $profile->phone) }}"
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Website</label>
                            <input type="url" name="website" value="{{ old('website', $profile->website) }}" placeholder="https://"
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('billing.country') }}</label>
                            <select name="country" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                                <option value="DO" {{ old('country', $profile->country) === 'DO' ? 'selected' : '' }}>Republica Dominicana</option>
                                <option value="MX" {{ old('country', $profile->country) === 'MX' ? 'selected' : '' }}>Mexico</option>
                                <option value="CO" {{ old('country', $profile->country) === 'CO' ? 'selected' : '' }}>Colombia</option>
                                <option value="PA" {{ old('country', $profile->country) === 'PA' ? 'selected' : '' }}>Panama</option>
                                <option value="CR" {{ old('country', $profile->country) === 'CR' ? 'selected' : '' }}>Costa Rica</option>
                                <option value="US" {{ old('country', $profile->country) === 'US' ? 'selected' : '' }}>United States</option>
                                <option value="ES" {{ old('country', $profile->country) === 'ES' ? 'selected' : '' }}>Espana</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('billing.city') }}</label>
                            <input type="text" name="city" value="{{ old('city', $profile->city) }}"
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('billing.address') }}</label>
                            <input type="text" name="address" value="{{ old('address', $profile->address) }}"
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('billing.description') }} (ES)</label>
                        <textarea name="description" rows="3" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500">{{ old('description', $profile->description) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('billing.description') }} (EN)</label>
                        <textarea name="description_en" rows="3" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500">{{ old('description_en', $profile->description_en) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Logo</label>
                        @if($profile->logo_path)
                            <div class="mb-2">
                                <img src="{{ Storage::url($profile->logo_path) }}" alt="Logo" class="h-16 w-16 rounded-lg object-cover">
                            </div>
                        @endif
                        <input type="file" name="logo" accept="image/*" class="text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                        @error('logo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="hidden" name="show_in_directory" value="0">
                        <input type="checkbox" name="show_in_directory" value="1" id="show_in_directory"
                               {{ old('show_in_directory', $profile->show_in_directory) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500">
                        <label for="show_in_directory" class="text-sm text-gray-700">{{ __('billing.show_in_directory') }}</label>
                    </div>

                    <div class="flex justify-end pt-4 border-t">
                        <button type="submit" class="px-6 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 transition">
                            {{ __('billing.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
