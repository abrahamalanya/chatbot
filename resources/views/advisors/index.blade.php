<x-app-layout>
    <x-slot name="title">Asesores</x-slot>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Lista de asesores</h2>
            <a href="{{ route('advisors.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-blue-900 text-white text-sm font-medium rounded-lg hover:bg-blue-800 transition">
                + Nuevo asesor
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wide">
                    <tr>
                        <th class="text-left px-6 py-3">Nombre</th>
                        <th class="text-left px-6 py-3">Teléfono</th>
                        <th class="text-left px-6 py-3">Email</th>
                        <th class="text-center px-6 py-3">Estado</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($advisors as $advisor)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-800">{{ $advisor->nombre }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $advisor->telefono }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $advisor->user?->email ?? '—' }}</td>
                        <td class="px-6 py-4 text-center">
                            @if($advisor->activo)
                                <span class="inline-block px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs font-medium">Activo</span>
                            @else
                                <span class="inline-block px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs font-medium">Inactivo</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('advisors.edit', $advisor) }}" class="text-blue-600 hover:underline text-xs">Editar</a>
                                <form method="POST" action="{{ route('advisors.destroy', $advisor) }}"
                                      onsubmit="return confirm('¿Eliminar este asesor?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:underline text-xs">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-gray-400">No hay asesores registrados.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($advisors->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $advisors->links() }}
        </div>
        @endif
    </div>

</x-app-layout>
