<?php

namespace Tests\Feature;

use App\Models\Advisor;
use App\Models\Assignment;
use App\Models\Message;
use App\Models\User;
use App\Models\WhatsappNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PanelAsesorCasosLimitesTest extends TestCase
{
    use RefreshDatabase;

    private Advisor $advisor;

    private Advisor $otroAdvisor;

    private User $asesorUser;

    private ?WhatsappNumber $linea = null;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('asesor');

        $this->asesorUser = User::factory()->create();
        $this->asesorUser->assignRole('asesor');

        $this->advisor = Advisor::create([
            'nombre'   => 'Asesor Uno',
            'telefono' => '51999999999',
            'activo'   => true,
            'user_id'  => $this->asesorUser->id,
        ]);

        $this->otroAdvisor = Advisor::create([
            'nombre'   => 'Asesor Dos',
            'telefono' => '51888888888',
            'activo'   => true,
        ]);
    }

    private function linea(): WhatsappNumber
    {
        return $this->linea ??= WhatsappNumber::create([
            'nombre'          => 'Linea 1',
            'phone_number_id' => '123456',
            'activo'          => true,
        ]);
    }

    /** Usuario con rol asesor pero sin ficha de Advisor: el panel no debe reventar. */
    public function test_usuario_asesor_sin_ficha_de_advisor_no_rompe_el_panel()
    {
        $huerfano = User::factory()->create();
        $huerfano->assignRole('asesor');
        $this->assertNull($huerfano->advisor);

        $this->actingAs($huerfano)
            ->get(route('chat.index'))
            ->assertOk()
            ->assertSee('No tienes clientes asignados');

        // Y las acciones que sí necesitan advisor deben responder 403, no 500
        $this->actingAs($huerfano)
            ->from(route('chat.index'))
            ->post(route('chat.accept'), ['cliente_telefono' => '51900000000'])
            ->assertForbidden();
    }

    /** Un asesor no debería poder abrir la conversación de un cliente de otro. */
    public function test_asesor_no_puede_ver_conversacion_de_otro_asesor()
    {
        $linea = $this->linea();

        Assignment::create([
            'cliente_telefono'        => '51955555555',
            'advisor_id'              => $this->otroAdvisor->id,
            'whatsapp_number_id'      => $linea->id,
            'status'                  => Assignment::STATUS_ASSIGNED,
            'accepted_at'             => now(),
            'conversation_expires_at' => now()->addHour(),
        ]);

        Message::create([
            'cliente_telefono'   => '51955555555',
            'advisor_id'         => $this->otroAdvisor->id,
            'whatsapp_number_id' => $linea->id,
            'mensaje'            => 'DATO PRIVADO DE OTRO ASESOR',
            'sender'             => 'cliente',
            'tipo'               => 'texto',
        ]);

        $this->actingAs($this->asesorUser)
            ->get(route('chat.index', ['cliente' => '51955555555']))
            ->assertOk()
            ->assertDontSee('DATO PRIVADO DE OTRO ASESOR');

        $this->actingAs($this->asesorUser)
            ->getJson(route('chat.messages', ['cliente_telefono' => '51955555555']))
            ->assertForbidden();
    }

    /** Cliente asignado a un asesor sin usuario linked: no lo ve nadie. */
    public function test_asignacion_a_asesor_huerfano_no_es_visible()
    {
        Assignment::create([
            'cliente_telefono'   => '51944444444',
            'advisor_id'         => $this->otroAdvisor->id, // sin user_id
            'whatsapp_number_id' => $this->linea()->id,
            'status'             => Assignment::STATUS_ASSIGNED,
        ]);

        $this->actingAs($this->asesorUser)
            ->get(route('chat.index'))
            ->assertDontSee('51944444444');
    }

    /**
     * El panel con la pestaña abierta debe recibir la asignación sin recargar:
     * el endpoint de la lista es lo que consulta el polling del front.
     */
    public function test_la_lista_se_actualiza_sin_recargar_la_pagina()
    {
        $asignacion = Assignment::create([
            'cliente_telefono'   => '51933333333',
            'advisor_id'         => $this->advisor->id,
            'whatsapp_number_id' => $this->linea()->id,
            'status'             => Assignment::STATUS_ASSIGNED,
        ]);

        $primera = $this->actingAs($this->asesorUser)
            ->getJson(route('chat.clientesLista'))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('pendientes', ['51933333333']);

        $html = $primera->json('html');
        $firma = $primera->json('firma');

        $this->assertStringContainsString('51933333333', $html);
        $this->assertStringContainsString('Pendiente aceptar', $html);
        $this->assertNotEmpty($firma);

        // Mientras nada cambia no se vuelve a renderizar el partial
        $this->actingAs($this->asesorUser)
            ->getJson(route('chat.clientesLista', ['firma' => $firma]))
            ->assertOk()
            ->assertJsonPath('html', null)
            ->assertJsonPath('firma', $firma);

        // Tras aceptar, la lista ya no lo marca como pendiente
        $asignacion->update([
            'accepted_at'             => now(),
            'conversation_expires_at' => now()->addHour(),
        ]);

        $this->actingAs($this->asesorUser)
            ->getJson(route('chat.clientesLista', ['firma' => $firma]))
            ->assertJsonPath('pendientes', []);
    }

    /** Asignar un cliente nuevo debe invalidar la firma (si no, nunca se vería). */
    public function test_una_asignacion_nueva_cambia_la_firma_de_la_lista()
    {
        Assignment::create([
            'cliente_telefono'   => '51922222222',
            'advisor_id'         => $this->advisor->id,
            'whatsapp_number_id' => $this->linea()->id,
            'status'             => Assignment::STATUS_ASSIGNED,
        ]);

        $firmaInicial = $this->actingAs($this->asesorUser)
            ->getJson(route('chat.clientesLista'))
            ->json('firma');

        Assignment::create([
            'cliente_telefono'   => '51922222223',
            'advisor_id'         => $this->advisor->id,
            'whatsapp_number_id' => $this->linea()->id,
            'status'             => Assignment::STATUS_ASSIGNED,
        ]);

        $this->actingAs($this->asesorUser)
            ->getJson(route('chat.clientesLista', ['firma' => $firmaInicial]))
            ->assertJsonPath('total', 2)
            ->assertJsonPath('pendientes', ['51922222222', '51922222223']);
    }
}
