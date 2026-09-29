<x-portal-layout>
    <x-slot name="title">{{ $titulo }}</x-slot>
    <x-slot name="metaDescription">{{ __('legal.meta', ['pagina' => $titulo, 'marca' => config('legal.marca')]) }}</x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-12 lg:py-16">
        <p class="text-xs uppercase tracking-wider text-gray-500 mb-2">{{ config('legal.marca') }}</p>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $titulo }}</h1>
        <p class="text-sm text-gray-500 mb-8">{{ __('legal.version', ['fecha' => config('legal.version')]) }}</p>

        <nav class="flex flex-wrap gap-4 text-sm mb-10" aria-label="{{ __('legal.otras') }}">
            @foreach (\App\Http\Controllers\LegalController::PAGINAS as $otra)
                <a href="{{ route('legal.'.$otra) }}"
                   class="{{ $otra === $pagina ? 'text-gray-900 font-semibold' : 'text-cyan-700 hover:underline' }}">{{ __('legal.'.$otra) }}</a>
            @endforeach
        </nav>

        <article class="prose prose-gray max-w-none text-gray-800 leading-relaxed [&_h2]:text-xl [&_h2]:font-semibold [&_h2]:mt-8 [&_h2]:mb-3 [&_p]:mb-3 [&_ul]:list-disc [&_ul]:pl-6 [&_ul]:mb-3 [&_li]:mb-1">
            @include($cuerpo, [
                'responsable' => config('legal.responsable'),
                'marca' => config('legal.marca'),
                'correo' => config('legal.correo'),
                'domicilio' => config('legal.domicilio'),
                'registro' => config('legal.registro'),
            ])
        </article>
    </div>
</x-portal-layout>
