{{-- Lista de contactos del asesor. Se renderiza en el panel y también vía
     GET /chat/clientes-lista (polling) para que un cliente recién asignado
     aparezca sin recargar la página. --}}
@forelse($clientes as $cliente)
@php $latest = $cliente->latest; @endphp
<a href="{{ route('chat.index', ['cliente' => $cliente->cliente_telefono]) }}"
   x-show="filterMatch(@js($cliente->nombre ?: ''), @js($cliente->cliente_telefono), {{ (int) $cliente->unread_count }})"
   class="flex items-center gap-3 px-4 py-3 hover:bg-blue-50 transition
          {{ $clienteSeleccionado === $cliente->cliente_telefono ? 'bg-blue-50 border-l-2 border-blue-600' : '' }}">
    <div class="relative shrink-0">
        <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-semibold text-sm">
            {{ strtoupper(substr($cliente->cliente_telefono, -2)) }}
        </div>
        {{-- Indicador de estado --}}
        @if($latest?->status === 'assigned' && !$latest?->accepted_at)
            <span class="absolute -top-0.5 -right-0.5 w-3 h-3 bg-orange-400 border-2 border-white rounded-full" title="Pendiente de aceptar"></span>
        @elseif($latest?->isConversationActive())
            <span class="absolute -top-0.5 -right-0.5 w-3 h-3 bg-green-500 border-2 border-white rounded-full" title="Activo"></span>
        @endif
    </div>
    <div class="min-w-0 flex-1">
        <p class="text-sm font-medium text-gray-800 truncate">
            {{ $cliente->nombre ?: '+' . $cliente->cliente_telefono }}
        </p>
        <p class="text-xs text-gray-400">
            @if($latest?->status === 'assigned' && !$latest?->accepted_at)
                <span class="text-orange-500 font-medium">Pendiente aceptar</span>
            @elseif($latest?->isConversationActive())
                <span class="text-green-600 font-medium">En sesión</span>
            @elseif($cliente->total_sesiones > 1)
                {{ $cliente->total_sesiones }} sesiones
            @else
                WhatsApp
            @endif
            @if($latest?->whatsappNumber)
                · {{ $latest->whatsappNumber->nombre }}
            @endif
        </p>
        @if($latest?->status === 'closed' && $latest?->disposition)
        <p class="text-xs text-gray-400 truncate">
            {{ \App\Models\Assignment::DISPOSITIONS[$latest->disposition] ?? $latest->disposition }}
        </p>
        @endif
    </div>
    @if($cliente->unread_count > 0)
    <span class="shrink-0 min-w-[1rem] h-4 px-1 rounded-full bg-green-500 text-white text-[10px] font-bold flex items-center justify-center" title="Mensajes sin leer">
        {{ $cliente->unread_count }}
    </span>
    @endif
</a>
@empty
<div class="px-4 py-8 text-center text-gray-400 text-sm">
    No tienes clientes asignados aún.
</div>
@endforelse
