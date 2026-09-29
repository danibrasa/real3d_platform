@props(['proyecto' => null])

{{-- El aviso de cookies analiticas. Solo se pinta donde hay analiticas que
     cargar: las cookies necesarias no piden permiso, y un aviso en cada
     pagina para nada es el que todo el mundo cierra sin leer. Las analiticas
     no se cargan hasta que se pulsa aceptar (window.cargarAnaliticas la
     define la pagina que las tiene). --}}
<div id="aviso-cookies" hidden
     class="fixed bottom-4 left-4 right-4 sm:left-auto sm:max-w-md z-50 bg-white text-gray-800 rounded-xl shadow-2xl border border-gray-200 p-4 text-sm"
     role="dialog" aria-live="polite" aria-label="{{ __('legal.cookies') }}">
    <p class="mb-3">
        {{ app()->getLocale() === 'en'
            ? 'This viewer can use analytics cookies so the developer knows what visitors look at. Only if you accept.'
            : 'Este visor puede usar cookies analíticas para que la promotora sepa qué miran los visitantes. Solo si las aceptas.' }}
        <a href="{{ route('legal.cookies') }}" class="underline text-cyan-700" target="_blank" rel="noopener">{{ __('legal.cookies') }}</a>
    </p>
    <div class="flex gap-2 justify-end">
        <button type="button" data-cookies="no" class="px-3 py-1.5 rounded-md border border-gray-300 hover:bg-gray-50">
            {{ app()->getLocale() === 'en' ? 'Only necessary' : 'Solo necesarias' }}
        </button>
        <button type="button" data-cookies="si" class="px-3 py-1.5 rounded-md bg-emerald-600 text-white hover:bg-emerald-700">
            {{ app()->getLocale() === 'en' ? 'Accept' : 'Aceptar' }}
        </button>
    </div>
</div>
<script>
(function () {
    var aviso = document.getElementById('aviso-cookies');
    var clave = 'cookies_analiticas';
    var decision = null;
    try { decision = localStorage.getItem(clave); } catch (e) {}

    if (decision === 'si' && typeof window.cargarAnaliticas === 'function') {
        window.cargarAnaliticas();
        return;
    }
    if (decision === 'no') return;

    aviso.hidden = false;
    aviso.querySelectorAll('[data-cookies]').forEach(function (b) {
        b.addEventListener('click', function () {
            try { localStorage.setItem(clave, b.dataset.cookies); } catch (e) {}
            aviso.hidden = true;
            if (b.dataset.cookies === 'si' && typeof window.cargarAnaliticas === 'function') {
                window.cargarAnaliticas();
            }
        });
    });
})();
</script>
