<x-guest-layout>
    <h1 class="text-lg font-semibold text-gray-900">{{ __('agentes.ya_no_vale') }}</h1>
    <p class="mt-2 text-sm text-gray-600">{{ __('agentes.pide_reenvio', ['dias' => \App\Support\Agentes\Invitacion::DIAS_DE_VIDA]) }}</p>
    <p class="mt-4 text-sm"><a href="{{ route('login') }}" class="underline text-gray-600 hover:text-gray-900">{{ __('agentes.ya_tengo_cuenta') }}</a></p>
</x-guest-layout>
