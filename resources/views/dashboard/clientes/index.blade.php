<x-app-layout>
    <x-slot name="title">Clientes registrados</x-slot>

    {{-- Flash --}}
    @if(session('success'))
    <div class="mb-4 px-4 py-2.5 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700">
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="mb-4 px-4 py-2.5 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
        {{ session('error') }}
    </div>
    @endif

    @php
        $etapaColor = fn ($etapa) => match ($etapa) {
            'completado'      => 'bg-green-100 text-green-700',
            'no_interesado'   => 'bg-red-100 text-red-600',
            'seguimiento'     => 'bg-orange-100 text-orange-600',
            'no_califica'     => 'bg-gray-200 text-gray-600',
            'sin_respuesta'   => 'bg-gray-200 text-gray-600',
            'tiempo_expirado' => 'bg-gray-200 text-gray-600',
            default           => 'bg-blue-100 text-blue-700',
        };
    @endphp

    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <div>
                <h2 class="font-semibold text-gray-800">Clientes registrados</h2>
                <p class="text-xs text-gray-400 mt-0.5">{{ $clientes->count() }} clientes con ficha registrada</p>
            </div>

            <form method="GET" action="{{ route('clientes.index') }}" class="flex items-center gap-2">
                <select name="etapa" onchange="this.form.submit()"
                        class="border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Todas las etapas</option>
                    @foreach(\App\Models\Assignment::DISPOSITIONS as $value => $label)
                    <option value="{{ $value }}" {{ request('etapa') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="tipo_credito" onchange="this.form.submit()"
                        class="border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Todos los créditos</option>
                    @foreach(\App\Models\Cliente::TIPOS_CREDITO as $value => $label)
                    <option value="{{ $value }}" {{ request('tipo_credito') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @if(request('etapa') || request('tipo_credito'))
                <a href="{{ route('clientes.index') }}" class="text-xs text-gray-400 hover:text-gray-600">Limpiar</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wide">
                    <tr>
                        <th class="text-left px-6 py-3">Cliente</th>
                        <th class="text-left px-6 py-3">Nombre</th>
                        <th class="text-left px-6 py-3">Crédito</th>
                        <th class="text-left px-6 py-3">Etapa</th>
                        <th class="text-left px-6 py-3">Contacto</th>
                        <th class="text-left px-6 py-3">Actualizado</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($clientes as $cliente)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-semibold text-xs shrink-0">
                                    {{ strtoupper(substr($cliente->cliente_telefono, -2)) }}
                                </div>
                                <p class="font-medium text-gray-800">+{{ $cliente->cliente_telefono }}</p>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-gray-600">{{ $cliente->nombre ?: '—' }}</td>
                        <td class="px-6 py-4 text-gray-600">
                            {{ \App\Models\Cliente::TIPOS_CREDITO[$cliente->tipo_credito] ?? '—' }}
                        </td>
                        <td class="px-6 py-4">
                            @if($cliente->etapa)
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium {{ $etapaColor($cliente->etapa) }}">
                                {{ \App\Models\Assignment::DISPOSITIONS[$cliente->etapa] ?? $cliente->etapa }}
                            </span>
                            @else
                            <span class="text-gray-400 text-xs">Sin definir</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-500 text-xs">
                            @if($cliente->correo) <p class="truncate max-w-[10rem]">{{ $cliente->correo }}</p> @endif
                            @if($cliente->documento) <p>{{ $cliente->documento }}</p> @endif
                            @if(!$cliente->correo && !$cliente->documento) — @endif
                        </td>
                        <td class="px-6 py-4 text-gray-400 text-xs">{{ $cliente->updated_at->format('d/m/Y H:i') }}</td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('chat.index', ['cliente' => $cliente->cliente_telefono]) }}"
                               class="text-blue-600 hover:underline text-xs">Ver chat</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-10 text-center text-gray-400">
                            Aún no has registrado clientes. Ábrelos desde "Mis Clientes" y usa el botón "Registrar cliente".
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</x-app-layout>
