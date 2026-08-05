<x-app-layout>
    <x-slot name="title">Editar Número WhatsApp</x-slot>

    <div class="max-w-lg">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="font-semibold text-gray-800 mb-6">Editar número de WhatsApp</h2>

            <form method="POST" action="{{ route('whatsapp-numbers.update', $whatsappNumber) }}" class="space-y-4">
                @csrf @method('PATCH')

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de la línea</label>
                    <input type="text" name="nombre" value="{{ old('nombre', $whatsappNumber->nombre) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('nombre') border-red-400 @enderror">
                    @error('nombre') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number ID (Meta)</label>
                    <input type="text" name="phone_number_id" value="{{ old('phone_number_id', $whatsappNumber->phone_number_id) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-500 @error('phone_number_id') border-red-400 @enderror">
                    @error('phone_number_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono visible (opcional)</label>
                    <input type="text" name="display_phone_number" value="{{ old('display_phone_number', $whatsappNumber->display_phone_number) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('display_phone_number') border-red-400 @enderror">
                    @error('display_phone_number') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Plantilla "asesor asignado" (opcional)</label>
                    <input type="text" name="template_asesor_asignado" value="{{ old('template_asesor_asignado', $whatsappNumber->template_asesor_asignado) }}" placeholder="Usa la plantilla por defecto si se deja vacío"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('template_asesor_asignado') border-red-400 @enderror">
                    @error('template_asesor_asignado') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Plantilla "asesor aceptó" (opcional)</label>
                    <input type="text" name="template_asesor_acepto" value="{{ old('template_asesor_acepto', $whatsappNumber->template_asesor_acepto) }}" placeholder="Usa la plantilla por defecto si se deja vacío"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('template_asesor_acepto') border-red-400 @enderror">
                    @error('template_asesor_acepto') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-2">
                    <input type="hidden" name="activo" value="0">
                    <input type="checkbox" name="activo" id="activo" value="1"
                           class="w-4 h-4 rounded text-blue-600"
                           {{ old('activo', $whatsappNumber->activo) ? 'checked' : '' }}>
                    <label for="activo" class="text-sm text-gray-700">Línea activa</label>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit"
                            class="px-5 py-2 bg-blue-900 text-white text-sm font-medium rounded-lg hover:bg-blue-800 transition">
                        Guardar cambios
                    </button>
                    <a href="{{ route('whatsapp-numbers.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancelar</a>
                </div>
            </form>
        </div>
    </div>

</x-app-layout>
