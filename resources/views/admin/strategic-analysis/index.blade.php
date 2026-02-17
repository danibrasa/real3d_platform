<x-app-layout>
    <x-slot name="title">Analisis Estrategico</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Analisis Estrategico</h2>
            <a href="{{ route('admin.strategic-analysis.pdf') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Descargar PDF
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            {{-- Table of contents --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                    Indice
                </h3>
                <nav class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <a href="#section-1" class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50 text-sm text-gray-600 hover:text-cyan-600 transition-colors">
                        <span class="flex-shrink-0 w-6 h-6 rounded-full bg-cyan-100 text-cyan-700 text-xs font-bold flex items-center justify-center">1</span>
                        Estado Actual de la Plataforma
                    </a>
                    <a href="#section-2" class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50 text-sm text-gray-600 hover:text-cyan-600 transition-colors">
                        <span class="flex-shrink-0 w-6 h-6 rounded-full bg-cyan-100 text-cyan-700 text-xs font-bold flex items-center justify-center">2</span>
                        Analisis Competitivo
                    </a>
                    <a href="#section-3" class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50 text-sm text-gray-600 hover:text-cyan-600 transition-colors">
                        <span class="flex-shrink-0 w-6 h-6 rounded-full bg-cyan-100 text-cyan-700 text-xs font-bold flex items-center justify-center">3</span>
                        Tendencias Proptech 2025-2026
                    </a>
                    <a href="#section-4" class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50 text-sm text-gray-600 hover:text-cyan-600 transition-colors">
                        <span class="flex-shrink-0 w-6 h-6 rounded-full bg-cyan-100 text-cyan-700 text-xs font-bold flex items-center justify-center">4</span>
                        Roadmap Estrategico de Mejoras
                    </a>
                    <a href="#section-5" class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50 text-sm text-gray-600 hover:text-cyan-600 transition-colors">
                        <span class="flex-shrink-0 w-6 h-6 rounded-full bg-cyan-100 text-cyan-700 text-xs font-bold flex items-center justify-center">5</span>
                        Posicionamiento Competitivo
                    </a>
                    <a href="#section-6" class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50 text-sm text-gray-600 hover:text-cyan-600 transition-colors">
                        <span class="flex-shrink-0 w-6 h-6 rounded-full bg-cyan-100 text-cyan-700 text-xs font-bold flex items-center justify-center">6</span>
                        Riesgos y Mitigacion
                    </a>
                    <a href="#section-7" class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50 text-sm text-gray-600 hover:text-cyan-600 transition-colors">
                        <span class="flex-shrink-0 w-6 h-6 rounded-full bg-cyan-100 text-cyan-700 text-xs font-bold flex items-center justify-center">7</span>
                        Metricas de Exito
                    </a>
                    <a href="#section-8" class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50 text-sm text-gray-600 hover:text-cyan-600 transition-colors">
                        <span class="flex-shrink-0 w-6 h-6 rounded-full bg-cyan-100 text-cyan-700 text-xs font-bold flex items-center justify-center">8</span>
                        Conclusion
                    </a>
                </nav>
            </div>

            {{-- Document content --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6 sm:p-10">
                <article class="prose prose-lg max-w-none
                    prose-headings:text-gray-900
                    prose-h1:text-3xl prose-h1:font-bold prose-h1:text-center prose-h1:mb-2 prose-h1:pb-4 prose-h1:border-b prose-h1:border-gray-200
                    prose-h2:text-2xl prose-h2:font-bold prose-h2:text-cyan-700 prose-h2:mt-12 prose-h2:mb-6 prose-h2:pb-2 prose-h2:border-b prose-h2:border-cyan-100
                    prose-h3:text-xl prose-h3:font-semibold prose-h3:text-gray-800 prose-h3:mt-8
                    prose-h4:text-lg prose-h4:font-semibold prose-h4:text-gray-700
                    prose-p:text-gray-600 prose-p:leading-relaxed
                    prose-strong:text-gray-800
                    prose-a:text-cyan-600 prose-a:no-underline hover:prose-a:underline
                    prose-table:text-sm
                    prose-th:bg-gray-50 prose-th:text-gray-700 prose-th:font-semibold prose-th:px-4 prose-th:py-3 prose-th:text-left
                    prose-td:px-4 prose-td:py-2.5 prose-td:text-gray-600 prose-td:border-gray-200
                    prose-tr:border-b prose-tr:border-gray-100
                    prose-blockquote:border-cyan-500 prose-blockquote:bg-cyan-50 prose-blockquote:py-1 prose-blockquote:px-6 prose-blockquote:rounded-r-lg prose-blockquote:not-italic prose-blockquote:text-gray-700
                    prose-li:text-gray-600
                    prose-hr:border-gray-200 prose-hr:my-8
                ">
                    {!! $html !!}
                </article>
            </div>

            {{-- Back to top + PDF buttons --}}
            <div class="mt-6 flex items-center justify-between">
                <a href="#" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-cyan-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                    Volver arriba
                </a>
                <a href="{{ route('admin.strategic-analysis.pdf') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Descargar PDF
                </a>
            </div>
        </div>
    </div>

    @push('head')
    <style>
        /* Section anchors for TOC */
        .prose h2 { scroll-margin-top: 5rem; }
        .prose h2:nth-of-type(1) { counter-reset: none; }

        /* Add IDs to h2 for anchor links */
        .prose h2:nth-of-type(1)::before { content: ""; }
        .prose h2:nth-of-type(2)::before { content: ""; }

        /* Table styling enhancements */
        .prose table { border-collapse: collapse; width: 100%; border-radius: 0.5rem; overflow: hidden; border: 1px solid #e5e7eb; }
        .prose thead tr { border-bottom: 2px solid #d1d5db; }
        .prose tbody tr:hover { background-color: #f9fafb; }

        /* Blockquote styling */
        .prose blockquote p { margin: 0.5rem 0; }

        /* Bold text in tables for emphasis */
        .prose td strong { color: #0891b2; }
    </style>
    @endpush

    <script>
        // Add IDs to h2 elements for anchor navigation
        document.addEventListener('DOMContentLoaded', function() {
            const headings = document.querySelectorAll('.prose h2');
            headings.forEach((h, i) => {
                h.id = 'section-' + (i + 1);
            });
        });
    </script>
</x-app-layout>
