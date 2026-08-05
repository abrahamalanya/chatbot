<x-app-layout>
    <x-slot name="title">Dashboard</x-slot>

    {{-- Flash --}}
    @if(session('success'))
    <div class="mb-4 px-4 py-2.5 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700">
        {{ session('success') }}
    </div>
    @endif

    {{-- Estadísticas --}}
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Asesores activos</p>
            <p class="text-3xl font-bold text-blue-900 mt-1">{{ $stats['asesores'] }}</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Clientes totales</p>
            <p class="text-3xl font-bold text-blue-900 mt-1">{{ $stats['clientes'] }}</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Mensajes</p>
            <p class="text-3xl font-bold text-blue-900 mt-1">{{ $stats['mensajes'] }}</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border {{ $stats['pendientes'] > 0 ? 'border-orange-200 bg-orange-50' : 'border-gray-100' }}">
            <p class="text-xs {{ $stats['pendientes'] > 0 ? 'text-orange-600' : 'text-gray-500' }} uppercase tracking-wide">En espera</p>
            <p class="text-3xl font-bold {{ $stats['pendientes'] > 0 ? 'text-orange-600' : 'text-blue-900' }} mt-1">{{ $stats['pendientes'] }}</p>
        </div>
    </div>

    {{-- Cola de pendientes --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6" x-data="{ openId: null }">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <div class="flex items-center gap-2">
                <h2 class="font-semibold text-gray-800">Clientes en espera</h2>
                @if($pendientes->count() > 0)
                    <span class="inline-flex items-center justify-center w-5 h-5 bg-orange-500 text-white text-xs font-bold rounded-full">
                        {{ $pendientes->count() }}
                    </span>
                @endif
            </div>
        </div>

        @forelse($pendientes as $pendiente)
        <div class="border-b border-gray-100 last:border-0">
            <div class="flex items-center justify-between px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-orange-100 flex items-center justify-center text-orange-600 font-semibold text-sm">
                        {{ strtoupper(substr($pendiente->cliente_telefono, -2)) }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-medium text-gray-800">+{{ $pendiente->cliente_telefono }}</p>
                            @if($pendiente->es_recurrente)
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-purple-100 text-purple-700 text-[10px] font-semibold rounded-full uppercase tracking-wide">
                                <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
                                </svg>
                                Recurrente
                            </span>
                            @endif
                            @if($pendiente->nota_dejada)
                            <span class="inline-flex items-center px-1.5 py-0.5 bg-blue-100 text-blue-700 text-[10px] font-semibold rounded-full uppercase tracking-wide">
                                Dejó su consulta
                            </span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-400">
                            Solicitud {{ $pendiente->created_at->diffForHumans() }}
                            @if($pendiente->whatsappNumber)
                                · {{ $pendiente->whatsappNumber->nombre }}
                            @endif
                        </p>
                    </div>
                </div>
                <button @click="openId = openId === {{ $pendiente->id }} ? null : {{ $pendiente->id }}"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-900 text-white text-xs font-medium rounded-lg hover:bg-blue-800 transition">
                    Asignar asesor
                    <svg class="w-3 h-3 transition-transform" :class="openId === {{ $pendiente->id }} ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
            </div>

            {{-- Selector de asesor --}}
            <div x-show="openId === {{ $pendiente->id }}"
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="px-6 pb-4">
                <form method="POST" action="{{ route('assignments.assign', $pendiente) }}"
                      class="bg-gray-50 rounded-xl p-4 border border-gray-100 space-y-3">
                    @csrf
                    <div class="flex items-center gap-3">
                        <select name="advisor_id"
                                class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                            <option value="">Selecciona un asesor...</option>
                            @foreach($asesores as $asesor)
                                <option value="{{ $asesor->id }}">{{ $asesor->nombre }} — {{ $asesor->assignments()->assigned()->count() }} clientes activos</option>
                            @endforeach
                        </select>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <label class="text-xs text-gray-500 whitespace-nowrap">Tiempo:</label>
                            <select name="duration"
                                    class="border border-gray-200 rounded-lg px-2 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                                <option value="5">5 min</option>
                                <option value="15" selected>15 min</option>
                                <option value="30">30 min</option>
                                <option value="60">1 hora</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="submit"
                                class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition">
                            Confirmar asignación
                        </button>
                        <button type="button" @click="openId = null"
                                class="px-3 py-2 text-gray-500 hover:text-gray-700 text-sm">
                            Cancelar
                        </button>
                        <p class="text-xs text-gray-400 ml-auto">El asesor podrá chatear durante el tiempo seleccionado.</p>
                    </div>
                </form>
            </div>
        </div>
        @empty
        <div class="px-6 py-10 text-center text-gray-400 text-sm">
            No hay clientes en espera. ✅
        </div>
        @endforelse
    </div>

    {{-- Historial de atenciones --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100" x-data="{ openHistId: null }">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-wrap gap-3">
            <h2 class="font-semibold text-gray-800">Historial de atenciones</h2>
            <div class="flex items-center gap-2">
                {{-- Filtro por resultado --}}
                <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2">
                    <select name="disposition" onchange="this.form.submit()"
                            class="border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white text-gray-600">
                        <option value="">Todos los resultados</option>
                        @foreach($dispositions as $value => $label)
                            <option value="{{ $value }}" {{ $filtroDisposition === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @if($filtroDisposition)
                    <a href="{{ route('dashboard') }}" class="text-xs text-gray-400 hover:text-gray-600">✕ Limpiar</a>
                    @endif
                </form>
                <a href="{{ route('advisors.index') }}" class="text-xs text-blue-600 hover:underline">Ver asesores →</a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wide">
                    <tr>
                        <th class="text-left px-6 py-3">Cliente</th>
                        <th class="text-left px-6 py-3">Línea</th>
                        <th class="text-left px-6 py-3">Asesor</th>
                        <th class="text-left px-6 py-3">Fecha</th>
                        <th class="text-center px-6 py-3">Ventana chat</th>
                        <th class="text-center px-6 py-3">Resultado</th>
                        <th class="text-center px-6 py-3">Estado</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($asignados as $asignado)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 font-medium text-gray-800">+{{ $asignado->cliente_telefono }}</td>
                        <td class="px-6 py-3 text-gray-500 text-xs">{{ $asignado->whatsappNumber?->nombre ?? '—' }}</td>
                        <td class="px-6 py-3 text-gray-600">{{ $asignado->advisor?->nombre ?? '—' }}</td>
                        <td class="px-6 py-3 text-gray-400 text-xs">{{ $asignado->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-6 py-3 text-center text-xs">
                            @if($asignado->conversation_expires_at)
                                @if($asignado->isConversationActive())
                                    <span class="text-green-600 font-medium">Activa · {{ $asignado->conversation_expires_at->format('H:i') }}</span>
                                @else
                                    <span class="text-gray-400">Expirada</span>
                                @endif
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-center">
                            @if($asignado->disposition)
                                @php
                                    $colors = [
                                        'completado'    => 'bg-green-100 text-green-700',
                                        'seguimiento'   => 'bg-blue-100 text-blue-700',
                                        'no_interesado' => 'bg-gray-100 text-gray-600',
                                        'sin_respuesta' => 'bg-yellow-100 text-yellow-700',
                                        'no_califica'   => 'bg-red-100 text-red-600',
                                    ];
                                    $color = $colors[$asignado->disposition] ?? 'bg-gray-100 text-gray-600';
                                @endphp
                                <span class="inline-block px-2 py-0.5 {{ $color }} rounded-full text-xs font-medium">
                                    {{ $dispositions[$asignado->disposition] }}
                                </span>
                            @else
                                <span class="text-gray-300 text-xs">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-center">
                            @if($asignado->status === 'closed')
                                <span class="inline-block px-2 py-0.5 bg-gray-100 text-gray-500 rounded-full text-xs font-medium">Cerrado</span>
                            @else
                                <span class="inline-block px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs font-medium">Activo</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-right">
                            @if(!$asignado->advisor_id)
                            <button @click="openHistId = openHistId === {{ $asignado->id }} ? null : {{ $asignado->id }}"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-900 text-white text-xs font-medium rounded-lg hover:bg-blue-800 transition whitespace-nowrap">
                                Asignar asesor
                                <svg class="w-3 h-3 transition-transform" :class="openHistId === {{ $asignado->id }} ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            @endif
                        </td>
                    </tr>
                    @if(!$asignado->advisor_id)
                    <tr x-show="openHistId === {{ $asignado->id }}"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0">
                        <td colspan="8" class="px-6 pb-4">
                            <form method="POST" action="{{ route('assignments.assign', $asignado) }}"
                                  class="bg-gray-50 rounded-xl p-4 border border-gray-100 space-y-3">
                                @csrf
                                <div class="flex items-center gap-3">
                                    <select name="advisor_id"
                                            class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                                        <option value="">Selecciona un asesor...</option>
                                        @foreach($asesores as $asesor)
                                            <option value="{{ $asesor->id }}">{{ $asesor->nombre }} — {{ $asesor->assignments()->assigned()->count() }} clientes activos</option>
                                        @endforeach
                                    </select>
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <label class="text-xs text-gray-500 whitespace-nowrap">Tiempo:</label>
                                        <select name="duration"
                                                class="border border-gray-200 rounded-lg px-2 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                                            <option value="5">5 min</option>
                                            <option value="15" selected>15 min</option>
                                            <option value="30">30 min</option>
                                            <option value="60">1 hora</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="submit"
                                            class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition">
                                        Confirmar asignación
                                    </button>
                                    <button type="button" @click="openHistId = null"
                                            class="px-3 py-2 text-gray-500 hover:text-gray-700 text-sm">
                                        Cancelar
                                    </button>
                                </div>
                            </form>
                        </td>
                    </tr>
                    @endif
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-gray-400">No hay atenciones aún.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</x-app-layout>
