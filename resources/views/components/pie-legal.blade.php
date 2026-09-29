@props(['oscuro' => false])

{{-- Los tres enlaces legales y quien responde. Va en todos los pies: quien
     mira un visor sin cuenta tambien tiene derecho a saber quien le trata los
     datos, y la aceptacion del registro enlaza aqui. --}}
<div {{ $attributes->merge(['class' => 'text-xs '.($oscuro ? 'text-slate-500' : 'text-gray-500')]) }}>
    <span>&copy; {{ date('Y') }} {{ config('legal.responsable') }} · {{ config('legal.marca') }}</span>
    <span class="mx-1" aria-hidden="true">·</span>
    @foreach (\App\Http\Controllers\LegalController::PAGINAS as $pagina)
        <a href="{{ route('legal.'.$pagina) }}" class="hover:underline {{ $oscuro ? 'hover:text-slate-300' : 'hover:text-gray-800' }}">{{ __('legal.'.$pagina) }}</a>@if (! $loop->last)<span class="mx-1" aria-hidden="true">·</span>@endif
    @endforeach
</div>
