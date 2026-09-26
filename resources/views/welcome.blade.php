<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Real3D — Plataforma de Visualización Inmobiliaria 3D para Promotores</title>
    <meta name="description" content="Real3D es la plataforma SaaS para promotores inmobiliarios que combina modelos 3D interactivos, video 360° y gestión de unidades en tiempo real. Prueba 14 días gratis.">
    <meta name="keywords" content="visualizacion 3D, inmobiliaria, pre-construccion, Punta Cana, modelos 3D, video 360, real estate, Three.js, visor 3D">
    <link rel="canonical" href="{{ url('/') }}">
    <meta name="app-version" content="{{ \App\Support\Version::label() }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Real3D | El futuro del real estate en tres dimensiones">
    <meta property="og:description" content="Transforma tus proyectos de nueva construcción en experiencias 3D interactivas. Tus compradores exploran cada unidad antes de que exista. Plataforma SaaS para promotores en Latam.">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Real3D | El futuro del real estate en tres dimensiones">
    <meta name="twitter:description" content="Real3D: plataforma SaaS para promotores con modelos 3D interactivos, video 360° y gestión de unidades en tiempo real.">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- JSON-LD WebSite --}}
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => 'Real3D',
        'url' => url('/'),
        'description' => 'Plataforma SaaS para promotores: modelos 3D interactivos, video 360° y gestión de unidades en tiempo real.',
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => url('/projects') . '?q={search_term_string}',
            'query-input' => 'required name=search_term_string',
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>
</head>
<body class="bg-[#0a0a1e] text-white antialiased" x-data="{ mobileOpen: false, scrolled: false }" @scroll.window="scrolled = window.scrollY > 50">
<x-env-banner />

    {{-- Navbar --}}
    <nav class="fixed top-0 inset-x-0 z-50 transition-all duration-300"
         :class="scrolled ? 'bg-[#0a0a1e]/90 backdrop-blur-lg shadow-lg shadow-black/20' : 'bg-transparent'">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 lg:h-20">
                {{-- Logo --}}
                <a href="/" class="flex items-center gap-2 group">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-cyan-500 to-blue-500 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1"/></svg>
                    </div>
                    <span class="text-lg font-bold bg-gradient-to-r from-cyan-400 to-blue-400 bg-clip-text text-transparent">Real3D.io</span>
                </a>

                {{-- Desktop Nav --}}
                <div class="hidden md:flex items-center gap-8">
                    <a href="#features" class="text-sm text-slate-400 hover:text-cyan-400 transition-colors">Características</a>
                    <a href="#projects" class="text-sm text-slate-400 hover:text-cyan-400 transition-colors">Proyectos</a>
                    <a href="#pricing" class="text-sm text-slate-400 hover:text-cyan-400 transition-colors">Precios</a>
                    <a href="#tech" class="text-sm text-slate-400 hover:text-cyan-400 transition-colors">Tecnología</a>
                    <a href="#contact" class="text-sm text-slate-400 hover:text-cyan-400 transition-colors">Contacto</a>
                </div>

                {{-- Desktop Auth --}}
                <div class="hidden md:flex items-center gap-3">
                    @guest
                        <a href="{{ route('login') }}" class="btn-outline-cyan px-4 py-2 rounded-lg text-sm font-medium">{{ __('general.login') }}</a>
                        <a href="{{ route('register') }}" class="btn-glow px-4 py-2 rounded-lg text-sm font-medium text-white">{{ __('welcome.explore_projects') }}</a>
                    @else
                        @if(auth()->user()->hasAdminAccess())
                            <a href="{{ route('admin.dashboard') }}" class="btn-glow px-4 py-2 rounded-lg text-sm font-medium text-white">{{ __('general.admin_panel') }}</a>
                        @else
                            <a href="{{ route('viewer.index') }}" class="btn-glow px-4 py-2 rounded-lg text-sm font-medium text-white">{{ __('general.view_projects') }}</a>
                        @endif
                    @endguest
                    {{-- Language selector --}}
                    <div class="flex items-center gap-1 ml-2">
                        <a href="?lang=es" class="text-xs px-1.5 py-0.5 rounded {{ app()->getLocale() === 'es' ? 'text-cyan-400 font-bold' : 'text-slate-500 hover:text-slate-300' }}">ES</a>
                        <span class="text-slate-600">|</span>
                        <a href="?lang=en" class="text-xs px-1.5 py-0.5 rounded {{ app()->getLocale() === 'en' ? 'text-cyan-400 font-bold' : 'text-slate-500 hover:text-slate-300' }}">EN</a>
                    </div>
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
                <a @click="mobileOpen = false" href="#features" class="block text-sm text-slate-400 hover:text-cyan-400 py-2">Características</a>
                <a @click="mobileOpen = false" href="#projects" class="block text-sm text-slate-400 hover:text-cyan-400 py-2">Proyectos</a>
                <a @click="mobileOpen = false" href="#tech" class="block text-sm text-slate-400 hover:text-cyan-400 py-2">Tecnología</a>
                <a @click="mobileOpen = false" href="#contact" class="block text-sm text-slate-400 hover:text-cyan-400 py-2">Contacto</a>
                <div class="pt-3 border-t border-white/10 flex flex-col gap-2">
                    @guest
                        <a href="{{ route('login') }}" class="btn-outline-cyan px-4 py-2 rounded-lg text-sm font-medium text-center">Iniciar Sesion</a>
                        <a href="{{ route('register') }}" class="btn-glow px-4 py-2 rounded-lg text-sm font-medium text-white text-center">Comenzar</a>
                    @else
                        @if(auth()->user()->hasAdminAccess())
                            <a href="{{ route('admin.dashboard') }}" class="btn-glow px-4 py-2 rounded-lg text-sm font-medium text-white text-center">Panel Admin</a>
                        @else
                            <a href="{{ route('viewer.index') }}" class="btn-glow px-4 py-2 rounded-lg text-sm font-medium text-white text-center">Ver Proyectos</a>
                        @endif
                    @endguest
                </div>
            </div>
        </div>
    </nav>

    {{-- Hero Section --}}
    <section class="relative min-h-screen flex items-center overflow-hidden">
        {{-- Background --}}
        <div class="absolute inset-0 bg-gradient-to-b from-[#0a0a1e] via-[#0f172a] to-[#1a1a3e]"></div>
        <div class="absolute inset-0 dot-pattern"></div>

        {{-- Decorative gradient orbs --}}
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-cyan-500/10 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-blue-500/10 rounded-full blur-[120px]"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-32 lg:py-0">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                {{-- Text --}}
                <div>
                    <div class="animate-fade-in-up">
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-medium bg-cyan-500/10 text-cyan-400 border border-cyan-500/20 mb-6">
                            <span class="w-1.5 h-1.5 rounded-full bg-cyan-400 animate-pulse"></span>
                            Tecnologia Inmersiva 3D
                        </span>
                    </div>

                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold leading-tight mb-6 animate-fade-in-up-delay-1">
                        Presenta, impacta
                        <span class="bg-gradient-to-r from-cyan-400 via-blue-400 to-cyan-300 bg-clip-text text-transparent">y cierra la venta</span>
                    </h1>

                    <p class="text-lg text-slate-400 mb-8 max-w-lg animate-fade-in-up-delay-2">
                        Real3D es la única plataforma que combina modelos 3D interactivos, video 360° inmersivo y gestión de unidades en tiempo real. Todo lo que necesitas para vender nueva construcción — en una sola herramienta.
                    </p>

                    <p class="text-sm text-slate-400 italic mb-4 animate-fade-in-up-delay-2">Real estate. Real 3D.</p>
                    <div class="flex flex-wrap gap-4 animate-fade-in-up-delay-3">
                        <a href="#projects" class="btn-glow px-6 py-3 rounded-xl text-sm font-semibold text-white inline-flex items-center gap-2">
                            {{ __('welcome.explore_projects') }}
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                        </a>
                        <a href="#contact" class="btn-outline-cyan px-6 py-3 rounded-xl text-sm font-semibold inline-flex items-center gap-2">
                            Solicitar Demo
                        </a>
                    </div>
                </div>

                {{-- Visual composition --}}
                <div class="hidden lg:flex items-center justify-center relative">
                    <div class="relative w-80 h-80">
                        {{-- Rotating ring --}}
                        <div class="absolute inset-0 rounded-full border border-cyan-500/20 animate-spin-slow"></div>
                        <div class="absolute inset-4 rounded-full border border-blue-500/15 animate-spin-slow" style="animation-direction: reverse; animation-duration: 25s;"></div>

                        {{-- Central element --}}
                        <div class="absolute inset-12 rounded-2xl bg-gradient-to-br from-cyan-500/20 to-blue-500/20 glass animate-float flex items-center justify-center">
                            <svg class="w-20 h-20 text-cyan-400/80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>

                        {{-- Floating elements --}}
                        <div class="absolute -top-4 right-8 w-16 h-16 rounded-xl glass animate-float flex items-center justify-center">
                            <svg class="w-8 h-8 text-cyan-400/60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3H21"/></svg>
                        </div>
                        <div class="absolute bottom-4 -left-4 w-14 h-14 rounded-lg glass animate-float-delay flex items-center justify-center">
                            <svg class="w-7 h-7 text-blue-400/60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/><path d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z"/></svg>
                        </div>
                        <div class="absolute top-1/2 -right-6 w-12 h-12 rounded-lg glass animate-float flex items-center justify-center" style="animation-delay: 1s;">
                            <svg class="w-6 h-6 text-purple-400/60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path d="M15.91 11.672a.375.375 0 010 .656l-5.603 3.113a.375.375 0 01-.557-.328V8.887c0-.286.307-.466.557-.327l5.603 3.112z"/></svg>
                        </div>

                        {{-- Glow dots --}}
                        <div class="absolute top-0 left-1/2 w-2 h-2 rounded-full bg-cyan-400 animate-pulse"></div>
                        <div class="absolute bottom-0 left-1/2 w-2 h-2 rounded-full bg-blue-400 animate-pulse" style="animation-delay: 1s;"></div>
                        <div class="absolute top-1/2 left-0 w-2 h-2 rounded-full bg-cyan-400 animate-pulse" style="animation-delay: 0.5s;"></div>
                        <div class="absolute top-1/2 right-0 w-2 h-2 rounded-full bg-blue-400 animate-pulse" style="animation-delay: 1.5s;"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bottom fade --}}
        <div class="absolute bottom-0 inset-x-0 h-32 bg-gradient-to-t from-[#0a0a1e] to-transparent"></div>
    </section>

    {{-- Features Section --}}
    <section id="features" class="relative py-24 lg:py-32">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl sm:text-4xl font-bold mb-4">Tecnología que transforma la venta inmobiliaria</h2>
                <p class="text-slate-400 max-w-2xl mx-auto">Herramientas de última generación para que tus compradores vivan el proyecto antes de que exista. Interactivo, inmersivo y sin fricciones.</p>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                {{-- Feature 1 --}}
                <div class="glass glass-hover rounded-2xl p-6 transition-all duration-300 hover:-translate-y-1 group">
                    <div class="w-12 h-12 rounded-xl bg-cyan-500/10 flex items-center justify-center mb-4 group-hover:bg-cyan-500/20 transition-colors">
                        <svg class="w-6 h-6 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-lg mb-2">Modelos 3D Interactivos</h3>
                    <p class="text-sm text-slate-400">Carga modelos GLB/GLTF y permite que tus compradores exploren cada detalle con rotación y zoom en tiempo real. Como estar dentro del proyecto.</p>
                </div>

                {{-- Feature 2 --}}
                <div class="glass glass-hover rounded-2xl p-6 transition-all duration-300 hover:-translate-y-1 group">
                    <div class="w-12 h-12 rounded-xl bg-blue-500/10 flex items-center justify-center mb-4 group-hover:bg-blue-500/20 transition-colors">
                        <svg class="w-6 h-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-lg mb-2">Video 360° Inmersivo</h3>
                    <p class="text-sm text-slate-400">Integra vídeos esféricos 360° como entorno envolvente y crea una experiencia cinematográfica única para cada proyecto.</p>
                </div>

                {{-- Feature 3 --}}
                <div class="glass glass-hover rounded-2xl p-6 transition-all duration-300 hover:-translate-y-1 group">
                    <div class="w-12 h-12 rounded-xl bg-purple-500/10 flex items-center justify-center mb-4 group-hover:bg-purple-500/20 transition-colors">
                        <svg class="w-6 h-6 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-lg mb-2">Gestión de Unidades 3D</h3>
                    <p class="text-sm text-slate-400">Asocia apartamentos, villas y locales directamente sobre el modelo 3D. Estados disponible/reservado/vendido en tiempo real, visibles para tus compradores.</p>
                </div>

                {{-- Feature 4 --}}
                <div class="glass glass-hover rounded-2xl p-6 transition-all duration-300 hover:-translate-y-1 group">
                    <div class="w-12 h-12 rounded-xl bg-emerald-500/10 flex items-center justify-center mb-4 group-hover:bg-emerald-500/20 transition-colors">
                        <svg class="w-6 h-6 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-lg mb-2">Panel de Administración</h3>
                    <p class="text-sm text-slate-400">Gestiona todos tus proyectos, configura el visor 3D, controla unidades y gestiona accesos desde un panel intuitivo y centralizado.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Stats Bar --}}
    <section class="relative py-16 border-y border-white/5">
        <div class="absolute inset-0 bg-gradient-to-r from-cyan-500/5 via-transparent to-blue-500/5"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 text-center">
                <div>
                    <div class="text-3xl sm:text-4xl font-bold bg-gradient-to-r from-cyan-400 to-blue-400 bg-clip-text text-transparent">{{ $projects->count() }}+</div>
                    <div class="text-sm text-slate-500 mt-1">Proyectos Activos</div>
                </div>
                <div>
                    <div class="text-3xl sm:text-4xl font-bold bg-gradient-to-r from-cyan-400 to-blue-400 bg-clip-text text-transparent">{{ $projects->sum('available_units_count') }}+</div>
                    <div class="text-sm text-slate-500 mt-1">Unidades Disponibles</div>
                </div>
                <div>
                    <div class="text-3xl sm:text-4xl font-bold bg-gradient-to-r from-cyan-400 to-blue-400 bg-clip-text text-transparent">3D</div>
                    <div class="text-sm text-slate-500 mt-1">Visualizaciones Interactivas</div>
                </div>
                <div>
                    <div class="text-3xl sm:text-4xl font-bold bg-gradient-to-r from-cyan-400 to-blue-400 bg-clip-text text-transparent">99.9%</div>
                    <div class="text-sm text-slate-500 mt-1">Uptime Garantizado</div>
                </div>
            </div>
        </div>
    </section>

    {{-- Projects Section --}}
    <section id="projects" class="relative py-24 lg:py-32">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl sm:text-4xl font-bold mb-4">Proyectos Destacados</h2>
                <p class="text-slate-400 max-w-2xl mx-auto">Explora en 3D los proyectos activos en Real3D. Modelos interactivos, video 360° y toda la información de cada unidad disponible — antes de que las llaves existan.</p>
            </div>

            @if($projects->count())
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($projects as $project)
                        <a href="{{ route('viewer.landing', $project->slug) }}" class="group glass glass-hover rounded-2xl overflow-hidden transition-all duration-300 hover:-translate-y-1">
                            {{-- Thumbnail / Gradient --}}
                            <div class="h-48 relative overflow-hidden">
                                @php
                                    $thumbnail = $project->files->where('file_type', 'thumbnail')->first();
                                @endphp
                                @if($thumbnail)
                                    <img src="{{ url('api/projects/' . $project->id . '/files/thumbnail') }}" alt="{{ $project->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                @else
                                    <div class="w-full h-full bg-gradient-to-br from-cyan-500/20 to-blue-500/20 flex items-center justify-center">
                                        <svg class="w-16 h-16 text-cyan-500/30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                            <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </div>
                                @endif
                                <div class="absolute inset-0 bg-gradient-to-t from-[#0a0a1e] via-transparent to-transparent"></div>

                                @if($project->units_count > 0)
                                    <div class="absolute top-3 right-3">
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-cyan-500/20 text-cyan-300 backdrop-blur-sm border border-cyan-500/20">
                                            {{ $project->available_units_count }} disponibles
                                        </span>
                                    </div>
                                @endif
                            </div>

                            {{-- Info --}}
                            <div class="p-5">
                                <h3 class="font-semibold text-lg mb-1 group-hover:text-cyan-400 transition-colors">{{ $project->name }}</h3>
                                @if($project->location)
                                    <p class="text-sm text-slate-500 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                                        {{ $project->location }}
                                    </p>
                                @endif
                                @if($project->description)
                                    <p class="text-sm text-slate-500 mt-2 line-clamp-2">{{ $project->description }}</p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="text-center mt-12">
                    <a href="{{ route('viewer.index') }}" class="inline-flex items-center gap-2 text-cyan-400 hover:text-cyan-300 font-medium transition-colors">
                        {{ __('welcome.view_all') }}
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </a>
                </div>
            @else
                <div class="glass rounded-2xl p-12 text-center">
                    <svg class="w-16 h-16 text-slate-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                        <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <p class="text-slate-500">{{ __('welcome.no_projects') }}</p>
                </div>
            @endif
        </div>
    </section>

    {{-- Tech Stack Section --}}
    <section id="tech" class="relative py-24 lg:py-32 border-t border-white/5">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl sm:text-4xl font-bold mb-4">Stack Tecnológico</h2>
                <p class="text-slate-400 max-w-2xl mx-auto">Construido con las mejores tecnologías para ofrecer rendimiento, seguridad y experiencias inmersivas.</p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                {{-- Three.js --}}
                <div class="glass glass-hover rounded-xl p-4 text-center transition-all duration-300 hover:-translate-y-1">
                    <div class="w-10 h-10 mx-auto mb-3 rounded-lg bg-white/5 flex items-center justify-center text-lg font-bold text-cyan-400">3</div>
                    <h4 class="font-medium text-sm mb-1">Three.js</h4>
                    <p class="text-xs text-slate-500">Motor 3D WebGL</p>
                </div>

                {{-- Laravel --}}
                <div class="glass glass-hover rounded-xl p-4 text-center transition-all duration-300 hover:-translate-y-1">
                    <div class="w-10 h-10 mx-auto mb-3 rounded-lg bg-white/5 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 24 24"><path d="M23.642 5.43a.364.364 0 01.014.1v5.149a.365.365 0 01-.174.311l-4.311 2.49v4.934a.365.365 0 01-.174.311l-9.004 5.2a.35.35 0 01-.09.038l-.032.01a.346.346 0 01-.174 0l-.032-.01a.35.35 0 01-.09-.038L.571 18.726a.365.365 0 01-.174-.311V3.076a.364.364 0 01.014-.1l.008-.028a.35.35 0 01.034-.076l.017-.025a.35.35 0 01.053-.059l.024-.02a.35.35 0 01.068-.042L5.118.226a.361.361 0 01.348 0l4.503 2.5a.35.35 0 01.068.042l.024.02a.35.35 0 01.053.059l.017.025a.35.35 0 01.034.076l.008.028.014.1v9.652l3.742-2.162V5.432a.364.364 0 01.014-.1l.008-.028a.35.35 0 01.034-.076l.017-.025a.35.35 0 01.053-.059l.024-.02a.35.35 0 01.068-.042l4.503-2.5a.361.361 0 01.348 0l4.503 2.5a.35.35 0 01.068.042l.024.02a.35.35 0 01.053.059l.017.025a.35.35 0 01.034.076zM23 10.36V5.862l-1.572.908-2.17 1.254v4.497zm-4.503 7.733V13.6l-2.136 1.222-6.607 3.783v4.526zM1.127 3.408v15.006l8.242 4.761v-4.527L5.073 16.2l-.023-.013-.01-.007a.35.35 0 01-.058-.049l-.006-.01a.35.35 0 01-.042-.069l-.006-.009a.35.35 0 01-.02-.078l-.004-.011a.35.35 0 01-.006-.085V5.572l-2.17-1.254zm4.117-2.45L1.571 3.076l3.673 2.118 3.672-2.118zM8.67 15.352l2.17-1.254V3.408l-1.572.908-2.17 1.254v10.69zm5.873-8.579l-3.673 2.118 3.673 2.118 3.673-2.118zm-.365 4.927L12.007 13v4.498l3.742-2.162v-4.498zm4.503-6.835l-3.673 2.118 3.673 2.118 3.673-2.118zm.365 4.927l-2.17-1.254-1.572-.908v4.498l2.17 1.254 1.572.908z"/></svg>
                    </div>
                    <h4 class="font-medium text-sm mb-1">Laravel 11</h4>
                    <p class="text-xs text-slate-500">Backend robusto</p>
                </div>

                {{-- WebGL --}}
                <div class="glass glass-hover rounded-xl p-4 text-center transition-all duration-300 hover:-translate-y-1">
                    <div class="w-10 h-10 mx-auto mb-3 rounded-lg bg-white/5 flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z"/></svg>
                    </div>
                    <h4 class="font-medium text-sm mb-1">WebGL</h4>
                    <p class="text-xs text-slate-500">Renderizado GPU</p>
                </div>

                {{-- Video 360 --}}
                <div class="glass glass-hover rounded-xl p-4 text-center transition-all duration-300 hover:-translate-y-1">
                    <div class="w-10 h-10 mx-auto mb-3 rounded-lg bg-white/5 flex items-center justify-center">
                        <svg class="w-5 h-5 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path d="M15.91 11.672a.375.375 0 010 .656l-5.603 3.113a.375.375 0 01-.557-.328V8.887c0-.286.307-.466.557-.327l5.603 3.112z"/></svg>
                    </div>
                    <h4 class="font-medium text-sm mb-1">Video 360</h4>
                    <p class="text-xs text-slate-500">Experiencia inmersiva</p>
                </div>

                {{-- Tailwind --}}
                <div class="glass glass-hover rounded-xl p-4 text-center transition-all duration-300 hover:-translate-y-1">
                    <div class="w-10 h-10 mx-auto mb-3 rounded-lg bg-white/5 flex items-center justify-center">
                        <svg class="w-5 h-5 text-sky-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12.001 4.8c-3.2 0-5.2 1.6-6 4.8 1.2-1.6 2.6-2.2 4.2-1.8.913.228 1.565.89 2.288 1.624C13.666 10.618 15.027 12 18.001 12c3.2 0 5.2-1.6 6-4.8-1.2 1.6-2.6 2.2-4.2 1.8-.913-.228-1.565-.89-2.288-1.624C16.337 6.182 14.976 4.8 12.001 4.8zm-6 7.2c-3.2 0-5.2 1.6-6 4.8 1.2-1.6 2.6-2.2 4.2-1.8.913.228 1.565.89 2.288 1.624 1.177 1.194 2.538 2.576 5.512 2.576 3.2 0 5.2-1.6 6-4.8-1.2 1.6-2.6 2.2-4.2 1.8-.913-.228-1.565-.89-2.288-1.624C10.337 13.382 8.976 12 6.001 12z"/></svg>
                    </div>
                    <h4 class="font-medium text-sm mb-1">Tailwind CSS</h4>
                    <p class="text-xs text-slate-500">UI moderna</p>
                </div>

                {{-- MariaDB --}}
                <div class="glass glass-hover rounded-xl p-4 text-center transition-all duration-300 hover:-translate-y-1">
                    <div class="w-10 h-10 mx-auto mb-3 rounded-lg bg-white/5 flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"/></svg>
                    </div>
                    <h4 class="font-medium text-sm mb-1">MariaDB</h4>
                    <p class="text-xs text-slate-500">Base de datos</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Pricing Section --}}
    <x-pricing-section />

    {{-- CTA Section --}}
    <section class="relative py-24 lg:py-32 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-r from-cyan-500/10 via-blue-500/10 to-cyan-500/10"></div>
        <div class="absolute inset-0 dot-pattern"></div>

        {{-- Glow orbs --}}
        <div class="absolute top-0 left-1/4 w-64 h-64 bg-cyan-500/20 rounded-full blur-[100px]"></div>
        <div class="absolute bottom-0 right-1/4 w-64 h-64 bg-blue-500/20 rounded-full blur-[100px]"></div>

        <div class="relative max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl sm:text-4xl font-bold mb-6">¿Listo para vender en 3D?</h2>
            <p class="text-lg text-slate-400 mb-10 max-w-xl mx-auto">Únete a los promotores que ya presentan sus proyectos con Real3D. Empieza gratis durante 14 días — sin tarjeta de crédito.</p>
            @guest
                <a href="{{ route('register.business') }}" class="btn-glow animate-glow-pulse px-8 py-4 rounded-xl text-lg font-semibold text-white inline-flex items-center gap-2">
                    Empezar Gratis — 14 días
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </a>
            @else
                <a href="{{ auth()->user()->hasAdminAccess() ? route('admin.dashboard') : route('viewer.index') }}" class="btn-glow animate-glow-pulse px-8 py-4 rounded-xl text-lg font-semibold text-white inline-flex items-center gap-2">
                    Ir al Panel
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </a>
            @endguest
        </div>
    </section>

    {{-- Footer --}}
    <footer id="contact" class="border-t border-white/5 py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-12">
                {{-- Brand --}}
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-cyan-500 to-blue-500 flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1"/></svg>
                        </div>
                        <span class="text-lg font-bold">Real3D.io</span>
                    </div>
                    <p class="text-sm text-slate-500 max-w-xs">Plataforma SaaS para promotores inmobiliarios. Modelos 3D interactivos, video 360° y gestión de unidades en tiempo real.</p>
                </div>

                {{-- Quick Links --}}
                <div>
                    <h4 class="font-semibold text-sm uppercase tracking-wider text-slate-400 mb-4">Links Rápidos</h4>
                    <ul class="space-y-2">
                        <li><a href="#features" class="text-sm text-slate-500 hover:text-cyan-400 transition-colors">Características</a></li>
                        <li><a href="#projects" class="text-sm text-slate-500 hover:text-cyan-400 transition-colors">Proyectos</a></li>
                        <li><a href="#tech" class="text-sm text-slate-500 hover:text-cyan-400 transition-colors">Tecnología</a></li>
                        <li><a href="{{ route('viewer.index') }}" class="text-sm text-slate-500 hover:text-cyan-400 transition-colors">Todos los Proyectos</a></li>
                        <li><a href="{{ route('portal.home') }}" class="text-sm text-slate-500 hover:text-cyan-400 transition-colors">Portal de Propiedades</a></li>
                    </ul>
                </div>

                {{-- Contact --}}
                <div>
                    <h4 class="font-semibold text-sm uppercase tracking-wider text-slate-400 mb-4">Contacto</h4>
                    <ul class="space-y-2">
                        <li class="text-sm text-slate-500 flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                            info@real3d.io
                        </li>
                        <li class="text-sm text-slate-500 flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                            Plataforma Cloud
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-12 pt-8 border-t border-white/5 text-center">
                <p class="text-sm text-slate-600">&copy; {{ date('Y') }} Real3D · real3d.io · Real estate. Real 3D.</p>
            </div>
        </div>
    </footer>

</body>
</html>
