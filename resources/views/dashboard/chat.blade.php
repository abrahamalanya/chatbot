<x-app-layout>
    <x-slot name="title">Mis Clientes</x-slot>

    <div class="flex gap-4 h-[calc(100vh-10rem)]">

        {{-- Lista de clientes --}}
        <div class="w-72 shrink-0 bg-white rounded-xl border border-gray-100 shadow-sm flex flex-col">
            <div class="px-4 py-3 border-b border-gray-100">
                <p class="text-sm font-semibold text-gray-700">Clientes asignados</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ $clientes->count() }} conversaciones</p>
            </div>
            <div class="flex-1 overflow-y-auto divide-y divide-gray-100">
                @forelse($clientes as $cliente)
                <a href="{{ route('chat.index', ['cliente' => $cliente->cliente_telefono]) }}"
                   class="flex items-center gap-3 px-4 py-3 hover:bg-blue-50 transition
                          {{ $clienteSeleccionado === $cliente->cliente_telefono ? 'bg-blue-50 border-l-2 border-blue-600' : '' }}">
                    <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-semibold text-sm shrink-0">
                        {{ strtoupper(substr($cliente->cliente_telefono, -2)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate">+{{ $cliente->cliente_telefono }}</p>
                        <p class="text-xs text-gray-400">WhatsApp</p>
                    </div>
                </a>
                @empty
                <div class="px-4 py-8 text-center text-gray-400 text-sm">
                    No tienes clientes asignados aún.
                </div>
                @endforelse
            </div>
        </div>

        {{-- Panel de chat --}}
        <div class="flex-1 bg-white rounded-xl border border-gray-100 shadow-sm flex flex-col">

            @if($clienteSeleccionado)

            {{-- Header del chat --}}
            <div class="flex items-center gap-3 px-5 py-3 border-b border-gray-100">
                <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-semibold text-sm">
                    {{ strtoupper(substr($clienteSeleccionado, -2)) }}
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-800">+{{ $clienteSeleccionado }}</p>
                    <p class="text-xs text-green-500">Activo</p>
                </div>
            </div>

            {{-- Mensajes --}}
            <div id="chat-box" class="flex-1 overflow-y-auto px-5 py-4 space-y-3">
                @foreach($mensajes as $msg)
                    @if($msg->sender === 'asesor')
                    <div class="flex justify-end">
                        <div class="max-w-xs bg-blue-900 text-white text-sm px-4 py-2.5 rounded-2xl rounded-tr-sm shadow-sm">
                            {{ $msg->mensaje }}
                            <p class="text-xs text-blue-300 mt-1 text-right">{{ $msg->created_at->format('H:i') }}</p>
                        </div>
                    </div>
                    @else
                    <div class="flex justify-start">
                        <div class="max-w-xs bg-gray-100 text-gray-800 text-sm px-4 py-2.5 rounded-2xl rounded-tl-sm shadow-sm">
                            {{ $msg->mensaje }}
                            <p class="text-xs text-gray-400 mt-1">{{ $msg->created_at->format('H:i') }}</p>
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>

            {{-- Input de mensaje --}}
            <div class="border-t border-gray-100 px-4 py-3">
                <form method="POST" action="{{ route('chat.send') }}" class="flex items-center gap-2">
                    @csrf
                    <input type="hidden" name="cliente_telefono" value="{{ $clienteSeleccionado }}">
                    <input type="text" name="mensaje" placeholder="Escribe un mensaje..."
                           autocomplete="off"
                           class="flex-1 border border-gray-200 rounded-full px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button type="submit"
                            class="w-10 h-10 bg-blue-900 text-white rounded-full flex items-center justify-center hover:bg-blue-800 transition shrink-0">
                        <svg class="w-4 h-4 rotate-90" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                        </svg>
                    </button>
                </form>
            </div>

            @else
            <div class="flex-1 flex items-center justify-center text-gray-400">
                <div class="text-center">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    <p class="text-sm">Selecciona un cliente para ver la conversación</p>
                </div>
            </div>
            @endif
        </div>

    </div>

    {{-- Auto-scroll y polling --}}
    <script>
        const clienteTelefono = @json($clienteSeleccionado);

        function scrollBottom() {
            const box = document.getElementById('chat-box');
            if (box) box.scrollTop = box.scrollHeight;
        }

        function cargarMensajes() {
            if (!clienteTelefono) return;

            fetch(`/chat/messages?cliente_telefono=${clienteTelefono}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                const box = document.getElementById('chat-box');
                if (!box) return;
                box.innerHTML = data.map(msg => {
                    const hora = new Date(msg.created_at).toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });
                    return msg.sender === 'asesor'
                        ? `<div class="flex justify-end">
                             <div class="max-w-xs bg-blue-900 text-white text-sm px-4 py-2.5 rounded-2xl rounded-tr-sm shadow-sm">
                               ${msg.mensaje}
                               <p class="text-xs text-blue-300 mt-1 text-right">${hora}</p>
                             </div>
                           </div>`
                        : `<div class="flex justify-start">
                             <div class="max-w-xs bg-gray-100 text-gray-800 text-sm px-4 py-2.5 rounded-2xl rounded-tl-sm shadow-sm">
                               ${msg.mensaje}
                               <p class="text-xs text-gray-400 mt-1">${hora}</p>
                             </div>
                           </div>`;
                }).join('');
                scrollBottom();
            });
        }

        scrollBottom();
        setInterval(cargarMensajes, 2000);
    </script>

</x-app-layout>
