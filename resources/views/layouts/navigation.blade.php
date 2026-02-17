<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="shrink-0 flex items-center">
                    <a href="{{ auth()->check() && auth()->user()->hasAdminAccess() ? route('admin.dashboard') : route('viewer.index') }}" class="font-bold text-lg text-gray-800">
                        RealEstate 3D
                    </a>
                </div>

                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    @auth
                        @if(auth()->user()->hasAdminAccess())
                            <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                                Dashboard
                            </x-nav-link>
                            <x-nav-link :href="route('admin.projects.index')" :active="request()->routeIs('admin.projects.*')">
                                Proyectos
                            </x-nav-link>
                            @can('view-inquiries')
                                <x-nav-link :href="route('admin.inquiries.index')" :active="request()->routeIs('admin.inquiries.*')">
                                    Consultas
                                    @if(($unreadInquiries ?? 0) > 0)
                                        <span class="ml-1 inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-500 rounded-full">{{ $unreadInquiries }}</span>
                                    @endif
                                </x-nav-link>
                            @endcan
                            <x-nav-link :href="route('admin.analytics.index')" :active="request()->routeIs('admin.analytics.*')">
                                Analytics
                            </x-nav-link>
                            @can('manage-agents')
                                <x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                                    {{ auth()->user()->isSuperadmin() ? 'Usuarios' : 'Mis Agentes' }}
                                </x-nav-link>
                            @endcan
                            @can('manage-currencies')
                                <x-nav-link :href="route('admin.currencies.index')" :active="request()->routeIs('admin.currencies.*')">
                                    Monedas
                                </x-nav-link>
                            @endcan
                            @can('manage-api-tokens')
                                <x-nav-link :href="route('admin.api-tokens.index')" :active="request()->routeIs('admin.api-tokens.*')">
                                    API
                                </x-nav-link>
                            @endcan
                            @can('create-project')
                                <x-nav-link :href="route('admin.strategic-analysis.index')" :active="request()->routeIs('admin.strategic-analysis.*')">
                                    Estrategia
                                </x-nav-link>
                            @endcan
                            @if(auth()->user()->isInmobiliaria())
                                <x-nav-link :href="route('admin.company-profile.edit')" :active="request()->routeIs('admin.company-profile.*')">
                                    {{ __('billing.my_company') }}
                                </x-nav-link>
                                <x-nav-link :href="route('admin.subscription.index')" :active="request()->routeIs('admin.subscription.*')">
                                    {{ __('billing.subscription') }}
                                </x-nav-link>
                                @if(auth()->user()->hasFeature('api_access'))
                                    <x-nav-link :href="route('admin.webhooks.index')" :active="request()->routeIs('admin.webhooks.*')">
                                        Webhooks
                                    </x-nav-link>
                                @endif
                            @endif
                            @can('view-audit-logs')
                                <x-nav-link :href="route('admin.audit-logs.index')" :active="request()->routeIs('admin.audit-logs.*')">
                                    Audit Log
                                </x-nav-link>
                            @endcan
                        @endif
                    @endauth
                    <x-nav-link :href="route('viewer.index')" :active="request()->routeIs('viewer.*')">
                        Ver Proyectos
                    </x-nav-link>
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6 gap-1">
                <x-currency-selector />
                <x-language-selector />
                @auth
                    @if(auth()->user()->hasAdminAccess())
                    {{-- Notification bell --}}
                    <div x-data="notificationBell()" x-init="startPolling()" class="relative mr-3">
                        <button @click="toggleDropdown()" class="relative p-2 text-gray-400 hover:text-gray-600 focus:outline-none transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            <span x-show="unread > 0" x-transition
                                  class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center w-4 h-4 text-[10px] font-bold text-white bg-red-500 rounded-full"
                                  :class="{ 'animate-pulse': justUpdated }"
                                  x-text="unread > 9 ? '9+' : unread"></span>
                        </button>
                        {{-- Dropdown --}}
                        <div x-show="dropdownOpen" @click.outside="dropdownOpen = false" x-transition
                             class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50 overflow-hidden"
                             style="display: none;">
                            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                                <span class="text-sm font-semibold text-gray-700">Notificaciones</span>
                                <span x-show="unread > 0" class="text-xs text-gray-500" x-text="unread + ' sin leer'"></span>
                            </div>
                            <div class="max-h-64 overflow-y-auto">
                                <template x-if="recentItems.length === 0">
                                    <div class="px-4 py-6 text-center text-sm text-gray-400">Sin notificaciones nuevas</div>
                                </template>
                                <template x-for="item in recentItems" :key="item.id">
                                    <a :href="'{{ route('admin.inquiries.index') }}'" class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-50 transition">
                                        <div class="flex items-start gap-3">
                                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-800 truncate" x-text="item.name"></p>
                                                <p class="text-xs text-gray-500 truncate" x-text="item.project_name"></p>
                                                <p class="text-xs text-gray-400 mt-0.5" x-text="item.time_ago"></p>
                                            </div>
                                        </div>
                                    </a>
                                </template>
                            </div>
                            <a href="{{ route('admin.inquiries.index') }}" class="block px-4 py-2.5 text-center text-xs font-medium text-blue-600 hover:bg-gray-50 border-t border-gray-100">
                                Ver todas las consultas
                            </a>
                        </div>
                    </div>
                    @endif

                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                <div>{{ Auth::user()->name }}</div>
                                <div class="ms-1">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            @if(auth()->user()->hasAdminAccess())
                                <x-dropdown-link :href="route('admin.dashboard')">Admin Panel</x-dropdown-link>
                            @endif
                            <x-dropdown-link :href="route('profile.edit')">Perfil</x-dropdown-link>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                    Cerrar Sesion
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @else
                    <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-gray-900">Iniciar sesion</a>
                @endauth
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @auth
                @if(auth()->user()->hasAdminAccess())
                    <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">Dashboard</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.projects.index')" :active="request()->routeIs('admin.projects.*')">Proyectos</x-responsive-nav-link>
                    @can('view-inquiries')
                        <x-responsive-nav-link :href="route('admin.inquiries.index')" :active="request()->routeIs('admin.inquiries.*')">
                            Consultas
                            @if(($unreadInquiries ?? 0) > 0)
                                <span class="ml-1 inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-500 rounded-full">{{ $unreadInquiries }}</span>
                            @endif
                        </x-responsive-nav-link>
                    @endcan
                    <x-responsive-nav-link :href="route('admin.analytics.index')" :active="request()->routeIs('admin.analytics.*')">Analytics</x-responsive-nav-link>
                    @can('manage-agents')
                        <x-responsive-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                            {{ auth()->user()->isSuperadmin() ? 'Usuarios' : 'Mis Agentes' }}
                        </x-responsive-nav-link>
                    @endcan
                    @can('manage-api-tokens')
                        <x-responsive-nav-link :href="route('admin.api-tokens.index')" :active="request()->routeIs('admin.api-tokens.*')">
                            API Tokens
                        </x-responsive-nav-link>
                    @endcan
                    @can('create-project')
                        <x-responsive-nav-link :href="route('admin.strategic-analysis.index')" :active="request()->routeIs('admin.strategic-analysis.*')">
                            Estrategia
                        </x-responsive-nav-link>
                    @endcan
                    @if(auth()->user()->isInmobiliaria())
                        <x-responsive-nav-link :href="route('admin.company-profile.edit')" :active="request()->routeIs('admin.company-profile.*')">
                            {{ __('billing.my_company') }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('admin.subscription.index')" :active="request()->routeIs('admin.subscription.*')">
                            {{ __('billing.subscription') }}
                        </x-responsive-nav-link>
                    @endif
                    @can('view-audit-logs')
                        <x-responsive-nav-link :href="route('admin.audit-logs.index')" :active="request()->routeIs('admin.audit-logs.*')">
                            Audit Log
                        </x-responsive-nav-link>
                    @endcan
                @endif
            @endauth
            <x-responsive-nav-link :href="route('viewer.index')" :active="request()->routeIs('viewer.*')">{{ __('general.view_projects') }}</x-responsive-nav-link>
            <div class="px-4 py-2 flex items-center gap-2">
                <a href="{{ request()->fullUrlWithQuery(['lang' => 'es']) }}" class="px-3 py-1 rounded text-sm {{ app()->getLocale() === 'es' ? 'bg-blue-100 text-blue-700 font-medium' : 'text-gray-600' }}">ES</a>
                <a href="{{ request()->fullUrlWithQuery(['lang' => 'en']) }}" class="px-3 py-1 rounded text-sm {{ app()->getLocale() === 'en' ? 'bg-blue-100 text-blue-700 font-medium' : 'text-gray-600' }}">EN</a>
            </div>
            @php $mobileCurrencies = app(\App\Services\CurrencyService::class)->getAvailable(); $mobileCurrent = \App\Services\CurrencyService::getCurrentCode(); @endphp
            <div class="px-4 py-2 flex items-center gap-2">
                @foreach($mobileCurrencies as $code => $cur)
                    @if($cur['is_active'])
                    <a href="{{ request()->fullUrlWithQuery(['currency' => $code]) }}" class="px-3 py-1 rounded text-sm {{ $mobileCurrent === $code ? 'bg-green-100 text-green-700 font-medium' : 'text-gray-600' }}">{{ $cur['symbol'] }}</a>
                    @endif
                @endforeach
            </div>
        </div>
        @auth
            <div class="pt-4 pb-1 border-t border-gray-200">
                <div class="px-4">
                    <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                    <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
                </div>
                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('profile.edit')">Perfil</x-responsive-nav-link>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                            Cerrar Sesion
                        </x-responsive-nav-link>
                    </form>
                </div>
            </div>
        @else
            <div class="pt-4 pb-1 border-t border-gray-200">
                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('login')">Iniciar sesion</x-responsive-nav-link>
                </div>
            </div>
        @endauth
    </div>
</nav>

@auth
@if(auth()->user()->hasAdminAccess())
{{-- Toast notification --}}
<div id="notification-toast" style="display:none;"
     class="fixed top-20 right-4 z-50 bg-white border border-gray-200 rounded-lg shadow-lg p-4 max-w-sm transition-all duration-300 translate-x-full opacity-0">
    <div class="flex items-start gap-3">
        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
        </div>
        <div class="flex-1">
            <p class="text-sm font-medium text-gray-800" id="toast-title"></p>
            <p class="text-xs text-gray-500 mt-0.5" id="toast-subtitle"></p>
        </div>
        <button onclick="hideNotificationToast()" class="text-gray-400 hover:text-gray-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <div class="mt-2 flex items-center justify-between">
        <a href="{{ route('admin.inquiries.index') }}" class="text-xs font-medium text-blue-600 hover:underline">Ver consulta</a>
        <div class="h-0.5 flex-1 ml-3 bg-gray-100 rounded overflow-hidden">
            <div id="toast-progress" class="h-full bg-blue-500 rounded transition-all" style="width: 100%;"></div>
        </div>
    </div>
</div>

<script>
function notificationBell() {
    return {
        unread: {{ $unreadInquiries ?? 0 }},
        lastCount: {{ $unreadInquiries ?? 0 }},
        justUpdated: false,
        dropdownOpen: false,
        recentItems: [],
        pollInterval: null,

        startPolling() {
            this.poll();
            this.pollInterval = setInterval(() => this.poll(), 30000);
        },

        async poll() {
            try {
                const res = await fetch('{{ route("admin.notifications.count") }}', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) return;
                const data = await res.json();
                const newCount = data.unread_inquiries;

                if (newCount > this.lastCount && data.latest_inquiry) {
                    this.showToast(data.latest_inquiry);
                    this.justUpdated = true;
                    setTimeout(() => this.justUpdated = false, 2000);
                }

                this.unread = newCount;
                this.lastCount = newCount;
            } catch (e) {}
        },

        async toggleDropdown() {
            this.dropdownOpen = !this.dropdownOpen;
            if (this.dropdownOpen) {
                try {
                    const res = await fetch('{{ route("admin.notifications.recent") }}', {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (res.ok) {
                        const data = await res.json();
                        this.recentItems = data.inquiries;
                    }
                } catch (e) {}
            }
        },

        showToast(inquiry) {
            const toast = document.getElementById('notification-toast');
            const title = document.getElementById('toast-title');
            const subtitle = document.getElementById('toast-subtitle');
            const progress = document.getElementById('toast-progress');

            title.textContent = 'Nueva consulta de ' + inquiry.name;
            subtitle.textContent = inquiry.project_name + ' — ' + inquiry.time_ago;

            toast.style.display = 'block';
            requestAnimationFrame(() => {
                toast.classList.remove('translate-x-full', 'opacity-0');
                toast.classList.add('translate-x-0', 'opacity-100');
            });

            progress.style.width = '100%';
            progress.style.transition = 'width 8s linear';
            requestAnimationFrame(() => progress.style.width = '0%');

            setTimeout(() => hideNotificationToast(), 8000);
        }
    };
}

function hideNotificationToast() {
    const toast = document.getElementById('notification-toast');
    toast.classList.add('translate-x-full', 'opacity-0');
    toast.classList.remove('translate-x-0', 'opacity-100');
    setTimeout(() => toast.style.display = 'none', 300);
}
</script>
@endif
@endauth
