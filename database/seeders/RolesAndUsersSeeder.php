<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesAndUsersSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'sistema']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'asesor']);

        $sistema = User::firstOrCreate(
            ['email' => 'abrahamalanya@laravel.com'],
            ['name' => 'Abraham Alanya', 'password' => bcrypt('abrahamalanya')]
        );
        $sistema->assignRole('sistema');
    }
}
