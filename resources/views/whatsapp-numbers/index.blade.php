<x-app-layout>
    <x-slot name="title">Números WhatsApp</x-slot>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Líneas de WhatsApp conectadas</h2>
            <a href="{{ route('whatsapp-numbers.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-blue-900 text-white text-sm font-medium rounded-lg hover:bg-blue-800 transition">
                + Nuevo número
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wide">
                    <tr>
                        <th class="text-left px-6 py-3">Nombre</th>
                        <th class="text-left px-6 py-3">Phone Number ID</th>
                        <th class="text-left px-6 py-3">Teléfono</th>
                        <th class="text-center px-6 py-3">Estado</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($whatsappNumbers as $numero)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-800">{{ $numero->nombre }}</td>
                        <td class="px-6 py-4 text-gray-600 font-mono text-xs">{{ $numero->phone_number_id }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $numero->display_phone_number ?? '—' }}</td>
                        <td class="px-6 py-4 text-center">
                            @if($numero->activo)
                                <span class="inline-block px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs font-medium">Activo</span>
                            @else
                                <span class="inline-block px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs font-medium">Inactivo</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('whatsapp-numbers.edit', $numero) }}" class="text-blue-600 hover:underline text-xs">Editar</a>
                                <form method="POST" action="{{ route('whatsapp-numbers.destroy', $numero) }}"
                                      onsubmit="return confirm('¿Eliminar este número?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:underline text-xs">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-gray-400">No hay números registrados.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($whatsappNumbers->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $whatsappNumbers->links() }}
        </div>
        @endif
    </div>

</x-app-layout>
