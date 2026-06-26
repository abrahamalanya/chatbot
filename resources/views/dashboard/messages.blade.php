<x-app-layout>
    <x-slot name="title">Historial de Mensajes</x-slot>

    <div class="flex gap-4 h-[calc(100vh-10rem)]">

        {{-- Lista de clientes --}}
        <div class="w-72 shrink-0 bg-white rounded-xl border border-gray-100 shadow-sm flex flex-col">
            <div class="px-4 py-3 border-b border-gray-100">
                <p class="text-sm font-semibold text-gray-700">Todos los clientes</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ $clientes->count() }} contactos</p>
            </div>
            <div class="flex-1 overflow-y-auto divide-y divide-gray-100">
                @forelse($clientes as $cliente)
                <a href="{{ route('admin.messages', ['cliente' => $cliente->cliente_telefono]) }}"
                   class="flex items-center gap-3 px-4 py-3 hover:bg-blue-50 transition
                          {{ $clienteSeleccionado === $cliente->cliente_telefono ? 'bg-blue-50 border-l-2 border-blue-600' : '' }}">
                    <div class="w-9 h-9 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 font-semibold text-sm shrink-0">
                        {{ strtoupper(substr($cliente->cliente_telefono, -2)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate">+{{ $cliente->cliente_telefono }}</p>
                        <p class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($cliente->last_activity)->diffForHumans() }}</p>
                    </div>
                </a>
                @empty
                <div class="px-4 py-8 text-center text-gray-400 text-sm">
                    Sin clientes aún.
                </div>
                @endforelse
            </div>
        </div>

        {{-- Panel derecho --}}
        <div class="flex-1 flex flex-col gap-4 min-w-0 overflow-hidden">

            @if($clienteSeleccionado)

            {{-- Historial de asignaciones --}}
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm shrink-0">
                <div class="px-5 py-3 border-b border-gray-100">
                    <p class="text-sm font-semibold text-gray-700">Historial de atenciones · +{{ $clienteSeleccionado }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-gray-50 text-gray-500 uppercase tracking-wide">
                            <tr>
                                <th class="text-left px-5 py-2">Asesor</th>
                                <th class="text-left px-5 py-2">Asignado</th>
                                <th class="text-left px-5 py-2">Aceptado</th>
                                <th class="text-center px-5 py-2">Duración</th>
                                <th class="text-center px-5 py-2">Resultado</th>
                                <th class="text-center px-5 py-2">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($historial as $h)
                            @php
                                $dispositionColors = [
                                    'completado'      => 'bg-green-100 text-green-700',
                                    'seguimiento'     => 'bg-blue-100 text-blue-700',
                                    'no_interesado'   => 'bg-gray-100 text-gray-600',
                                    'sin_respuesta'   => 'bg-yellow-100 text-yellow-700',
                                    'no_califica'     => 'bg-red-100 text-red-600',
                                    'tiempo_expirado' => 'bg-orange-100 text-orange-700',
                                ];
                                $dc = $dispositionColors[$h->disposition] ?? 'bg-gray-100 text-gray-500';
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-2.5 font-medium text-gray-800">{{ $h->advisor?->nombre ?? '—' }}</td>
                                <td class="px-5 py-2.5 text-gray-500">{{ $h->created_at->format('d/m H:i') }}</td>
                                <td class="px-5 py-2.5 text-gray-500">{{ $h->accepted_at?->format('d/m H:i') ?? '—' }}</td>
                                <td class="px-5 py-2.5 text-center text-gray-500">{{ $h->conversation_duration }} min</td>
                                <td class="px-5 py-2.5 text-center">
                                    @if($h->disposition)
                                        <span class="inline-block px-2 py-0.5 {{ $dc }} rounded-full font-medium">
                                            {{ \App\Models\Assignment::DISPOSITIONS[$h->disposition] }}
                                        </span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-2.5 text-center">
                                    @if($h->status === 'pending')
                                        <span class="inline-block px-2 py-0.5 bg-orange-100 text-orange-700 rounded-full font-medium">Pendiente</span>
                                    @elseif($h->status === 'assigned')
                                        <span class="inline-block px-2 py-0.5 bg-green-100 text-green-700 rounded-full font-medium">Activo</span>
                                    @else
                                        <span class="inline-block px-2 py-0.5 bg-gray-100 text-gray-500 rounded-full font-medium">Cerrado</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="px-5 py-4 text-center text-gray-400">Sin historial.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Mensajes (solo lectura) --}}
            <div class="flex-1 bg-white rounded-xl border border-gray-100 shadow-sm flex flex-col min-h-0">
                <div class="px-5 py-3 border-b border-gray-100 shrink-0">
                    <p class="text-sm font-semibold text-gray-700">Conversación completa</p>
                    <p class="text-xs text-gray-400">{{ count($mensajes) }} mensajes</p>
                </div>
                <div class="flex-1 overflow-y-auto px-5 py-4 space-y-2">
                    @forelse($mensajes as $msg)
                        @if($msg->tipo === 'opcion')
                        <div class="flex justify-center">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-50 border border-blue-100 text-blue-700 text-xs rounded-full">
                                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                                {{ $msg->mensaje }}
                            </span>
                        </div>
                        @elseif($msg->sender === 'asesor')
                        <div class="flex justify-end">
                            <div class="max-w-xs lg:max-w-md">
                                <p class="text-[10px] text-gray-400 text-right mb-0.5">{{ $msg->advisor?->nombre ?? 'Asesor' }}</p>
                                <div class="bg-blue-900 text-white text-sm px-4 py-2.5 rounded-2xl rounded-tr-sm shadow-sm">
                                    {{ $msg->mensaje }}
                                    <p class="text-xs text-blue-300 mt-1 text-right">{{ $msg->created_at->format('H:i') }}</p>
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="flex justify-start">
                            <div class="max-w-xs lg:max-w-md bg-gray-100 text-gray-800 text-sm px-4 py-2.5 rounded-2xl rounded-tl-sm shadow-sm">
                                {{ $msg->mensaje }}
                                <p class="text-xs text-gray-400 mt-1">{{ $msg->created_at->format('H:i') }}</p>
                            </div>
                        </div>
                        @endif
                    @empty
                    <div class="flex items-center justify-center h-full text-gray-400 text-sm">
                        Sin mensajes para este cliente.
                    </div>
                    @endforelse
                </div>
            </div>

            @else
            <div class="flex-1 bg-white rounded-xl border border-gray-100 shadow-sm flex items-center justify-center text-gray-400">
                <div class="text-center">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    <p class="text-sm">Selecciona un cliente para ver su conversación</p>
                </div>
            </div>
            @endif

        </div>
    </div>

</x-app-layout>
