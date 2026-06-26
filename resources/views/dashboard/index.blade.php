<x-app-layout>
    <x-slot name="title">Dashboard</x-slot>

    {{-- Tarjetas de estadísticas --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">

        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <p class="text-sm text-gray-500">Asesores activos</p>
            <p class="text-3xl font-bold text-blue-900 mt-1">{{ $stats['asesores'] }}</p>
        </div>

        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <p class="text-sm text-gray-500">Clientes atendidos</p>
            <p class="text-3xl font-bold text-blue-900 mt-1">{{ $stats['clientes'] }}</p>
        </div>

        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <p class="text-sm text-gray-500">Mensajes totales</p>
            <p class="text-3xl font-bold text-blue-900 mt-1">{{ $stats['mensajes'] }}</p>
        </div>

        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <p class="text-sm text-gray-500">Asignaciones activas</p>
            <p class="text-3xl font-bold text-blue-900 mt-1">{{ $stats['pendientes'] }}</p>
        </div>

    </div>

    {{-- Tabla de asesores --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Asesores</h2>
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
                        <th class="text-center px-6 py-3">Clientes</th>
                        <th class="text-center px-6 py-3">Estado</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($asesores as $asesor)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-800">{{ $asesor->nombre }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $asesor->telefono }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $asesor->user?->email ?? '—' }}</td>
                        <td class="px-6 py-4 text-center text-gray-700">{{ $asesor->assignments_count }}</td>
                        <td class="px-6 py-4 text-center">
                            @if($asesor->activo)
                                <span class="inline-block px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs font-medium">Activo</span>
                            @else
                                <span class="inline-block px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs font-medium">Inactivo</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('advisors.edit', $asesor) }}" class="text-blue-600 hover:underline text-xs">Editar</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-gray-400">No hay asesores registrados.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</x-app-layout>
