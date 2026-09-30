<?php

namespace Tests\Feature;

use App\Models\Advisor;
use App\Models\Assignment;
use App\Models\User;
use App\Models\WhatsappNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AsignacionVisibleParaAsesorTest extends TestCase
{
    use RefreshDatabase;

    private User $asesorUser;

    private Advisor $advisor;

    private WhatsappNumber $linea;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('asesor');
        Role::findOrCreate('admin');

        $this->asesorUser = User::factory()->create();
        $this->asesorUser->assignRole('asesor');

        $this->advisor = Advisor::create([
            'nombre'   => 'Asesor Uno',
            'telefono' => '51999999999',
            'activo'   => true,
            'user_id'  => $this->asesorUser->id,
        ]);

        $this->linea = WhatsappNumber::create([
            'nombre'         => 'Linea 1',
            'phone_number_id' => '123456',
            'activo'         => true,
        ]);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    /** Cliente nuevo entra a la cola (pending) y el admin lo asigna al asesor. */
    public function test_cliente_asignado_aparece_en_la_ventana_del_asesor()
    {
        Http::fake();

        $assignment = Assignment::create([
            'cliente_telefono'   => '51988888888',
            'advisor_id'         => null,
            'whatsapp_number_id' => $this->linea->id,
            'status'             => Assignment::STATUS_PENDING,
        ]);

        $this->actingAs($this->admin())
            ->post(route('assignments.assign', $assignment), [
                'advisor_id' => $this->advisor->id,
                'duration'   => 60,
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertSame($this->advisor->id, $assignment->fresh()->advisor_id);
        $this->assertSame(Assignment::STATUS_ASSIGNED, $assignment->fresh()->status);
        $this->assertNull($assignment->fresh()->accepted_at);

        $this->actingAs($this->asesorUser)
            ->get(route('chat.index'))
            ->assertOk()
            ->assertSee('51988888888')
            ->assertSee('Pendiente aceptar');

        // El asesor debe poder abrir el chat y ver el botón de aceptar
        $this->actingAs($this->asesorUser)
            ->get(route('chat.index', ['cliente' => '51988888888']))
            ->assertOk()
            ->assertSee('Aceptar cliente');

        $this->actingAs($this->asesorUser)
            ->post(route('chat.accept'), ['cliente_telefono' => '51988888888'])
            ->assertRedirect(route('chat.index', ['cliente' => '51988888888']));

        $this->assertNotNull($assignment->fresh()->accepted_at);
    }

    /** Cliente recurrente: ya tuvo sesión con el asesor y vuelve a la cola. */
    public function test_cliente_recurrente_asignado_nuevamente_aparece()
    {
        Http::fake();

        // Sesión antigua, ya cerrada
        $vieja = Assignment::create([
            'cliente_telefono'   => '51977777777',
            'advisor_id'         => $this->advisor->id,
            'whatsapp_number_id' => $this->linea->id,
            'status'             => Assignment::STATUS_CLOSED,
            'disposition'        => 'completado',
            'accepted_at'        => now()->subDays(2),
        ]);
        $vieja->forceFill(['created_at' => now()->subDays(2), 'updated_at' => now()->subDays(2)])->save();

        // Nueva solicitud del mismo cliente
        $nueva = Assignment::create([
            'cliente_telefono'   => '51977777777',
            'advisor_id'         => null,
            'whatsapp_number_id' => $this->linea->id,
            'status'             => Assignment::STATUS_PENDING,
        ]);

        $this->actingAs($this->admin())
            ->post(route('assignments.assign', $nueva), [
                'advisor_id' => $this->advisor->id,
                'duration'   => 60,
            ]);

        $this->actingAs($this->asesorUser)
            ->get(route('chat.index', ['cliente' => '51977777777']))
            ->assertOk()
            ->assertSee('Aceptar cliente');
    }

    /** El admin reasigna un cliente que ya estaba en la cola hacia este asesor. */
    public function test_reasignacion_desde_otro_asesor_aparece_en_la_ventana_del_nuevo()
    {
        Http::fake();

        $otroAdvisor = Advisor::create([
            'nombre'   => 'Asesor Dos',
            'telefono' => '51888888888',
            'activo'   => true,
        ]);

        $assignment = Assignment::create([
            'cliente_telefono'   => '51966666666',
            'advisor_id'         => $otroAdvisor->id,
            'whatsapp_number_id' => $this->linea->id,
            'status'             => Assignment::STATUS_ASSIGNED,
        ]);

        $this->actingAs($this->admin())
            ->post(route('assignments.assign', $assignment), [
                'advisor_id' => $this->advisor->id,
                'duration'   => 60,
            ]);

        $this->actingAs($this->asesorUser)
            ->get(route('chat.index', ['cliente' => '51966666666']))
            ->assertOk()
            ->assertSee('Aceptar cliente');
    }
}
