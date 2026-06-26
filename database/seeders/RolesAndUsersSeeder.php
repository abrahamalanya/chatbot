<?php

namespace Database\Seeders;

use App\Models\Advisor;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesAndUsersSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $asesor = Role::firstOrCreate(['name' => 'asesor']);

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@credimas.com'],
            ['name' => 'Administrador', 'password' => bcrypt('password')]
        );
        $adminUser->assignRole($admin);

        $asesorUser = User::firstOrCreate(
            ['email' => 'asesor@credimas.com'],
            ['name' => 'Asesor Demo', 'password' => bcrypt('password')]
        );
        $asesorUser->assignRole($asesor);

        Advisor::firstOrCreate(
            ['telefono' => '51999999999'],
            [
                'nombre'  => 'Asesor Demo',
                'activo'  => true,
                'user_id' => $asesorUser->id,
            ]
        );
    }
}
