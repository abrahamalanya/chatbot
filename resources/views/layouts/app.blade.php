<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'CREDIMAS' }} - Panel</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 font-sans antialiased" x-data="{ sidebarOpen: false }">

    {{-- Overlay móvil --}}
    <div x-show="sidebarOpen"
         x-transition.opacity
         class="fixed inset-0 bg-black/50 z-20 lg:hidden"
         @click="sidebarOpen = false"></div>

    {{-- Sidebar --}}
    <aside class="fixed top-0 left-0 h-full w-64 bg-blue-900 text-white z-30 flex flex-col transition-transform duration-300"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">

        {{-- Logo --}}
        <div class="flex items-center gap-3 px-6 py-5 border-b border-blue-800">
            <div class="w-9 h-9 bg-yellow-400 rounded-lg flex items-center justify-center font-bold text-blue-900 text-lg">C</div>
            <span class="text-xl font-bold tracking-wide">CREDIMAS</span>
        </div>

        {{-- Navegación --}}
        <nav class="flex-1 px-4 py-6 space-y-1">

            @role('admin')
            <p class="text-xs text-blue-400 uppercase font-semibold px-3 mb-2">Administración</p>

            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                      {{ request()->routeIs('dashboard') ? 'bg-blue-700 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Dashboard
            </a>

            <a href="{{ route('advisors.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                      {{ request()->routeIs('advisors.*') ? 'bg-blue-700 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Asesores
            </a>
            @endrole

            @role('asesor')
            <p class="text-xs text-blue-400 uppercase font-semibold px-3 mb-2">Mi panel</p>

            <a href="{{ route('chat.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                      {{ request()->routeIs('chat.*') ? 'bg-blue-700 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                Mis Clientes
            </a>
            @endrole

        </nav>

        {{-- Usuario --}}
        <div class="px-4 py-4 border-t border-blue-800">
            <div class="flex items-center gap-3 px-3 py-2 rounded-lg bg-blue-800">
                <div class="w-8 h-8 rounded-full bg-yellow-400 flex items-center justify-center text-blue-900 font-bold text-sm shrink-0">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-blue-300 truncate capitalize">{{ auth()->user()->getRoleNames()->first() }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="mt-2">
                @csrf
                <button type="submit"
                        class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-blue-300 hover:bg-blue-800 hover:text-white transition">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Cerrar sesión
                </button>
            </form>
        </div>
    </aside>

    {{-- Contenido principal --}}
    <div class="lg:ml-64 min-h-screen flex flex-col">

        {{-- Topbar --}}
        <header class="bg-white border-b border-gray-200 px-6 py-4 flex items-center gap-4 sticky top-0 z-10">
            <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <h1 class="text-lg font-semibold text-gray-800 flex-1">{{ $title ?? 'Panel' }}</h1>
            <span class="text-sm text-gray-400 hidden sm:block">{{ now()->format('d/m/Y') }}</span>
        </header>

        {{-- Flash success --}}
        @if(session('success'))
        <div class="mx-6 mt-4" x-data="{ show: true }" x-show="show" x-transition>
            <div class="flex items-center justify-between px-4 py-3 bg-green-50 border border-green-300 text-green-800 rounded-lg text-sm">
                <span>{{ session('success') }}</span>
                <button @click="show = false" class="ml-4 text-green-600 font-bold">&times;</button>
            </div>
        </div>
        @endif

        {{-- Contenido --}}
        <main class="flex-1 p-6">
            {{ $slot }}
        </main>
    </div>

</body>
</html>
