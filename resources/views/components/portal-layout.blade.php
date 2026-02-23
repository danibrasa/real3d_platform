<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@isset($title){{ $title }} | @endisset Real3D Properties</title>
    <meta name="description" content="@isset($metaDescription){{ $metaDescription }}@else {{ __('portal.meta_description_default') }} @endisset">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="alternate" type="text/markdown" href="/llms.txt" title="LLMs.txt">
    <link rel="mcp" href="/mcp" type="application/json">
    @stack('head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-900" x-data="{ mobileOpen: false }">

    {{-- Navbar --}}
    <nav class="bg-[#0a0a1e] text-white sticky top-0 z-50 shadow-lg shadow-black/20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                {{-- Logo --}}
                <a href="{{ route('portal.home') }}" class="flex items-center gap-2 group">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-cyan-500 to-blue-500 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1"/></svg>
                    </div>
                    <span class="text-lg font-bold bg-gradient-to-r from-cyan-400 to-blue-400 bg-clip-text text-transparent">Real3D</span>
                </a>

                {{-- Desktop Nav --}}
                <div class="hidden md:flex items-center gap-6">
                    <a href="{{ route('portal.home') }}" class="text-sm {{ request()->routeIs('portal.home') ? 'text-cyan-400 font-medium' : 'text-slate-400 hover:text-cyan-400' }} transition-colors">{{ __('portal.nav_home') }}</a>
                    <a href="{{ route('portal.search') }}" class="text-sm {{ request()->routeIs('portal.search') ? 'text-cyan-400 font-medium' : 'text-slate-400 hover:text-cyan-400' }} transition-colors">{{ __('portal.nav_search') }}</a>
                    <a href="{{ route('directory.index') }}" class="text-sm text-slate-400 hover:text-cyan-400 transition-colors">{{ __('portal.nav_developers') }}</a>
                    <a href="{{ route('blog.index') }}" class="text-sm {{ request()->routeIs('blog.*') ? 'text-cyan-400 font-medium' : 'text-slate-400 hover:text-cyan-400' }} transition-colors">Blog</a>
                </div>

                {{-- Desktop Right --}}
                <div class="hidden md:flex items-center gap-3">
                    <div class="flex items-center gap-1 mr-2">
                        <a href="?lang=es" class="text-xs px-1.5 py-0.5 rounded {{ app()->getLocale() === 'es' ? 'text-cyan-400 font-bold' : 'text-slate-500 hover:text-slate-300' }}">ES</a>
                        <span class="text-slate-600">|</span>
                        <a href="?lang=en" class="text-xs px-1.5 py-0.5 rounded {{ app()->getLocale() === 'en' ? 'text-cyan-400 font-bold' : 'text-slate-500 hover:text-slate-300' }}">EN</a>
                    </div>
                    <a href="{{ route('register.business') }}" class="btn-glow px-4 py-2 rounded-lg text-sm font-medium text-white">{{ __('portal.list_property') }}</a>
                </div>

                {{-- Mobile Hamburger --}}
                <button @click="mobileOpen = !mobileOpen" class="md:hidden p-2 text-slate-400 hover:text-white transition-colors">
                    <svg x-show="!mobileOpen" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg x-show="mobileOpen" x-cloak class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        {{-- Mobile Menu --}}
        <div x-show="mobileOpen" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="md:hidden bg-[#0a0a1e]/95 backdrop-blur-lg border-t border-white/5">
            <div class="px-4 py-4 space-y-3">
                <a @click="mobileOpen = false" href="{{ route('portal.home') }}" class="block text-sm text-slate-400 hover:text-cyan-400 py-2">{{ __('portal.nav_home') }}</a>
                <a @click="mobileOpen = false" href="{{ route('portal.search') }}" class="block text-sm text-slate-400 hover:text-cyan-400 py-2">{{ __('portal.nav_search') }}</a>
                <a @click="mobileOpen = false" href="{{ route('directory.index') }}" class="block text-sm text-slate-400 hover:text-cyan-400 py-2">{{ __('portal.nav_developers') }}</a>
                <a @click="mobileOpen = false" href="{{ route('blog.index') }}" class="block text-sm text-slate-400 hover:text-cyan-400 py-2">Blog</a>
                <div class="pt-3 border-t border-white/10 flex flex-col gap-2">
                    <a href="{{ route('register.business') }}" class="btn-glow px-4 py-2 rounded-lg text-sm font-medium text-white text-center">{{ __('portal.list_property') }}</a>
                </div>
                <div class="flex items-center gap-2 pt-2">
                    <a href="?lang=es" class="text-xs px-2 py-1 rounded {{ app()->getLocale() === 'es' ? 'text-cyan-400 font-bold' : 'text-slate-500' }}">ES</a>
                    <a href="?lang=en" class="text-xs px-2 py-1 rounded {{ app()->getLocale() === 'en' ? 'text-cyan-400 font-bold' : 'text-slate-500' }}">EN</a>
                </div>
            </div>
        </div>
    </nav>

    <main>{{ $slot }}</main>

    {{-- Footer --}}
    <footer class="bg-[#0a0a1e] text-white border-t border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid md:grid-cols-3 gap-8">
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-cyan-500 to-blue-500 flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1"/></svg>
                        </div>
                        <span class="font-bold bg-gradient-to-r from-cyan-400 to-blue-400 bg-clip-text text-transparent">Real3D</span>
                    </div>
                    <p class="text-sm text-slate-500">{{ __('portal.footer_tagline') }}</p>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-slate-300 mb-3">{{ __('portal.footer_explore') }}</h4>
                    <ul class="space-y-2">
                        <li><a href="{{ route('portal.home') }}" class="text-sm text-slate-500 hover:text-cyan-400 transition-colors">{{ __('portal.nav_home') }}</a></li>
                        <li><a href="{{ route('portal.search') }}" class="text-sm text-slate-500 hover:text-cyan-400 transition-colors">{{ __('portal.nav_search') }}</a></li>
                        <li><a href="{{ route('directory.index') }}" class="text-sm text-slate-500 hover:text-cyan-400 transition-colors">{{ __('portal.nav_developers') }}</a></li>
                        <li><a href="{{ route('blog.index') }}" class="text-sm text-slate-500 hover:text-cyan-400 transition-colors">Blog</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-slate-300 mb-3">{{ __('portal.footer_for_developers') }}</h4>
                    <ul class="space-y-2">
                        <li><a href="{{ url('/') }}" class="text-sm text-slate-500 hover:text-cyan-400 transition-colors">Real3D SaaS</a></li>
                        <li><a href="{{ route('register.business') }}" class="text-sm text-slate-500 hover:text-cyan-400 transition-colors">{{ __('portal.list_property') }}</a></li>
                    </ul>
                </div>
            </div>
            <div class="mt-8 pt-6 border-t border-white/5 text-center text-xs text-slate-600">
                &copy; {{ date('Y') }} Real3D. All rights reserved.
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
