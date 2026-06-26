<x-app-layout>
    <x-slot name="title">Nuevo Asesor</x-slot>

    <div class="max-w-lg">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="font-semibold text-gray-800 mb-6">Crear asesor</h2>

            <form method="POST" action="{{ route('advisors.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre completo</label>
                    <input type="text" name="nombre" value="{{ old('nombre') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('nombre') border-red-400 @enderror">
                    @error('nombre') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono WhatsApp</label>
                    <input type="text" name="telefono" value="{{ old('telefono') }}" placeholder="51999999999"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('telefono') border-red-400 @enderror">
                    @error('telefono') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email (para login)</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('email') border-red-400 @enderror">
                    @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
                    <input type="password" name="password"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('password') border-red-400 @enderror">
                    @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar contraseña</label>
                    <input type="password" name="password_confirmation"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit"
                            class="px-5 py-2 bg-blue-900 text-white text-sm font-medium rounded-lg hover:bg-blue-800 transition">
                        Crear asesor
                    </button>
                    <a href="{{ route('advisors.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancelar</a>
                </div>
            </form>
        </div>
    </div>

</x-app-layout>
