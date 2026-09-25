<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-3']) }}>
    <svg viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" class="w-12 h-12">
        <defs>
            <linearGradient id="logo-grad" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#06b6d4"/>
                <stop offset="100%" stop-color="#3b82f6"/>
            </linearGradient>
        </defs>
        <rect width="48" height="48" rx="12" fill="url(#logo-grad)"/>
        <path d="M24 10L10 21v15a3 3 0 003 3h7v-10h8v10h7a3 3 0 003-3V21L24 10z" fill="white"/>
    </svg>
    <span class="text-xl font-bold text-gray-800">Real3D.io</span>
</div>
