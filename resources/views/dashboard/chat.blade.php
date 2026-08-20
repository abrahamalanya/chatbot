<x-app-layout>
    <x-slot name="title">Mis Clientes</x-slot>

    <div class="flex gap-4 h-[calc(100vh-10rem)]" x-data="{ modalCierre: false, modalCliente: false }">

        {{-- Lista de clientes --}}
        <div class="w-72 shrink-0 bg-white rounded-xl border border-gray-100 shadow-sm flex flex-col">
            <div class="px-4 py-3 border-b border-gray-100">
                <p class="text-sm font-semibold text-gray-700">Mis clientes</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ $clientes->count() }} contactos</p>
            </div>
            <div id="client-list" class="flex-1 overflow-y-auto divide-y divide-gray-100">
                @forelse($clientes as $cliente)
                @php $latest = $cliente->latest; @endphp
                <a href="{{ route('chat.index', ['cliente' => $cliente->cliente_telefono]) }}"
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
            </div>
        </div>

        {{-- Panel de chat --}}
        <div class="flex-1 bg-white rounded-xl border border-gray-100 shadow-sm flex flex-col min-w-0">

            @if($clienteSeleccionado)

            {{-- Header --}}
            <div class="flex items-center gap-3 px-5 py-3 border-b border-gray-100 shrink-0">
                <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-semibold text-sm shrink-0">
                    {{ strtoupper(substr($clienteSeleccionado, -2)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                        {{ $clienteRegistro?->nombre ?: '+' . $clienteSeleccionado }}
                        @if($assignment?->whatsappNumber)
                        <span class="px-1.5 py-0.5 bg-gray-100 text-gray-500 text-[10px] font-medium rounded-full uppercase tracking-wide">
                            {{ $assignment->whatsappNumber->nombre }}
                        </span>
                        @endif
                    </p>
                    @if($assignment?->status === 'assigned' && !$assignment?->accepted_at)
                        <p class="text-xs text-orange-500">Asignado — pendiente de aceptar</p>
                    @elseif($assignment?->isConversationActive())
                        <p class="text-xs text-green-500" id="chat-status">
                            Sesión activa · expira a las {{ $assignment->conversation_expires_at->format('H:i') }}
                        </p>
                    @elseif($assignment?->status === 'assigned')
                        <p class="text-xs text-orange-500" id="chat-status">Sesión expirada</p>
                    @elseif($assignment?->status === 'closed')
                        <p class="text-xs text-gray-400">
                            Cerrado · {{ \App\Models\Assignment::DISPOSITIONS[$assignment->disposition] ?? '—' }}
                        </p>
                    @else
                        <p class="text-xs text-gray-400">Historial</p>
                    @endif
                </div>

                {{-- Registro de cliente --}}
                @if($clienteRegistro)
                <button @click="modalCliente = true"
                        class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-50 border border-gray-200 text-gray-600 text-xs font-medium rounded-lg hover:bg-gray-100 transition"
                        title="Editar datos del cliente">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                    </svg>
                    {{ \App\Models\Assignment::DISPOSITIONS[$clienteRegistro->etapa] ?? 'Registrado' }}
                </button>
                @else
                <button @click="modalCliente = true"
                        class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 border border-indigo-200 text-indigo-700 text-xs font-medium rounded-lg hover:bg-indigo-100 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                    Registrar cliente
                </button>
                @endif

                {{-- Countdown --}}
                @if($assignment?->isConversationActive())
                <div id="countdown-badge"
                     class="shrink-0 px-2.5 py-1 bg-green-50 border border-green-200 rounded-lg text-xs text-green-700 font-mono tabular-nums"
                     data-expires="{{ $assignment->conversation_expires_at->toIso8601String() }}">—</div>
                @endif

                {{-- Botón extender tiempo (solo si hay sesión activa) --}}
                @if($assignment?->status === 'assigned' && $assignment?->accepted_at)
                <form method="POST" action="{{ route('chat.extend') }}">
                    @csrf
                    <input type="hidden" name="cliente_telefono" value="{{ $clienteSeleccionado }}">
                    <input type="hidden" name="minutos" value="10">
                    <button type="submit"
                            class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 border border-blue-200 text-blue-700 text-xs font-medium rounded-lg hover:bg-blue-100 transition"
                            title="Agregar 10 minutos a la sesión">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        +10 min
                    </button>
                </form>
                @endif

                {{-- Botón cerrar (solo si hay sesión activa) --}}
                @if($assignment?->status === 'assigned' && $assignment?->accepted_at)
                <button @click="modalCierre = true"
                        class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 border border-red-200 text-red-600 text-xs font-medium rounded-lg hover:bg-red-100 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Cerrar
                </button>
                @endif
            </div>

            {{-- Mensajes --}}
            <div id="chat-box" class="flex-1 overflow-y-auto px-5 py-4 space-y-2">
                @foreach($mensajes as $msg)
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
                        <div class="max-w-xs lg:max-w-sm bg-blue-900 text-white text-sm px-4 py-2.5 rounded-2xl rounded-tr-sm shadow-sm whitespace-pre-wrap break-words">
                            @include('dashboard.partials.message-content', ['msg' => $msg, 'light' => true])
                            <p class="text-xs text-blue-300 mt-1 text-right">{{ $msg->created_at->format('H:i') }}</p>
                        </div>
                    </div>
                    @else
                    <div class="flex justify-start">
                        <div class="max-w-xs lg:max-w-sm bg-gray-100 text-gray-800 text-sm px-4 py-2.5 rounded-2xl rounded-tl-sm shadow-sm whitespace-pre-wrap break-words">
                            @include('dashboard.partials.message-content', ['msg' => $msg, 'light' => false])
                            <p class="text-xs text-gray-400 mt-1">{{ $msg->created_at->format('H:i') }}</p>
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>

            {{-- Zona inferior: según estado --}}
            @if($assignment?->status === 'assigned' && !$assignment?->accepted_at)
            {{-- ESTADO: pendiente de aceptar --}}
            <div class="border-t border-gray-100 px-5 py-4 shrink-0 bg-orange-50">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-gray-800">Nuevo cliente asignado</p>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Tienes <strong>{{ $assignment->conversation_duration }} minutos</strong> de sesión al aceptar.
                        </p>
                    </div>
                    <form method="POST" action="{{ route('chat.accept') }}">
                        @csrf
                        <input type="hidden" name="cliente_telefono" value="{{ $clienteSeleccionado }}">
                        <button type="submit"
                                class="px-5 py-2 bg-green-600 text-white text-sm font-semibold rounded-xl hover:bg-green-700 transition whitespace-nowrap">
                            ✓ Aceptar cliente
                        </button>
                    </form>
                </div>
            </div>

            @elseif($assignment?->isConversationActive())
            {{-- ESTADO: sesión activa — puede escribir --}}
            <div class="border-t border-gray-100 px-4 py-3 shrink-0" id="input-area">
                <form method="POST" action="{{ route('chat.send') }}" class="flex items-end gap-2">
                    @csrf
                    <input type="hidden" name="cliente_telefono" value="{{ $clienteSeleccionado }}">
                    <textarea name="mensaje" id="msg-input" placeholder="Escribe un mensaje... (Shift+Enter para salto de línea)"
                              rows="1" maxlength="1000"
                              class="flex-1 border border-gray-200 rounded-2xl px-4 py-2 text-sm resize-none leading-normal focus:outline-none focus:ring-2 focus:ring-blue-500"
                              style="max-height: 120px;"></textarea>
                    <button type="submit"
                            class="w-10 h-10 bg-blue-900 text-white rounded-full flex items-center justify-center hover:bg-blue-800 transition shrink-0">
                        <svg class="w-4 h-4 rotate-90" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                        </svg>
                    </button>
                </form>
            </div>

            @else
            {{-- ESTADO: expirada o cerrada --}}
            <div class="border-t border-gray-100 px-4 py-3 shrink-0 text-center text-xs text-gray-400">
                @if($assignment?->disposition)
                    Conversación cerrada ·
                    <span class="font-medium">{{ \App\Models\Assignment::DISPOSITIONS[$assignment->disposition] }}</span>
                @else
                    Solo historial — sin conversación activa
                @endif
            </div>
            @endif

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

        {{-- Modal de cierre --}}
        <div x-show="modalCierre"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
             @click.self="modalCierre = false">
            <div x-show="modalCierre"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="bg-white rounded-2xl shadow-xl w-full max-w-sm mx-4 p-6">
                <h3 class="text-base font-semibold text-gray-800 mb-1">Cerrar conversación</h3>
                <p class="text-sm text-gray-500 mb-5">Selecciona el resultado de esta atención.</p>
                <form method="POST" action="{{ route('chat.close') }}">
                    @csrf
                    <input type="hidden" name="cliente_telefono" value="{{ $clienteSeleccionado }}">
                    <div class="space-y-2 mb-5">
                        @foreach(\App\Models\Assignment::DISPOSITIONS as $value => $label)
                        @if($value !== 'tiempo_expirado')
                        <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 cursor-pointer hover:bg-gray-50 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 transition">
                            <input type="radio" name="disposition" value="{{ $value }}" class="accent-blue-600">
                            <span class="text-sm text-gray-700">{{ $label }}</span>
                        </label>
                        @endif
                        @endforeach
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition">
                            Confirmar cierre
                        </button>
                        <button type="button" @click="modalCierre = false"
                                class="flex-1 py-2 border border-gray-200 text-gray-600 text-sm rounded-lg hover:bg-gray-50 transition">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal de registro de cliente --}}
        <div x-show="modalCliente"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
             @click.self="modalCliente = false">
            <div x-show="modalCliente"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="bg-white rounded-2xl shadow-xl w-full max-w-sm mx-4 p-6 max-h-[90vh] overflow-y-auto">
                <h3 class="text-base font-semibold text-gray-800 mb-1">
                    {{ $clienteRegistro ? 'Editar cliente' : 'Registrar cliente' }}
                </h3>
                <p class="text-sm text-gray-500 mb-5">+{{ $clienteSeleccionado }}</p>
                <form method="POST" action="{{ route('clientes.store') }}">
                    @csrf
                    <input type="hidden" name="cliente_telefono" value="{{ $clienteSeleccionado }}">

                    <div class="space-y-3 mb-5">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Nombre completo</label>
                            <input type="text" name="nombre" value="{{ old('nombre', $clienteRegistro?->nombre) }}"
                                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Correo electrónico</label>
                            <input type="email" name="correo" value="{{ old('correo', $clienteRegistro?->correo) }}"
                                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Documento (DNI/RUC)</label>
                            <input type="text" name="documento" value="{{ old('documento', $clienteRegistro?->documento) }}"
                                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Tipo de crédito</label>
                            <select name="tipo_credito" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">— Sin definir —</option>
                                @foreach(\App\Models\Cliente::TIPOS_CREDITO as $value => $label)
                                <option value="{{ $value }}" {{ old('tipo_credito', $clienteRegistro?->tipo_credito) === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Etapa</label>
                            <select name="etapa" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">— Sin definir —</option>
                                @foreach(\App\Models\Assignment::DISPOSITIONS as $value => $label)
                                <option value="{{ $value }}" {{ old('etapa', $clienteRegistro?->etapa) === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Notas</label>
                            <textarea name="notas" rows="3"
                                      class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('notas', $clienteRegistro?->notas) }}</textarea>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 py-2 bg-blue-900 text-white text-sm font-medium rounded-lg hover:bg-blue-800 transition">
                            Guardar
                        </button>
                        <button type="button" @click="modalCliente = false"
                                class="flex-1 py-2 border border-gray-200 text-gray-600 text-sm rounded-lg hover:bg-gray-50 transition">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    {{-- Toast de advertencia 1 minuto --}}
    <div id="warning-toast"
         style="display:none"
         class="fixed bottom-6 right-6 z-50 flex items-start gap-3 bg-orange-600 text-white px-4 py-3 rounded-xl shadow-lg max-w-xs">
        <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
        </svg>
        <div>
            <p class="text-sm font-semibold">¡Queda 1 minuto!</p>
            <p class="text-xs text-orange-200 mt-0.5">Termina de gestionar al cliente antes de que expire la sesión.</p>
        </div>
        <button onclick="document.getElementById('warning-toast').style.display='none'"
                class="ml-2 text-orange-200 hover:text-white text-lg leading-none shrink-0">×</button>
    </div>

    <script>
        const clienteTelefono = @json($clienteSeleccionado);
        let renderedIds = @json(collect($mensajes)->pluck('id'));

        // ── Mantener la posición de scroll de la lista de clientes ────────────
        // Cada clic en un cliente recarga la página completa, así que la
        // posición se guarda antes de navegar y se restaura al cargar.
        const clientList = document.getElementById('client-list');
        if (clientList) {
            const savedScroll = sessionStorage.getItem('chatClientListScroll');
            if (savedScroll !== null) {
                clientList.scrollTop = parseInt(savedScroll, 10);
            }
            clientList.addEventListener('click', function (e) {
                if (e.target.closest('a')) {
                    sessionStorage.setItem('chatClientListScroll', clientList.scrollTop);
                }
            });
        }

        // ── Countdown ─────────────────────────────────────────────────────────
        const badge = document.getElementById('countdown-badge');
        if (badge) {
            const expires = new Date(badge.dataset.expires);
            let warned = false;

            function updateCountdown() {
                const diff = Math.floor((expires - Date.now()) / 1000);

                // Aviso 1 minuto antes
                if (diff <= 60 && !warned) {
                    warned = true;
                    document.getElementById('warning-toast').style.display = 'flex';
                    setTimeout(() => document.getElementById('warning-toast').style.display = 'none', 12000);
                }

                if (diff <= 0) {
                    badge.textContent = 'Expirado';
                    badge.className = 'shrink-0 px-2.5 py-1 bg-orange-50 border border-orange-300 rounded-lg text-xs text-orange-600 font-mono';
                    const status = document.getElementById('chat-status');
                    if (status) {
                        status.textContent = 'Sesión expirada';
                        status.className = 'text-xs text-orange-500';
                    }
                    // Deshabilitar input
                    const inputArea = document.getElementById('input-area');
                    if (inputArea) {
                        inputArea.innerHTML = `<p class="text-center text-xs text-orange-500 py-3">
                            Sesión expirada — no puedes enviar más mensajes
                        </p>`;
                    }
                    return;
                }

                const m = String(Math.floor(diff / 60)).padStart(2, '0');
                const s = String(diff % 60).padStart(2, '0');
                badge.textContent = m + ':' + s;
            }

            updateCountdown();
            setInterval(updateCountdown, 1000);
        }

        // ── Input de mensaje: Enter envía, Shift+Enter hace salto de línea ────
        const msgInput = document.getElementById('msg-input');
        if (msgInput) {
            msgInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    msgInput.closest('form').requestSubmit();
                }
            });
            msgInput.addEventListener('input', function () {
                msgInput.style.height = 'auto';
                msgInput.style.height = Math.min(msgInput.scrollHeight, 120) + 'px';
            });
        }

        // ── Scroll y polling de mensajes ──────────────────────────────────────
        function scrollBottom() {
            const box = document.getElementById('chat-box');
            if (box) box.scrollTop = box.scrollHeight;
        }

        function isNearBottom(box) {
            return box.scrollHeight - box.scrollTop - box.clientHeight < 80;
        }

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str ?? '';
            return div.innerHTML;
        }

        function renderContent(msg, light) {
            const textColor = light ? 'text-white' : 'text-gray-800';

            if (msg.tipo === 'imagen') {
                const img = msg.media_url
                    ? `<a href="${msg.media_url}" target="_blank" rel="noopener">
                         <img src="${msg.media_url}" alt="Imagen" class="rounded-lg max-w-full max-h-60 object-cover mb-1">
                       </a>`
                    : '';
                const caption = (msg.mensaje && msg.mensaje !== '📷 Imagen') ? `<p>${escapeHtml(msg.mensaje)}</p>` : '';
                return img + caption;
            }

            if (msg.tipo === 'documento') {
                if (!msg.media_url) return `<p>${escapeHtml(msg.mensaje)}</p>`;
                return `<a href="${msg.media_url}" target="_blank" rel="noopener" class="flex items-center gap-2 ${textColor} underline decoration-dotted">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span class="truncate">${escapeHtml(msg.media_filename || 'Documento')}</span>
                </a>`;
            }

            if (msg.tipo === 'video') {
                const video = msg.media_url
                    ? `<video src="${msg.media_url}" controls class="rounded-lg max-w-full max-h-60 mb-1"></video>`
                    : '';
                const caption = (msg.mensaje && msg.mensaje !== '🎥 Video') ? `<p>${escapeHtml(msg.mensaje)}</p>` : '';
                return video + caption;
            }

            if (msg.tipo === 'audio') {
                if (!msg.media_url) return `<p>${escapeHtml(msg.mensaje)}</p>`;
                return `<audio src="${msg.media_url}" controls class="max-w-full mb-1" style="max-width: 240px;"></audio>`;
            }

            if (msg.tipo === 'ubicacion') {
                return `<a href="https://www.google.com/maps?q=${msg.latitude},${msg.longitude}" target="_blank" rel="noopener" class="flex items-center gap-2 ${textColor} underline decoration-dotted">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span>${escapeHtml(msg.mensaje)}</span>
                </a>`;
            }

            return escapeHtml(msg.mensaje.trim());
        }

        function renderMsg(msg) {
            const hora = new Date(msg.created_at).toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });

            if (msg.tipo === 'opcion') {
                return `<div class="flex justify-center">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-50 border border-blue-100 text-blue-700 text-xs rounded-full">
                        <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                        ${escapeHtml(msg.mensaje)}
                    </span>
                </div>`;
            }
            if (msg.sender === 'asesor') {
                return `<div class="flex justify-end">
                    <div class="max-w-xs lg:max-w-sm bg-blue-900 text-white text-sm px-4 py-2.5 rounded-2xl rounded-tr-sm shadow-sm whitespace-pre-wrap break-words">
                        ${renderContent(msg, true)}
                        <p class="text-xs text-blue-300 mt-1 text-right">${hora}</p>
                    </div>
                </div>`;
            }
            return `<div class="flex justify-start">
                <div class="max-w-xs lg:max-w-sm bg-gray-100 text-gray-800 text-sm px-4 py-2.5 rounded-2xl rounded-tl-sm shadow-sm whitespace-pre-wrap break-words">
                    ${renderContent(msg, false)}
                    <p class="text-xs text-gray-400 mt-1">${hora}</p>
                </div>
            </div>`;
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

                const newIds = data.map(m => m.id);

                // Sin cambios: no tocar el DOM para no interrumpir video/audio en reproducción
                if (newIds.length === renderedIds.length && newIds.every((id, i) => id === renderedIds[i])) {
                    return;
                }

                const wasNearBottom = renderedIds.length === 0 || isNearBottom(box);

                // Si solo se agregaron mensajes nuevos al final, los anexamos sin re-renderizar lo existente
                const isAppendOnly = newIds.length > renderedIds.length
                    && renderedIds.every((id, i) => id === newIds[i]);

                if (isAppendOnly) {
                    box.insertAdjacentHTML('beforeend', data.slice(renderedIds.length).map(renderMsg).join(''));
                } else {
                    box.innerHTML = data.map(renderMsg).join('');
                }

                renderedIds = newIds;

                if (wasNearBottom) scrollBottom();
            });
        }

        scrollBottom();
        setInterval(cargarMensajes, 2000);
    </script>

</x-app-layout>
