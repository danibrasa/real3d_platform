<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="app-version" content="{{ \App\Support\Version::label() }}">

        <title>@isset($title){{ $title }} - @endisset{{ config('app.name') }}</title>
        <meta name="description" content="@isset($metaDescription){{ $metaDescription }}@else{{ 'Plataforma de visualizacion inmobiliaria 3D. Explora proyectos con modelos 3D interactivos, video 360 y gestion de unidades en tiempo real.' }}@endisset">
        <link rel="canonical" href="{{ url()->current() }}">
        <link rel="icon" type="image/svg+xml" href="/favicon.svg">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @stack('head')
        @stack('importmap')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
    <x-env-banner />
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>

            <x-admin-version />
        </div>
    </body>
</html>
