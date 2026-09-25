@php $cat = $category ?? ''; @endphp
@switch($cat)
    @case('beach')
        <svg class="w-4 h-4 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15c2.483 0 4.345-1.5 5-3 .655 1.5 2.517 3 5 3s4.345-1.5 5-3c.655 1.5 2.517 3 5 3M3 19c2.483 0 4.345-1.5 5-3 .655 1.5 2.517 3 5 3s4.345-1.5 5-3c.655 1.5 2.517 3 5 3"/></svg>
        @break
    @case('airport')
        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l-7-7 1.41-1.41L11 15.17V2h2v13.17l4.59-4.58L19 12l-7 7z" transform="rotate(-45 12 12)"/></svg>
        @break
    @case('hospital')
        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m-6-6h12"/><rect x="3" y="3" width="18" height="18" rx="2" stroke-width="2" fill="none"/></svg>
        @break
    @case('shopping')
        <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
        @break
    @case('restaurant')
        <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v18h2V3M7 3v8a4 4 0 004 4v6h2v-6a4 4 0 004-4V3M21 3v18"/></svg>
        @break
    @case('school')
        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
        @break
    @case('golf')
        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h4l1-6h8l1 6h4M12 3v12M12 3l5 4-5 2"/></svg>
        @break
    @case('marina')
        <svg class="w-4 h-4 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2L12 16M8 6l4-4 4 4M6 18c2 2 4 2 6 0s4-2 6 0M4 22c2 2 4 2 6 0s4-2 6 0 4-2 6 0"/></svg>
        @break
    @case('supermarket')
        <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
        @break
    @case('gas_station')
        <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 10V4a1 1 0 00-1-1H6a1 1 0 00-1 1v16a1 1 0 001 1h12a1 1 0 001-1v-4M8 7h8M8 11h4"/></svg>
        @break
    @case('pharmacy')
        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m-8-8h16"/></svg>
        @break
    @case('park')
        <svg class="w-4 h-4 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 22V12m0 0c-3 0-6-3-6-6s3-6 6-6 6 3 6 6-3 6-6 6z"/></svg>
        @break
    @default
        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
@endswitch
