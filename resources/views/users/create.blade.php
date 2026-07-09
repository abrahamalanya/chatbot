<x-app-layout>
    <x-slot name="title">Nuevo usuario</x-slot>

    <div class="max-w-lg">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="font-semibold text-gray-800 mb-5">Crear usuario</h2>

            <form method="POST" action="{{ route('users.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre completo</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full border {{ $errors->has('name') ? 'border-red-400' : 'border-gray-200' }} rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('name')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                @php $emailPrefix = old('email') ? str_replace('@credimasperu.com', '', old('email')) : ''; @endphp
                <div x-data="{ prefix: '{{ $emailPrefix }}' }">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico</label>
                    <div class="flex items-center">
                        <input type="text" x-model="prefix" required placeholder=""
                               class="flex-1 min-w-0 border {{ $errors->has('email') ? 'border-red-400' : 'border-gray-300' }} rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <span class="px-3 py-2 bg-gray-50 text-gray-500 text-sm  whitespace-nowrap select-none">@@credimasperu.com</span>
                    </div>
                    <input type="hidden" name="email" :value="prefix + '@@credimasperu.com'">
                    @error('email')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
                    <input type="password" name="password" required minlength="8"
                           class="w-full border {{ $errors->has('password') ? 'border-red-400' : 'border-gray-200' }} rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('password')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar contraseña</label>
                    <input type="password" name="password_confirmation" required minlength="8"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rol</label>
                    <select name="role" required
                            class="w-full border {{ $errors->has('role') ? 'border-red-400' : 'border-gray-200' }} rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">Selecciona un rol...</option>
                        @foreach($roles as $rol)
                            <option value="{{ $rol }}" {{ old('role') === $rol ? 'selected' : '' }}>
                                {{ ucfirst($rol) }}
                            </option>
                        @endforeach
                    </select>
                    @error('role')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                    @role('sistema')
                    <p class="text-xs text-gray-400 mt-1">
                        <strong>Sistema</strong>: acceso total. <strong>Admin</strong>: gestión del panel.
                    </p>
                    @endrole
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit"
                            class="px-5 py-2 bg-blue-900 text-white text-sm font-medium rounded-lg hover:bg-blue-800 transition">
                        Crear usuario
                    </button>
                    <a href="{{ route('users.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancelar</a>
                </div>
            </form>
        </div>
    </div>

</x-app-layout>
