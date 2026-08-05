<?php

namespace App\Http\Controllers;

use App\Models\WhatsappNumber;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WhatsappNumberController extends Controller
{
    public function index()
    {
        $whatsappNumbers = WhatsappNumber::latest()->paginate(10);
        return view('whatsapp-numbers.index', compact('whatsappNumbers'));
    }

    public function create()
    {
        return view('whatsapp-numbers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'                    => 'required|string|max:100',
            'phone_number_id'           => 'required|string|max:50|unique:whatsapp_numbers',
            'display_phone_number'      => 'nullable|string|max:30',
            'activo'                    => 'boolean',
            'template_asesor_asignado'  => 'nullable|string|max:100',
            'template_asesor_acepto'    => 'nullable|string|max:100',
        ]);

        WhatsappNumber::create([
            ...$data,
            'activo' => $data['activo'] ?? true,
        ]);

        return redirect()->route('whatsapp-numbers.index')->with('success', 'Número de WhatsApp creado correctamente.');
    }

    public function edit(WhatsappNumber $whatsappNumber)
    {
        return view('whatsapp-numbers.edit', compact('whatsappNumber'));
    }

    public function update(Request $request, WhatsappNumber $whatsappNumber)
    {
        $data = $request->validate([
            'nombre'                    => 'required|string|max:100',
            'phone_number_id'           => ['required', 'string', 'max:50', Rule::unique('whatsapp_numbers')->ignore($whatsappNumber->id)],
            'display_phone_number'      => 'nullable|string|max:30',
            'activo'                    => 'boolean',
            'template_asesor_asignado'  => 'nullable|string|max:100',
            'template_asesor_acepto'    => 'nullable|string|max:100',
        ]);

        $whatsappNumber->update([
            ...$data,
            'activo' => $data['activo'] ?? false,
        ]);

        return redirect()->route('whatsapp-numbers.index')->with('success', 'Número actualizado.');
    }

    public function destroy(WhatsappNumber $whatsappNumber)
    {
        if ($whatsappNumber->assignments()->exists()) {
            return back()->with('error', 'No se puede eliminar un número con conversaciones asociadas. Desactívalo en su lugar.');
        }

        $whatsappNumber->delete();

        return redirect()->route('whatsapp-numbers.index')->with('success', 'Número eliminado.');
    }
}
