<?php

namespace App\Console\Commands;

use App\Models\Advisor;
use App\Models\Assignment;
use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

/**
 * Diagnóstico de la ventana del asesor.
 *
 * Cuando alguien reporta "asigné un cliente y no le aparece al asesor", casi
 * siempre es una de estas tres cosas: el asesor tiene el panel abierto sin
 * recargar (ya no aplica: la lista se refresca sola), el cliente se asignó a
 * una ficha de Advisor que no tiene usuario (nadie puede verlo nunca), o la
 * ficha del asesor está duplicada y el admin eligió la otra.
 */
class DiagnoseAssignments extends Command
{
    protected $signature = 'assignments:diagnostico
                            {--advisor= : Limitar a un asesor por nombre o teléfono}
                            {--horas=24 : Sólo asignaciones de las últimas N horas}';

    protected $description = 'Revisa por qué una asignación podría no verse en la ventana de un asesor';

    public function handle(): int
    {
        $horas    = max(1, (int) $this->option('horas'));
        $filtro   = $this->option('advisor');
        $desde    = now()->subHours($horas);

        $this->line("<fg=cyan>Diagnóstico de asignaciones</> (últimas {$horas}h)");
        $this->newLine();

        $this->warnAsesoresSinUsuario();
        $this->warnNombresDuplicados();
        $this->warnUsuariosSinFicha();
        $this->listarAsignacionesRecientes($filtro, $desde);

        return self::SUCCESS;
    }

    private function warnAsesoresSinUsuario(): void
    {
        $huerfanos = Advisor::whereNull('user_id')->withCount('assignments')->get();

        if ($huerfanos->isEmpty()) {
            $this->info('✓ Todos los asesores tienen usuario linked.');

            return;
        }

        $this->newLine();
        $this->error('✗ Asesores SIN usuario: sus clientes no los ve nadie en el panel.');

        foreach ($huerfanos as $advisor) {
            $this->line(sprintf(
                '  #%d %s (%s) — %d asignación(es) · %d sin aceptar',
                $advisor->id,
                $advisor->nombre,
                $advisor->telefono,
                $advisor->assignments_count,
                $advisor->assignments()->assigned()->whereNull('accepted_at')->count(),
            ));
        }

        $this->line('  <fg=yellow>→ Asigna esas fichas a un usuario real o muévele los clientes.</>');
    }

    private function warnNombresDuplicados(): void
    {
        // Agrupado en PHP y no en SQL: la comparación sin distinguir mayúsculas
        // depende del motor (MySQL vs SQLite) y aquí son pocas filas.
        $porNombre = Advisor::all()->groupBy(fn (Advisor $a) => mb_strtolower(trim($a->nombre)));

        $duplicados = $porNombre->filter(fn ($grupo) => $grupo->count() > 1);

        if ($duplicados->isEmpty()) {
            return;
        }

        $this->newLine();
        $this->warn('⚠ Hay nombres de asesor repetidos: en el dashboard se eligen por nombre,'
            . ' así que es fácil asignar a la ficha equivocada.');

        foreach ($duplicados as $grupo) {
            $this->line("  \"{$grupo->first()->nombre}\" × {$grupo->count()}:");

            foreach ($grupo as $advisor) {
                $this->line(sprintf(
                    '    #%d · %s · user_id=%s · %d cliente(s)',
                    $advisor->id,
                    $advisor->telefono,
                    $advisor->user_id ?? '—',
                    $advisor->assignments()->count(),
                ));
            }
        }
    }

    private function warnUsuariosSinFicha(): void
    {
        $roles = Role::whereIn('name', ['asesor', 'supervisor'])->pluck('name');

        if ($roles->isEmpty()) {
            return;
        }

        $usuarios = User::role($roles)->whereDoesntHave('advisor')->get();

        if ($usuarios->isEmpty()) {
            return;
        }

        $this->newLine();
        $this->error('✗ Usuarios con rol asesor/supervisor SIN ficha de Advisor:'
            . ' su panel queda vacío siempre.');

        foreach ($usuarios as $usuario) {
            $this->line("  #{$usuario->id} {$usuario->name} <{$usuario->email}>");
        }
    }

    private function listarAsignacionesRecientes(?string $filtro, $desde): void
    {
        $this->newLine();
        $this->line('<fg=cyan>Asignaciones de la última(s) hora(s)</> (id · cliente · asesor · estado):');

        $query = Assignment::with('advisor')
            ->where('created_at', '>=', $desde)
            ->orderByDesc('id')
            ->limit(50);

        if ($filtro) {
            $query->whereHas('advisor', fn ($q) => $q
                ->where('nombre', 'like', "%{$filtro}%")
                ->orWhere('telefono', 'like', "%{$filtro}%"));
        }

        $filas = $query->get();

        if ($filas->isEmpty()) {
            $this->line('  (ninguna)');

            return;
        }

        $this->table(
            ['ID', 'Cliente', 'Asesor (ficha)', 'Usuario', 'Estado', 'Acción'],
            $filas->map(function (Assignment $a) {
                $estado = match (true) {
                    $a->status === Assignment::STATUS_PENDING => 'en cola',
                    $a->status === Assignment::STATUS_CLOSED   => 'cerrada',
                    $a->accepted_at === null                   => 'ASIGNADA SIN ACEPTAR',
                    $a->isConversationActive()                  => 'sesión activa',
                    default                                   => 'expirada',
                };

                return [
                    $a->id,
                    $a->cliente_telefono,
                    $a->advisor ? "#{$a->advisor->id} {$a->advisor->nombre}" : '— sin asesor',
                    $a->advisor?->user_id ? "#{$a->advisor->user_id}" : '<fg=red>sin cuenta</>',
                    $estado,
                    $a->status === Assignment::STATUS_PENDING ? 'asignar desde el dashboard' : '—',
                ];
            })->all()
        );
    }
}
