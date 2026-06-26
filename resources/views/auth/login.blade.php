<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CREDIMAS - Iniciar sesión</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 font-sans antialiased flex items-center justify-center min-h-screen">

    <div class="w-full max-w-md px-4">

        {{-- Logo --}}
        <div class="flex items-center justify-center gap-3 mb-8">
            <div class="w-11 h-11 bg-blue-900 rounded-xl flex items-center justify-center font-bold text-yellow-400 text-2xl">C</div>
            <span class="text-2xl font-bold text-blue-900 tracking-wide">CREDIMAS</span>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
            <h1 class="text-xl font-semibold text-gray-800 mb-1">Iniciar sesión</h1>
            <p class="text-sm text-gray-500 mb-6">Accede al panel de gestión</p>

            @if(session('status'))
                <div class="mb-4 text-sm text-green-600 bg-green-50 border border-green-200 rounded-lg px-4 py-3">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                           required autofocus autocomplete="username"
                           class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 {{ $errors->has('email') ? 'border-red-400' : 'border-gray-300' }}">
                    @error('email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
                    <input id="password" type="password" name="password"
                           required autocomplete="current-password"
                           class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 {{ $errors->has('password') ? 'border-red-400' : 'border-gray-300' }}">
                    @error('password')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" name="remember" class="rounded border-gray-300 text-blue-600">
                        Recordarme
                    </label>
                </div>

                <button type="submit"
                        class="w-full py-2.5 bg-blue-900 text-white text-sm font-semibold rounded-lg hover:bg-blue-800 transition">
                    Ingresar
                </button>
            </form>
        </div>

        <p class="text-center text-xs text-gray-400 mt-6">&copy; {{ date('Y') }} CREDIMAS Perú</p>
    </div>

</body>
</html>
